<x-client-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Projects</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if($projects->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-500">
                    No projects yet. Your service provider will add them here.
                </div>
            @else
                <div class="space-y-4">
                    @foreach($projects as $project)
                        <a href="{{ route('client.projects.show', $project) }}" class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $project->title }}</h3>
                                    @if($project->description)
                                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $project->description }}</p>
                                    @endif
                                </div>
                                <x-project-status-badge :status="$project->status" />
                            </div>
                            <p class="text-xs text-gray-400 mt-3">Created {{ $project->created_at->format('d M Y') }}</p>
                        </a>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-client-layout>
