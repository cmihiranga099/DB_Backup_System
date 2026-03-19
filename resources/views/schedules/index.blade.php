@extends('layouts.app')


@section('content')
@php
    $dayNames   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
@endphp

<div class="space-y-5">

    {{-- ── STATS ROW ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs uppercase tracking-widest text-slate-400">Total</p>
            <p class="text-4xl font-bold text-slate-800 mt-1">{{ $totals['backups'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Backups run</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs uppercase tracking-widest text-emerald-500">Success</p>
            <p class="text-4xl font-bold text-emerald-600 mt-1">{{ $totals['success'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Completed</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs uppercase tracking-widest text-rose-400">Failed</p>
            <p class="text-4xl font-bold text-rose-500 mt-1">{{ $totals['failed'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Errors</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs uppercase tracking-widest text-slate-400">Storage</p>
            <p class="text-4xl font-bold text-slate-800 mt-1">{{ $totals['size_gb'] }}</p>
            <p class="text-xs text-slate-400 mt-1">GB used</p>
        </div>
    </div>

    {{-- ── MANUAL BACKUP ── --}}
    <div class="bg-slate-900 rounded-2xl p-5 flex flex-wrap items-center gap-4 shadow-lg">
        <div class="flex-1 min-w-0">
            <p class="text-white font-semibold">Run a backup now</p>
            <p class="text-slate-400 text-sm mt-0.5">Immediately creates a fresh dump without a schedule.</p>
        </div>
        <form method="POST" action="{{ route('backup-suite.run.manual') }}" class="flex items-center gap-3 shrink-0">
            @csrf
            <select name="mode" class="bg-slate-800 border border-slate-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                <option value="database">Database only</option>
                <option value="files">Files only</option>
                <option value="both">Database + Files</option>
            </select>
            <button class="px-5 py-2 bg-emerald-500 hover:bg-emerald-400 text-white rounded-xl font-semibold text-sm transition shadow">
                ▶ Run now
            </button>
        </form>
    </div>

    {{-- ── SCHEDULES TABLE ── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div>
                <h2 class="font-semibold text-slate-900">Schedules</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ $schedules->count() }} configured</p>
            </div>
            <button onclick="openDrawer('create')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Schedule
            </button>
        </div>

        @if($schedules->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm">No schedules yet.</p>
                <button onclick="openDrawer('create')" class="mt-3 text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                    + Create your first schedule
                </button>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-400 text-xs uppercase tracking-wider border-b border-slate-100">
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-3 py-3 font-medium">Project</th>
                        <th class="px-3 py-3 font-medium">Mode</th>
                        <th class="px-3 py-3 font-medium">Frequency</th>
                        <th class="px-3 py-3 font-medium">Detail</th>
                        <th class="px-3 py-3 font-medium">Time</th>
                        <th class="px-3 py-3 font-medium">Next Run</th>
                        <th class="px-3 py-3 font-medium">Last Run</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($schedules as $schedule)
                    @php $lastRun = $schedule->runs()->orderByDesc('created_at')->first(); @endphp
                    <tr class="hover:bg-slate-50/60 transition {{ $schedule->isExpired() ? 'opacity-50' : '' }}">
                        <td class="px-6 py-3 font-medium text-slate-900">
                            {{ $schedule->name }}
                            @if($schedule->isExpired())
                                <span class="ml-1 text-xs text-rose-400 font-normal">(expired)</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-xs">
                            @if($schedule->project)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-violet-50 text-violet-700 font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                    </svg>
                                    {{ $schedule->project->name }}
                                </span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <span class="px-2 py-0.5 rounded-lg text-xs font-medium
                                {{ $schedule->mode === 'database' ? 'bg-blue-50 text-blue-600' : ($schedule->mode === 'files' ? 'bg-violet-50 text-violet-600' : 'bg-amber-50 text-amber-600') }}">
                                {{ ucfirst($schedule->mode) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 capitalize text-slate-600 text-xs">{{ $schedule->type }}</td>
                        <td class="px-3 py-3 text-slate-500 text-xs">
                            @if($schedule->type === 'weekly')     {{ $dayNames[$schedule->day_of_week] ?? '—' }}
                            @elseif($schedule->type === 'monthly') Day {{ $schedule->day_of_month ?? '—' }}
                            @elseif($schedule->type === 'yearly')  {{ $monthNames[$schedule->month] ?? '—' }} {{ $schedule->day_of_month ?? '—' }}
                            @elseif($schedule->type === 'custom')  <code class="text-xs">{{ $schedule->cron_expression }}</code>
                            @else —
                            @endif
                        </td>
                        <td class="px-3 py-3 text-slate-600 text-xs font-mono">{{ substr($schedule->time, 0, 5) }}</td>
                        <td class="px-3 py-3 text-slate-400 text-xs">
                            {{ $schedule->next_run_at?->format('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-xs">
                            @if($lastRun)
                                @php $lc = ['success'=>'emerald','failed'=>'rose','running'=>'amber','pending'=>'slate'][$lastRun->status] ?? 'slate'; @endphp
                                <span class="px-2 py-0.5 rounded-full bg-{{ $lc }}-100 text-{{ $lc }}-700 capitalize">{{ $lastRun->status }}</span>
                                <div class="text-slate-400 mt-0.5 text-xs">{{ $lastRun->started_at?->diffForHumans() }}</div>
                            @else
                                <span class="text-slate-300">Never</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <form method="POST" action="{{ route('backup-suite.schedules.toggle', $schedule) }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium transition
                                    {{ $schedule->enabled ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $schedule->enabled ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $schedule->enabled ? 'On' : 'Off' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="inline-flex items-center gap-3">
                                <form method="POST" action="{{ route('backup-suite.schedules.run', $schedule) }}" class="inline">
                                    @csrf
                                    <button class="text-slate-400 hover:text-emerald-600 transition" title="Run now">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                </form>
                                <button onclick="openDrawer('edit', {{ $schedule->toJson() }})"
                                    class="text-slate-400 hover:text-blue-600 transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('backup-suite.schedules.destroy', $schedule) }}" class="inline"
                                    onsubmit="return confirm('Delete \'{{ addslashes($schedule->name) }}\'?')">
                                    @csrf @method('DELETE')
                                    <button class="text-slate-400 hover:text-rose-500 transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── RECENT BACKUPS ── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div>
                <h2 class="font-semibold text-slate-900">Recent Backups</h2>
                <p class="text-xs text-slate-400 mt-0.5">Latest 10 runs</p>
            </div>
            <a href="{{ route('backup-suite.history.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-medium transition">View all →</a>
        </div>
        @if($recentRuns->isEmpty())
            <div class="text-center py-14 text-slate-400 text-sm">No backups yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-400 text-xs uppercase tracking-wider border-b border-slate-100">
                        <th class="px-6 py-3 font-medium">Filename</th>
                        <th class="px-3 py-3 font-medium">Schedule</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-3 py-3 font-medium">Size</th>
                        <th class="px-3 py-3 font-medium">Started</th>
                        <th class="px-3 py-3 font-medium">Duration</th>
                        <th class="px-6 py-3 font-medium text-right">Download</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($recentRuns as $run)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-6 py-3 font-mono text-xs text-slate-700 max-w-[160px] truncate" title="{{ $run->filename }}">{{ $run->filename }}</td>
                        <td class="px-3 py-3 text-slate-500 text-xs">{{ $run->schedule?->name ?? 'Manual' }}</td>
                        <td class="px-3 py-3">
                            @php $colors = ['success'=>'emerald','failed'=>'rose','running'=>'amber','pending'=>'slate']; $c = $colors[$run->status] ?? 'slate'; @endphp
                            <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $c }}-100 text-{{ $c }}-700 capitalize">{{ $run->status }}</span>
                        </td>
                        <td class="px-3 py-3 text-slate-500 text-xs">{{ $run->size_bytes ? number_format($run->size_bytes/1048576,2).' MB' : '—' }}</td>
                        <td class="px-3 py-3 text-slate-400 text-xs">{{ $run->started_at?->format('d M H:i') ?? '—' }}</td>
                        <td class="px-3 py-3 text-slate-400 text-xs">
                            @if($run->started_at && $run->finished_at) {{ $run->finished_at->diffForHumans($run->started_at, true) }}
                            @else —
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('backup-suite.history.download', $run) }}"
                                class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-900 font-medium transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     RIGHT-SIDE DRAWER  (Create + Edit combined)
═══════════════════════════════════════════════════════════════ --}}

{{-- Backdrop --}}
<div id="drawer-backdrop"
     onclick="closeDrawer()"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 hidden transition-opacity"></div>

{{-- Drawer panel --}}
<aside id="schedule-drawer"
       class="fixed top-0 right-0 h-screen w-full max-w-md bg-white z-50 shadow-2xl
              translate-x-full transition-transform duration-300 flex flex-col">

    {{-- Drawer header --}}
    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
        <div>
            <h2 id="drawer-title" class="text-lg font-semibold text-slate-900">New Schedule</h2>
            <p id="drawer-desc" class="text-xs text-slate-400 mt-0.5">Configure timing and backup destination.</p>
        </div>
        <button onclick="closeDrawer()"
            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Drawer form --}}
    <div class="flex-1 overflow-y-auto px-6 py-5">
        <form method="POST" id="drawer-form" class="space-y-4">
            @csrf
            <span id="drawer-method-field"></span>{{-- PUT injected by JS for edit --}}

            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Backup Name <span class="text-rose-500">*</span></label>
                <input id="d-name" name="name" required placeholder="e.g. Nightly DB Backup"
                    value="{{ old('name') }}"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
            </div>

            {{-- Project --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Project <span class="text-slate-400 text-xs">(optional — leave blank to use host app's DB)</span></label>
                <select id="d-project_id" name="project_id"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none">
                    <option value="">— Host application DB —</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->db_database }})</option>
                    @endforeach
                </select>
                @if($projects->isEmpty())
                    <p class="text-xs text-slate-400 mt-1">
                        <a href="{{ route('backup-suite.projects.index') }}" class="text-emerald-600 hover:underline">Add a project</a> to back up an external database.
                    </p>
                @endif
            </div>

            {{-- Mode + Frequency --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mode <span class="text-rose-500">*</span></label>
                    <select id="d-mode" name="mode"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none">
                        <option value="database">Database only</option>
                        <option value="files">Files only</option>
                        <option value="both">DB + Files</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Frequency <span class="text-rose-500">*</span></label>
                    <select id="d-type" name="type"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="custom">Custom (cron)</option>
                    </select>
                </div>
            </div>

            {{-- Time + Timezone --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Run Time <span class="text-rose-500">*</span></label>
                    <input id="d-time" name="time" type="time" value="{{ old('time','02:00') }}" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Timezone</label>
                    <input id="d-timezone" name="timezone" value="{{ old('timezone', config('backup-suite.timezone','Asia/Colombo')) }}"
                        placeholder="Asia/Colombo"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                </div>
            </div>

            {{-- Weekly --}}
            <div id="d-weekly" class="hidden">
                <label class="block text-sm font-medium text-slate-700 mb-1">Day of Week</label>
                <select id="d-day_of_week" name="day_of_week"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none">
                    <option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option>
                    <option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="0">Sunday</option>
                </select>
            </div>

            {{-- Monthly --}}
            <div id="d-monthly" class="hidden">
                <label class="block text-sm font-medium text-slate-700 mb-1">Day of Month (1–31)</label>
                <input id="d-day_of_month" name="day_of_month" type="number" min="1" max="31"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
            </div>

            {{-- Yearly --}}
            <div id="d-yearly" class="hidden grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Month</label>
                    <select id="d-month" name="month"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none">
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                            <option value="{{ $i+1 }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Day of Month</label>
                    <input id="d-day_of_month_y" name="day_of_month" type="number" min="1" max="31"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                </div>
            </div>

            {{-- Custom cron --}}
            <div id="d-custom" class="hidden">
                <label class="block text-sm font-medium text-slate-700 mb-1">Cron Expression</label>
                <input id="d-cron" name="cron_expression" placeholder="0 2 * * *"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                <p class="text-xs text-slate-400 mt-1">minute · hour · day · month · weekday</p>
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                    <input id="d-start_date" name="start_date" type="date" value="{{ old('start_date') }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">End Date <span class="text-slate-400">(auto-stop)</span></label>
                    <input id="d-end_date" name="end_date" type="date" value="{{ old('end_date') }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                </div>
            </div>

            {{-- File paths --}}
            <div id="d-filepaths" class="hidden">
                <label class="block text-sm font-medium text-slate-700 mb-1">File / Folder Paths <span class="text-slate-400">(one per line)</span></label>
                <textarea id="d-file_paths" name="file_paths[]" rows="3"
                    placeholder="storage/app&#10;public/uploads"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400 focus:outline-none"></textarea>
            </div>

            {{-- Enabled toggle --}}
            <label class="flex items-center gap-3 cursor-pointer select-none">
                <input id="d-enabled" type="checkbox" name="enabled" value="1"
                    class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                <span class="text-sm font-medium text-slate-700">Enable this schedule immediately</span>
            </label>
        </form>
    </div>

    {{-- Drawer footer --}}
    <div class="shrink-0 px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-white">
        <button onclick="closeDrawer()"
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition">
            Cancel
        </button>
        <button onclick="document.getElementById('drawer-form').submit()"
            class="px-6 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition shadow-sm">
            Save Schedule
        </button>
    </div>
</aside>

{{-- ── JAVASCRIPT ── --}}
<script>
const baseUrl = '{{ url(config("backup-suite.route_prefix","backup-suite")."/schedules") }}';

// ── Field sync ────────────────────────────────────────────────────────────────
function syncDrawerFields() {
    const type = document.getElementById('d-type').value;
    const mode = document.getElementById('d-mode').value;
    document.getElementById('d-weekly').classList.toggle('hidden', type !== 'weekly');
    document.getElementById('d-monthly').classList.toggle('hidden', type !== 'monthly');
    document.getElementById('d-yearly').classList.toggle('hidden', type !== 'yearly');
    document.getElementById('d-custom').classList.toggle('hidden', type !== 'custom');
    document.getElementById('d-filepaths').classList.toggle('hidden', mode === 'database');
}
document.getElementById('d-type').addEventListener('change', syncDrawerFields);
document.getElementById('d-mode').addEventListener('change', syncDrawerFields);

// ── Open drawer ───────────────────────────────────────────────────────────────
function openDrawer(mode, schedule = null) {
    const form        = document.getElementById('drawer-form');
    const methodField = document.getElementById('drawer-method-field');
    const title       = document.getElementById('drawer-title');
    const desc        = document.getElementById('drawer-desc');

    // Reset form
    form.reset();
    methodField.innerHTML = '';
    document.getElementById('d-file_paths').value = '';

    if (mode === 'create') {
        title.textContent = 'New Schedule';
        desc.textContent  = 'Configure timing and backup destination.';
        form.action       = baseUrl;
        document.getElementById('d-time').value = '02:00';
        document.getElementById('d-timezone').value = '{{ config('backup-suite.timezone','Asia/Colombo') }}';
        document.getElementById('d-start_date').value = new Date().toLocaleDateString('en-CA', { timeZone: '{{ config('backup-suite.timezone','Asia/Colombo') }}' });
    } else {
        title.textContent = 'Edit Schedule';
        desc.textContent  = 'Update the schedule settings.';
        form.action       = baseUrl + '/' + schedule.id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('d-name').value          = schedule.name        || '';
        document.getElementById('d-project_id').value    = schedule.project_id  || '';
        document.getElementById('d-mode').value          = schedule.mode        || 'database';
        document.getElementById('d-type').value          = schedule.type        || 'daily';
        document.getElementById('d-time').value       = schedule.time        || '';
        document.getElementById('d-timezone').value   = schedule.timezone    || '';
        document.getElementById('d-start_date').value = schedule.start_date  || '';
        document.getElementById('d-end_date').value   = schedule.end_date    || '';
        document.getElementById('d-enabled').checked  = !!schedule.enabled;
        document.getElementById('d-cron').value       = schedule.cron_expression || '';

        if (schedule.day_of_week != null) document.getElementById('d-day_of_week').value = schedule.day_of_week;
        document.getElementById('d-day_of_month').value   = schedule.day_of_month || '';
        document.getElementById('d-day_of_month_y').value = schedule.day_of_month || '';
        if (schedule.month) document.getElementById('d-month').value = schedule.month;

        const paths = schedule.file_paths;
        document.getElementById('d-file_paths').value = Array.isArray(paths) ? paths.join('\n') : '';
    }

    syncDrawerFields();

    document.getElementById('drawer-backdrop').classList.remove('hidden');
    document.getElementById('schedule-drawer').classList.remove('translate-x-full');
    document.body.style.overflow = 'hidden';
}

// ── Close drawer ──────────────────────────────────────────────────────────────
function closeDrawer() {
    document.getElementById('schedule-drawer').classList.add('translate-x-full');
    document.getElementById('drawer-backdrop').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
</script>
@endsection
