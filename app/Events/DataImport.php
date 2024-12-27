<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DataImport implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tempPath;
    public $fileName;
    public $config;
    public $model;
    public $user;
    public $type;
    public $message;

    /**
     * Constructor for initializing the event with import process details.
     *
     * This constructor method sets up the required properties with the provided import process details.
     * It initializes the temporary file path, file name, configuration, user, type, and message.
     *
     * @param string $tempPath The path to the temporary file used during the import.
     * @param string $fileName The name of the file being processed for import.
     * @param array $config An array containing the configuration settings for the import process.
     * @param string $model The name of the model being processed for import.
     * @param \App\Models\User $user The user who is initiating the import process.
     * @param string $type The type of import being performed.
     * @param string $message A message associated with the import process, typically used for notifications or logging.
     */
    
    public function __construct($tempPath, $fileName, $config, $model, $user, $type, $message)
    {
        $this->tempPath = $tempPath;
        $this->fileName = $fileName;
        $this->config   = $config;
        $this->model    = $model;
        $this->user     = $user;
        $this->type     = $type;
        $this->message  = $message;
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