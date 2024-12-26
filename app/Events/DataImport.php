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
    public $config;
    public $user;
    public $type;
    public $message;

    /**
     * Initializes a new instance of the DataImport event.
     * This constructor sets up the event with the necessary parameters including the file path,
     * configuration for the import process, the authenticated user, the import type, and a status message.
     * It does not dispatch the job itself; instead, the job is dispatched separately as needed.
     *
     * @param string $tempPath The path to the temporary file containing data to be processed.
     * @param array $config The configuration settings for the import process, including mappings and validation rules.
     * @param \App\Models\User $user The authenticated user who initiated the data import process.
     * @param string $type The type of import being performed (e.g., 'full', 'incremental').
     * @param string $message A message that reflects the status or result of the import process (e.g., success or failure).
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