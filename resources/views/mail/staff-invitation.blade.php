<x-mail::message>
# You've been invited to join {{ $business->name }}

Hi **{{ $member->name }}**,

**{{ $business->name }}** has invited you to join their team as a staff member on Clienthub.

Click the button below to set your password and activate your account. This link expires in **72 hours**.

<x-mail::button :url="$inviteUrl">
Accept Invitation
</x-mail::button>

If you were not expecting this invitation you can safely ignore this email.

Thanks,
{{ $business->name }}
</x-mail::message>
