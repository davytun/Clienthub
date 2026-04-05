<x-client-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('client.projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; All Projects</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-1">{{ $project->title }}</h2>
            </div>
            <x-project-status-badge :status="$project->status" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Description --}}
            @if($project->description)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">About this project</h3>
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $project->description }}</p>
                </div>
            @endif

            {{-- Files --}}
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Files</h3>
                </div>

                @if($project->files->isEmpty())
                    <p class="px-6 py-4 text-sm text-gray-500">No files have been shared yet.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($project->files as $file)
                            <li class="px-6 py-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $file->original_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $file->sizeForHumans() }} &middot; {{ $file->created_at->format('d M Y') }}</p>
                                </div>
                                <a href="{{ route('client.projects.files.download', [$project, $file]) }}"
                                   class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                                    Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </div>
</x-client-layout>
