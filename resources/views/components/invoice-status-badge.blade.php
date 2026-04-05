@props(['status'])

@php
    $classes = match($status) {
        'draft' => 'bg-gray-100 text-gray-600',
        'sent'  => 'bg-blue-100 text-blue-800',
        'paid'  => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
    {{ ucfirst($status) }}
</span>
