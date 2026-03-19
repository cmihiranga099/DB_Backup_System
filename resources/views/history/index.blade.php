@extends('layouts.app')

@section('content')
<div class="space-y-4">

    {{-- ── FILTERS ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
        <form method="GET" action="{{ route('backup-suite.history.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Schedule</label>
                <select name="schedule_id" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none">
                    <option value="">All schedules</option>
                    @foreach($schedules as $s)
                        <option value="{{ $s->id }}" @selected(request('schedule_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none">
                    <option value="">All statuses</option>
                    <option value="success" @selected(request('status')=='success')>Success</option>
                    <option value="failed"  @selected(request('status')=='failed')>Failed</option>
                    <option value="running" @selected(request('status')=='running')>Running</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From date</label>
                <input name="from" type="date" value="{{ request('from') }}"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">To date</label>
                <input name="to" type="date" value="{{ request('to') }}"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:outline-none" />
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-medium hover:bg-slate-700 transition">Filter</button>
            @if(request()->hasAny(['schedule_id','status','from','to']))
                <a href="{{ route('backup-suite.history.index') }}" class="px-4 py-2 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-slate-50">Clear</a>
            @endif
        </form>
    </div>

    {{-- ── TABLE ── --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Backup History</h2>
                <p class="text-sm text-slate-500">{{ $runs->total() }} records found.</p>
            </div>
            <a href="{{ route('backup-suite.schedules.index') }}" class="text-sm text-slate-600 hover:text-slate-900 font-medium">← Back to schedules</a>
        </div>

        @if($runs->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                No backup records found.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-100">
                        <th class="pb-2 font-medium">Started</th>
                        <th class="pb-2 font-medium">Schedule</th>
                        <th class="pb-2 font-medium">Filename</th>
                        <th class="pb-2 font-medium">Status</th>
                        <th class="pb-2 font-medium">Size</th>
                        <th class="pb-2 font-medium">Duration</th>
                        <th class="pb-2 font-medium">Drive</th>
                        <th class="pb-2 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($runs as $run)
                    <tr>
                        <td class="py-3 text-xs text-slate-600">
                            {{ $run->started_at?->format('d M Y') }}<br>
                            <span class="text-slate-400">{{ $run->started_at?->format('H:i:s') }}</span>
                        </td>
                        <td class="py-3 text-slate-700">{{ $run->schedule?->name ?? 'Manual' }}</td>
                        <td class="py-3 max-w-[160px] truncate text-slate-700 font-mono text-xs" title="{{ $run->filename }}">
                            {{ $run->filename }}
                        </td>
                        <td class="py-3">
                            @php $colors = ['success'=>'emerald','failed'=>'rose','running'=>'amber','pending'=>'slate']; $c = $colors[$run->status] ?? 'slate'; @endphp
                            <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $c }}-100 text-{{ $c }}-700 capitalize">{{ $run->status }}</span>
                            @if($run->status === 'failed' && $run->message)
                                <p class="text-xs text-rose-500 mt-1 max-w-[200px] truncate" title="{{ $run->message }}">{{ $run->message }}</p>
                            @endif
                        </td>
                        <td class="py-3 text-slate-500 text-xs">
                            {{ $run->size_bytes ? number_format($run->size_bytes / 1048576, 2) . ' MB' : '—' }}
                        </td>
                        <td class="py-3 text-slate-500 text-xs">
                            @if($run->started_at && $run->finished_at)
                                {{ $run->finished_at->diffForHumans($run->started_at, true) }}
                            @else —
                            @endif
                        </td>
                        <td class="py-3">
                            @if($run->remote_path)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">Uploaded</span>
                            @else
                                <span class="text-slate-400 text-xs">Local only</span>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            @if($run->local_path || $run->remote_path)
                                <a href="{{ route('backup-suite.history.download', $run) }}"
                                    class="text-slate-700 hover:text-slate-900 text-xs font-medium">↓ Download</a>
                            @else
                                <span class="text-slate-300 text-xs">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $runs->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
