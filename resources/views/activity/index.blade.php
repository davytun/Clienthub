<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Activity Log</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                @if($logs->isEmpty())
                    <div class="p-8 text-center text-gray-500">No activity recorded yet.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Who</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($logs as $log)
                                <tr>
                                    <td class="px-6 py-3 text-xs text-gray-400 whitespace-nowrap">
                                        {{ $log->created_at->diffForHumans() }}
                                        <span class="block text-gray-300">{{ $log->created_at->format('d M Y H:i') }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-700">
                                        {{ $log->actor?->name ?? 'System' }}
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @switch(explode('.', $log->action)[0])
                                                @case('invoice') bg-blue-100 text-blue-800 @break
                                                @case('project') bg-purple-100 text-purple-800 @break
                                                @case('file')    bg-yellow-100 text-yellow-800 @break
                                                @case('client')  bg-green-100 text-green-800 @break
                                                @case('staff')   bg-orange-100 text-orange-800 @break
                                                @default         bg-gray-100 text-gray-700
                                            @endswitch
                                        ">
                                            {{ $log->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-500">
                                        @if($log->meta)
                                            @if(isset($log->meta['number']))
                                                {{ $log->meta['number'] }}
                                            @elseif(isset($log->meta['title']))
                                                {{ $log->meta['title'] }}
                                            @elseif(isset($log->meta['name']))
                                                {{ $log->meta['name'] }}
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
