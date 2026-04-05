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

    {{-- 30-second page refresh for messages --}}
    <meta http-equiv="refresh" content="30">

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
                <div class="px-6 py-4 border-b border-gray-100">
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
                                    <p class="text-xs text-gray-400">{{ $file->sizeForHumans() }} &middot; {{ $file->uploader->name }} &middot; {{ $file->created_at->format('d M Y') }}</p>
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

                @can('uploadFile', $project)
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        <form method="POST" action="{{ route('projects.files.store', $project) }}" enctype="multipart/form-data" class="flex items-center gap-3">
                            @csrf
                            <input type="file" name="file" required class="text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300" />
                            <x-primary-button type="submit">Upload</x-primary-button>
                        </form>
                        @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-400">PDF, DOC, DOCX, XLS, XLSX, PNG, JPG — max 10 MB</p>
                    </div>
                @endcan
            </div>

            {{-- Messages --}}
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Messages</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Refreshes every 30 seconds</p>
                </div>

                <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                    @forelse($project->messages as $msg)
                        @php $isMine = $msg->sender_id === auth()->id(); @endphp
                        <div class="px-6 py-4 flex {{ $isMine ? 'flex-row-reverse' : 'flex-row' }} gap-3">
                            <div class="shrink-0 w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                                {{ strtoupper(substr($msg->sender->name, 0, 1)) }}
                            </div>
                            <div class="{{ $isMine ? 'items-end' : 'items-start' }} flex flex-col max-w-lg">
                                <p class="text-xs text-gray-400 mb-1">
                                    {{ $msg->sender->name }} &middot; {{ $msg->created_at->diffForHumans() }}
                                </p>
                                <div class="px-4 py-2 rounded-lg text-sm {{ $isMine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $msg->body }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-4 text-sm text-gray-500">No messages yet. Start the conversation.</p>
                    @endforelse
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <form method="POST" action="{{ route('projects.messages.store', $project) }}">
                        @csrf
                        @error('body') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div class="flex gap-3">
                            <textarea name="body" rows="2" placeholder="Type a message…" required
                                class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none">{{ old('body') }}</textarea>
                            <x-primary-button type="submit" class="self-end">Send</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
