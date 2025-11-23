@component('mail::message')
# Welcome to {{ config('app.name') }}!

Hello {{ $userName }},

We're thrilled to have you on board! Your account has been successfully created.

@component('mail::panel')
**Email:** {{ $userEmail }}
**Account Type:** {{ ucfirst($user->role) }}
@endcomponent

You can now start using our Travel System to manage your travel requests.

@component('mail::button', ['url' => config('app.frontend_url', config('app.url'))])
Access Your Dashboard
@endcomponent

If you have any questions or need assistance, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent

