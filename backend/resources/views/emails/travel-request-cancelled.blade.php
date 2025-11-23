@component('mail::message')
# Travel Request Cancelled

Hello {{ $userName }},

We regret to inform you that your travel request has been cancelled.

@component('mail::panel')
**Destination:** {{ $destination }}  
**Departure Date:** {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}  
**Return Date:** {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
@if($reason)

**Cancellation Reason:** {{ $reason }}
@endif
@endcomponent

If you have any questions or would like to submit a new travel request, please contact your manager or HR department.

@component('mail::button', ['url' => config('app.frontend_url', config('app.url')) . '/travel-requests'])
View All Travel Requests
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
