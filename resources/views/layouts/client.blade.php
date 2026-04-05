<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ auth()->guard('client')->user()->business->name ?? config('app.name') }} — Client Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php $brand = auth()->guard('client')->user()->business->brand_color ?? '#1D9E75'; @endphp
    <style>
        :root { --brand: {{ $brand }}; }
        .brand-border  { border-color: var(--brand) !important; }
        .brand-text    { color: var(--brand) !important; }
        .brand-bg      { background-color: var(--brand) !important; }
        .brand-active-tab { border-bottom: 2px solid var(--brand); color: #111; font-weight: 600; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">

    @php
        $business = auth()->guard('client')->user()->business;
        $client   = auth()->guard('client')->user();
    @endphp

    <nav class="bg-white border-b-2 brand-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    {{-- Logo or business name --}}
                    <a href="{{ route('client.projects.index') }}" class="flex items-center gap-2">
                        @if($business->logo_path)
                            <img src="{{ route('client.logo') }}" alt="{{ $business->name }}" class="h-8 object-contain" />
                        @else
                            <span class="font-semibold text-gray-800">{{ $business->name }}</span>
                        @endif
                    </a>

                    <div class="hidden sm:flex sm:ms-8 space-x-6">
                        <a href="{{ route('client.projects.index') }}"
                           class="text-sm py-5 inline-flex items-center gap-1 px-1 {{ request()->routeIs('client.projects.*') ? 'brand-active-tab' : 'text-gray-500 hover:text-gray-700' }}">
                            My Projects
                            @php $unread = $client->unreadMessageCount(); @endphp
                            @if($unread > 0)
                                <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full">
                                    {{ $unread > 9 ? '9+' : $unread }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('client.invoices.index') }}"
                           class="text-sm py-5 inline-block px-1 {{ request()->routeIs('client.invoices.*') ? 'brand-active-tab' : 'text-gray-500 hover:text-gray-700' }}">
                            My Invoices
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">{{ $client->name }}</span>
                    <form method="POST" action="{{ route('client.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    @if(isset($header))
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endif

    <main>{{ $slot }}</main>

</body>
</html>
