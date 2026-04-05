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

            {{-- Messages --}}
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Messages</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Refreshes every 30 seconds</p>
                </div>

                <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                    @php $clientId = auth()->guard('client')->id(); @endphp
                    @forelse($project->messages as $msg)
                        @php $isMine = $msg->sender_id === $clientId; @endphp
                        <div class="px-6 py-4 flex {{ $isMine ? 'flex-row-reverse' : 'flex-row' }} gap-3">
                            <div class="shrink-0 w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                                {{ strtoupper(substr($msg->sender->name, 0, 1)) }}
                            </div>
                            <div class="{{ $isMine ? 'items-end' : 'items-start' }} flex flex-col max-w-lg">
                                <p class="text-xs text-gray-400 mb-1">
                                    {{ $isMine ? 'You' : $msg->sender->name }} &middot; {{ $msg->created_at->diffForHumans() }}
                                </p>
                                <div class="px-4 py-2 rounded-lg text-sm {{ $isMine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $msg->body }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-4 text-sm text-gray-500">No messages yet.</p>
                    @endforelse
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <form method="POST" action="{{ route('client.projects.messages.store', $project) }}">
                        @csrf
                        @error('body') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div class="flex gap-3">
                            <textarea name="body" rows="2" placeholder="Type a message…" required
                                class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none">{{ old('body') }}</textarea>
                            <button type="submit" class="self-end px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-client-layout>
