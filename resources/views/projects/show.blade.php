<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->title }}</h2>
                <p class="text-sm text-gray-500 mt-1">Client: {{ $project->client->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-project-status-badge :status="$project->status" />
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Edit</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Description --}}
            @if($project->description)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Description</h3>
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $project->description }}</p>
                </div>
            @endif

            {{-- Files --}}
            <div class="bg-white shadow-sm rounded-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Files</h3>
                </div>

                @if($project->files->isEmpty())
                    <p class="px-6 py-4 text-sm text-gray-500">No files uploaded yet.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($project->files as $file)
                            <li class="px-6 py-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $file->original_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $file->sizeForHumans() }} &middot; Uploaded by {{ $file->uploader->name }} &middot; {{ $file->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('files.download', $file) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Download</a>
                                    @can('delete', $file)
                                        <form method="POST" action="{{ route('files.destroy', $file) }}" onsubmit="return confirm('Delete this file?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm text-red-500 hover:text-red-700">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Upload form --}}
                @can('uploadFile', $project)
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        <form method="POST" action="{{ route('projects.files.store', $project) }}" enctype="multipart/form-data" class="flex items-center gap-3">
                            @csrf
                            <input type="file" name="file" required class="text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300" />
                            <x-primary-button type="submit">Upload</x-primary-button>
                        </form>
                        @error('file')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-400">PDF, DOC, DOCX, XLS, XLSX, PNG, JPG — max 10 MB</p>
                    </div>
                @endcan
            </div>

        </div>
    </div>
</x-app-layout>
