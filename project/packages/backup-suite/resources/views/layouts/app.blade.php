<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safeguard Scheduler</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 flex">

    {{-- ── SIDEBAR ── --}}
    <aside class="fixed top-0 left-0 h-screen w-60 bg-slate-900 flex flex-col z-40 shadow-2xl">

        {{-- Logo / Branding --}}
        <div class="px-6 pt-8 pb-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center shadow-lg shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm leading-tight">Safeguard</p>
                    <p class="text-slate-400 text-xs">Backup Scheduler</p>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-5 space-y-1">
            @php
                $navItems = [
                    [
                        'label' => 'Projects',
                        'route' => 'backup-suite.projects.index',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12c0 2.21 3.582 4 8 4s8-1.79 8-4"/>',
                    ],
                    [
                        'label' => 'Backups',
                        'route' => 'backup-suite.schedules.index',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                    ],
                    [
                        'label' => 'History',
                        'route' => 'backup-suite.history.index',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                    ],
                    [
                        'label' => 'Settings',
                        'route' => 'backup-suite.settings.index',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                    ],
                ];
            @endphp

            @foreach($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                   {{ $active
                       ? 'bg-white/10 text-white shadow-inner'
                       : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    {{-- Active indicator bar --}}
                    <span class="w-0.5 h-5 rounded-full {{ $active ? 'bg-emerald-400' : 'bg-transparent' }} shrink-0"></span>
                    <svg class="w-4.5 h-4.5 shrink-0 {{ $active ? 'text-emerald-400' : '' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $item['icon'] !!}
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- New Schedule button --}}
        <div class="px-3 pb-4">
            <button onclick="typeof openDrawer !== 'undefined' ? openDrawer('create') : window.location='{{ route('backup-suite.schedules.index') }}#new'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-semibold transition shadow-lg">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                New Schedule
            </button>
        </div>

        {{-- Footer --}}
        <div class="px-5 py-4 border-t border-white/5">
            <p class="text-xs text-slate-600">v1.0 · Backup Suite</p>
        </div>
    </aside>

    {{-- ── MAIN CONTENT ── --}}
    <div class="ml-60 flex-1 min-h-screen flex flex-col">

        {{-- Top bar --}}
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div>
                @php
                    $pageTitle = match(true) {
                        request()->routeIs('backup-suite.projects.index')  => 'Projects',
                        request()->routeIs('backup-suite.schedules.index') => 'Backups',
                        request()->routeIs('backup-suite.history.index')   => 'History',
                        request()->routeIs('backup-suite.settings.index')  => 'Settings',
                        default => 'Backup Suite',
                    };
                    $pageDesc = match(true) {
                        request()->routeIs('backup-suite.projects.index')  => 'Manage Laravel projects and run per-project backups',
                        request()->routeIs('backup-suite.schedules.index') => 'Manage schedules and run manual backups',
                        request()->routeIs('backup-suite.history.index')   => 'Browse and download past backups',
                        request()->routeIs('backup-suite.settings.index')  => 'Configure Google Drive and scheduler',
                        default => '',
                    };
                @endphp
                <h1 class="text-lg font-semibold text-slate-900">{{ $pageTitle }}</h1>
                @if($pageDesc)
                    <p class="text-xs text-slate-400 mt-0.5">{{ $pageDesc }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-900 grid place-content-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        <div class="px-8 pt-5 space-y-3">
            @if(session('status'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 flex items-center gap-2 text-sm shadow-sm">
                    <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 flex items-center gap-2 text-sm shadow-sm">
                    <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @if(!empty($errors) && $errors->any())
                <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm shadow-sm">
                    <p class="font-semibold mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 px-8 py-6">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

</body>
</html>
