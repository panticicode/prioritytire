<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Jobs\DataImportJob;

class DataImport implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tempPath;
    public $config;
    public $user;
    public $type;
    public $message;

    /**
     * Creates a new instance of the DataImport event.
     * This constructor initializes the event with the provided parameters including the file path,
     * configuration, user, import type, and a success message.
     * It also dispatches a job to handle the data import process.
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

        DataImportJob::dispatch($tempPath, $config, $user, $type, $message);
    }

    /**
     * Specifies the channels that the event should broadcast on.
     * In this case, the event will be broadcast on the 'data-import' channel.
     *
     * @return array The list of channels the event will broadcast on.
     */

    public function broadcastOn()
    {
        return ['data-import'];
    }

    /**
     * Specifies the name of the event to broadcast.
     * In this case, the event will be broadcast as 'DataImport'.
     *
     * @return string The name of the event.
     */
    
    public function broadcastAs()
    {
        return 'DataImport';
    }
}