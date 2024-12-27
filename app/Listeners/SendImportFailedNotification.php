<?php

namespace App\Listeners;

use App\Events\ImportFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\ImportFailedMail;

class SendImportFailedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImportFailed $event): void
    {
        Mail::to($event->user->email)->send(new ImportFailedMail($event->user, $event->message));
    }
}
