@component('mail::message')
# {{ $notificationTitle }}

@if($notificationBody)
{{ $notificationBody }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
