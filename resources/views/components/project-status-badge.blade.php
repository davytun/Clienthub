@props(['status'])

@php
    $classes = match($status) {
        'active'    => 'bg-green-100 text-green-800',
        'on_hold'   => 'bg-yellow-100 text-yellow-800',
        'completed' => 'bg-gray-100 text-gray-600',
        default     => 'bg-gray-100 text-gray-600',
    };

    $label = match($status) {
        'active'    => 'Active',
        'on_hold'   => 'On Hold',
        'completed' => 'Completed',
        default     => ucfirst($status),
    };
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
    {{ $label }}
</span>
