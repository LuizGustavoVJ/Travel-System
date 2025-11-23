@component('mail::message')
# Travel Request Created

Hello {{ $userName }},

Your travel request has been successfully created and is now pending approval.

@component('mail::panel')
**Destination:** {{ $destination }}
**Departure Date:** {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
**Return Date:** {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
**Status:** Pending Approval
@endcomponent

Your request will be reviewed by an administrator. You will receive a notification once a decision has been made.

@component('mail::button', ['url' => config('app.frontend_url', config('app.url')) . '/travel-requests/' . $travelRequest->id])
View Travel Request
@endcomponent

If you have any questions, please contact your manager or HR department.

Thanks,<br>
{{ config('app.name') }}
@endcomponent

