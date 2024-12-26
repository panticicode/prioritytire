<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Events\DataImport;
use App\Models\Order;
use App\Models\ImportLog;
use Validator;

class DataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tempPath;
    protected $config;
    protected $user;
    protected $type;
    protected $message;

    /**
     * Creates a new job instance.
     * The constructor initializes the job with the provided parameters including the file path,
     * configuration, user, import type, and a success message.
     *
     * @param string $tempPath The path to the temporary file to be processed.
     * @param array $config The configuration for the import process.
     * @param \App\Models\User $user The authenticated user initiating the import.
     * @param string $type The type of import being processed.
     * @param string $message A message indicating the status of the import process.
     * @return void
     */
    public function __construct($tempPath, $config, $user, $type, $message)
    {
        $this->tempPath = $tempPath;
        $this->config   = $config;
        $this->user     = $user;
        $this->type     = $type;
        $this->message  = $message;
    }

    /**
     * Handles the data import process.
     * Validates the imported data and processes each row according to the provided configuration.
     * Logs validation errors, updates or creates records in the database, and associates the imported orders with the user.
     * The import status is determined based on the number of processed rows and validation errors.
     * 
     * @return void
     */
    public function handle(): void
    {
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

        $orderIds      = [];
        $hasErrors     = false;
        $processedRows = 0;

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
                            'user_id'            => $this->user->id,
                            'import_type'        => $this->type,
                            'row_number'         => $index + 2,
                            'column'             => $column,
                            'invalid_value'      => $rowData[$column] ?? null,
                            'validation_message' => $message,
                        ]);
                    }
                }
                continue;
            }

            $updateKeys = $this->config['update_or_create'];
            $updateData = array_intersect_key($rowData, array_flip($updateKeys));
            $createData = array_diff_key($rowData, $updateData);

            $order = Order::updateOrCreate($updateData, $createData);
            $orderIds[] = $order->id;
            $processedRows++;
        }

        $this->user->orders()->attach($orderIds, ['type' => $this->type]);

        Storage::delete($this->tempPath);

        // Setting message based on the result of the import

        $status = (!$processedRows) ? 'failed' : ($hasErrors ? 'errors' : 'success');

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

        event(new DataImport($this->tempPath, $this->config, $this->user, $this->type, $this->message));
    }
}
