<x-mail::message>
# You've been invited

Hi {{ $client->name }},

**{{ $business->name }}** has invited you to their client portal.

You can view your projects, files, and invoices — all in one place.

Click the button below to set your password and get started. This link expires in **72 hours**.

<x-mail::button :url="$inviteUrl">
Accept Invitation
</x-mail::button>

If you didn't expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ $business->name }}
</x-mail::message>
