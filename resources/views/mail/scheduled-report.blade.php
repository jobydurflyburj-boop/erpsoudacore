@component('mail::message')
# Scheduled Report: {{ $reportName }}

Your scheduled report is attached to this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
