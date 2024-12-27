<x-mail::message>
# Import Failed Notification

Hello {{ $username }},

We regret to inform you that an error occurred during the import process.

**Error Message:**
{{ $message }}

Please review the error and try again.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>