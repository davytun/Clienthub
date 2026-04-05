<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name') }} — Client Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">

    <nav class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('client.projects.index') }}" class="font-semibold text-gray-800">
                        {{ auth()->guard('client')->user()->business->name ?? config('app.name') }}
                    </a>
                    <div class="hidden sm:flex sm:ms-8 space-x-6">
                        <a href="{{ route('client.projects.index') }}"
                           class="text-sm {{ request()->routeIs('client.projects.*') ? 'text-gray-900 font-medium border-b-2 border-indigo-500' : 'text-gray-500 hover:text-gray-700' }} py-5 inline-block">
                            My Projects
                        </a>
                        <a href="{{ route('client.invoices.index') }}"
                           class="text-sm {{ request()->routeIs('client.invoices.*') ? 'text-gray-900 font-medium border-b-2 border-indigo-500' : 'text-gray-500 hover:text-gray-700' }} py-5 inline-block ml-6">
                            My Invoices
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">{{ auth()->guard('client')->user()->name }}</span>
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
