@component('mail::message')
# Travel Request Approved

Hello {{ $userName }},

Great news! Your travel request has been approved.

@component('mail::panel')
**Destination:** {{ $destination }}  
**Departure Date:** {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}  
**Return Date:** {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
@endcomponent

You can now proceed with your travel arrangements.

@component('mail::button', ['url' => config('app.frontend_url', config('app.url')) . '/travel-requests/' . $travelRequest->id])
View Travel Request
@endcomponent

If you have any questions, please contact your manager or HR department.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
