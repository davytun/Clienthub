<x-mail::message>
# New message on {{ $project->title }}

Hi,

**{{ $message->sender->name }}** sent a message on project **{{ $project->title }}**.

> {{ $message->body }}

@if($recipientIsClient)
<x-mail::button :url="route('client.projects.show', $project)">
View Project
</x-mail::button>
@else
<x-mail::button :url="route('projects.show', $project)">
View Project
</x-mail::button>
@endif

Thanks,<br>
{{ $project->business->name }}
</x-mail::message>
