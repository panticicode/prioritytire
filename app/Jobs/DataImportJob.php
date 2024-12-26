<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Order;
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
     * Executes the job to handle the data import.
     * This method processes the uploaded file by validating headers and rows,
     * and inserting or updating orders based on the imported data.
     * After processing, the temporary file is deleted.
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

        $orderIds = [];

        foreach (array_slice($data, 1) as $row) 
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
                continue;
            }

            $updateKeys = $this->config['update_or_create'];
            $updateData = array_intersect_key($rowData, array_flip($updateKeys));
            $createData = array_diff_key($rowData, $updateData);

            $order = Order::updateOrCreate($updateData, $createData);
            $orderIds[] = $order->id;
        }

        $this->user->orders()->attach($orderIds, ['type' => $this->type]);

        Storage::delete($this->tempPath);
    }
}
