<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\UtilityHelper;
use Illuminate\Support\Str;
use App\Events\DataImport;
use App\Models\ImportLog;
use App\Models\AuditLog;
use App\Models\Import;
use App\Models\Order;
use Carbon\Carbon;
use Validator;

class DataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tempPath;
    protected $fileName;
    protected $config;
    protected $user;
    protected $type;
    protected $message;

    public $tries = 3;

    /**
     * Constructor for initializing the job with import process details.
     *
     * This constructor method sets up the required properties with the provided import process details.
     * It initializes the temporary file path, file name, configuration, user, type, and message.
     *
     * @param string $tempPath The path to the temporary file used during the import.
     * @param string $fileName The name of the file being processed for import.
     * @param array $config An array containing the configuration settings for the import process.
     * @param \App\Models\User $user The user who is initiating the import process.
     * @param string $type The type of import being performed.
     * @param string $message A message associated with the import process, typically used for notifications or logging.
     */
    public function __construct($tempPath, $fileName, $config, $user, $type, $message)
    {
        $this->tempPath = $tempPath;
        $this->fileName = $fileName;
        $this->config   = $config;
        $this->user     = $user;
        $this->type     = $type;
        $this->message  = $message;
    }

    /**
     * Handles the import process of data from a temporary file.
     *
     * This method performs the following steps:
     * 
     * - Retrieves the file path and reads the data from an Excel file into an array.
     * - Validates the headers of the data to ensure required headers are present.
     * - Creates an import record in the database to track the import process.
     * - Processes each row of data, validating it against specified rules.
     * - Logs any validation errors and continues processing the next row.
     * - Updates or creates records in the database based on the data.
     * - Logs changes for auditing purposes if any differences are detected.
     * - Attaches the imported models to the user.
     * - Deletes the temporary file after processing.
     * - Sets the import status based on the result and triggers an event.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $filePath = Storage::path($this->tempPath);
            $data = Excel::toArray([], $filePath)[0];

            $headers = $data[0];
            $requiredHeaders = array_keys($this->config["headers_to_db"]);

            foreach ($requiredHeaders as $header) 
            {
                if (!in_array($header, $headers)) 
                {
                    return;
                }
            }

            $modelIds      = [];
            $hasErrors     = false;
            $processedRows = 0;

            $import = Import::create([
                'user_id'  => $this->user->id,
                'type'     => $this->type,
                'filename' => $this->fileName,
                'status'   => 'pending', 
            ]);

            foreach (array_slice($data, 1) as $index => $row) 
            {
                $rowData = array_combine($headers, $row);
                $rules = [];
                foreach ($this->config['headers_to_db'] as $key => $field) 
                {
                    $rules[$key] = $field['validation'];
                }

                $validator = Validator::make($rowData, $rules);

                if ($validator->fails()) 
                {
                    $hasErrors = true;

                    foreach ($validator->errors()->messages() as $column => $messages) 
                    {
                        foreach ($messages as $message) 
                        {
                            ImportLog::create([
                                'user_id' => $this->user->id,
                                'type'    => $this->type,
                                'row'     => $index + 2,
                                'column'  => $column,
                                'value'   => $rowData[$column] ?? null,
                                'message' => $message,
                            ]);
                        }
                    }
                    continue;
                }

                $updateKeys = $this->config['update_or_create'];
                $updateData = array_intersect_key($rowData, array_flip($updateKeys));
                $createData = array_merge(['row' => $index + 2], array_diff_key($rowData, $updateData));
                unset($createData[""]);

                foreach (array_keys(config('imports')) as $key) 
                {
                    // Fetch existing model for comparison
                    $existingModel  = UtilityHelper::model($key)->where($updateData)->first();

                    if ($existingModel) 
                    {
                        // Log changes
                        foreach ($createData as $column => $newValue) 
                        {
                            $oldValue = $existingModel->{$column};

                            if($column === 'order_date')
                            {
                                if (isset($createData['order_date'])) 
                                {
                                    $oldValue = UtilityHelper::formatDate($oldValue, 'd/m/Y');
                                    $newValue = UtilityHelper::formatDate($newValue, 'd/m/Y');
                                }
                            }

                            if (is_null($oldValue) || is_null($newValue)) 
                            {
                                continue;
                            }

                            if ($oldValue != $newValue) 
                            {
                                AuditLog::create([
                                    'import_id' => $import->id,
                                    'model_id'  => $existingModel->id, 
                                    'model'     => $key,
                                    'row'       => $index + 2,
                                    'column'    => $column,
                                    'old_value' => $oldValue,
                                    'new_value' => $newValue,
                                ]);
                            }
                        }
                    }

                    $order      = UtilityHelper::model($key)->updateOrCreate($updateData, $createData);
                    $modelIds[] = $order->id;    
                }

                $processedRows++;
            }

            array_map(function($model) use ($modelIds)
            {
                call_user_func([$this->user, $model])->attach($modelIds, ['type' => $this->type]);
                
            }, array_keys(config('imports')));

            Storage::delete($this->tempPath);

            // Setting message based on the result of the import

            $status = (!$processedRows) ? 'failed' : ($hasErrors ? 'errors' : 'success');

            $import->update(['status' => $status]);
            
            switch ($status) 
            {
                case ('failed'):
                        $this->message = [
                            'theme' => 'danger',
                            'text'  => 'Import Process Failed!'
                        ];
                    break;
                case ('errors'):
                        $this->message = [
                            'theme' => 'warning',
                            'text'  => 'Import Process Finished but some errors occurred!'
                        ];
                    break;
                default:
                        $this->message = [
                            'theme' => 'success',
                            'text'  => 'Import Process Finished Successfully!'
                        ];
                    break;
            }

            event(new DataImport($this->tempPath, $this->fileName, $this->config, $this->user, $this->type, $this->message));
        } catch (Exception $e) {
            \Log::error('Error in DataImportJob: ' . $e->getMessage());
        }
    }
}
