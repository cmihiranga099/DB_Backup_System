@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Laravel Projects</h2>
            <p class="text-sm text-slate-500 mt-0.5">Register projects to back up their databases on a schedule</p>
        </div>
        <button onclick="openProjectDrawer('create')"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-semibold rounded-xl shadow transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add Project
        </button>
    </div>

    {{-- Projects grid --}}
    @if($projects->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center shadow-sm">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 7a4 4 0 014-4h10a4 4 0 014 4v2a4 4 0 01-4 4H7a4 4 0 01-4-4V7zm0 8a4 4 0 004 4h10a4 4 0 004-4"/>
                </svg>
            </div>
            <p class="text-slate-500 text-sm">No projects yet. Add your first Laravel project to start scheduling backups.</p>
            <button onclick="openProjectDrawer('create')"
                class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-semibold rounded-xl shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Add Project
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($projects as $project)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition overflow-hidden">
                {{-- Card header --}}
                <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-400 to-indigo-500 flex items-center justify-center shrink-0 shadow">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-800 text-sm truncate">{{ $project->name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $project->description ?: 'No description' }}</p>
                        </div>
                    </div>
                    <span class="shrink-0 text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-lg font-medium">
                        {{ $project->schedules_count }} schedule{{ $project->schedules_count != 1 ? 's' : '' }}
                    </span>
                </div>

                {{-- DB info --}}
                <div class="px-5 py-3 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                        <span class="font-mono truncate">{{ $project->db_host }}:{{ $project->db_port }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                        <span class="font-mono truncate">{{ $project->db_database }}</span>
                        <span class="text-slate-300">·</span>
                        <span>{{ $project->db_username }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="px-5 py-3 border-t border-slate-100 flex items-center gap-2">
                    {{-- Run Backup --}}
                    <form method="POST" action="{{ route('backup-suite.projects.backup', $project) }}" class="flex-1">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Backup Now
                        </button>
                    </form>

                    {{-- Test Connection --}}
                    <button onclick="testProjectConnection({{ $project->id }}, this)"
                        data-test-url="{{ route('backup-suite.projects.test', $project) }}"
                        class="flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Test
                    </button>

                    {{-- Edit --}}
                    <button onclick="openProjectDrawer('edit', @json($project))"
                        class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>

                    {{-- Delete --}}
                    <form method="POST" action="{{ route('backup-suite.projects.destroy', $project) }}"
                        onsubmit="return confirm('Delete project \'{{ $project->name }}\' and its schedules?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ── RIGHT DRAWER ── --}}
<div id="projectDrawerOverlay" onclick="closeProjectDrawer()"
    class="fixed inset-0 bg-black/30 z-40 hidden transition-opacity"></div>

<aside id="projectDrawer"
    class="fixed top-0 right-0 h-screen w-[420px] bg-white shadow-2xl z-50 flex flex-col translate-x-full transition-transform duration-300">

    <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
        <h2 id="drawerTitle" class="font-semibold text-slate-800 text-base">Add Project</h2>
        <button onclick="closeProjectDrawer()" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <form id="projectForm" method="POST" class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        {{-- Name --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Project Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" id="field_name" required placeholder="My Laravel App"
                class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent">
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
            <textarea name="description" id="field_description" rows="2" placeholder="Optional notes..."
                class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent resize-none"></textarea>
        </div>

        <hr class="border-slate-200">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Database Connection</p>

        {{-- Host + Port --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Host <span class="text-rose-500">*</span></label>
                <input type="text" name="db_host" id="field_db_host" required placeholder="127.0.0.1"
                    class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Port <span class="text-rose-500">*</span></label>
                <input type="number" name="db_port" id="field_db_port" required value="3306" min="1" max="65535"
                    class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent">
            </div>
        </div>

        {{-- Database --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Database Name <span class="text-rose-500">*</span></label>
            <input type="text" name="db_database" id="field_db_database" required placeholder="my_app_db"
                class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent font-mono">
        </div>

        {{-- Username + Password --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Username <span class="text-rose-500">*</span></label>
                <input type="text" name="db_username" id="field_db_username" required placeholder="root"
                    class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent font-mono">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" name="db_password" id="field_db_password" placeholder="(blank = keep current)"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent font-mono pr-9">
                    <button type="button" onclick="togglePw()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Test connection button (inside drawer) --}}
        <div>
            <button type="button" id="drawerTestBtn" onclick="testDrawerConnection()"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Test Connection
            </button>
            <p id="drawerTestResult" class="text-xs mt-2 text-center hidden"></p>
        </div>
    </form>

    <div class="px-6 py-4 border-t border-slate-200 flex gap-3">
        <button type="button" onclick="closeProjectDrawer()"
            class="flex-1 px-4 py-2.5 text-sm text-slate-600 font-semibold bg-slate-100 hover:bg-slate-200 rounded-xl transition">
            Cancel
        </button>
        <button type="submit" form="projectForm"
            class="flex-1 px-4 py-2.5 text-sm text-white font-semibold bg-emerald-500 hover:bg-emerald-400 rounded-xl shadow transition">
            Save Project
        </button>
    </div>
</aside>

<script>
const baseUrl = '{{ url(config("backup-suite.route_prefix", "backup-suite") . "/projects") }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

let currentProjectId = null;

function openProjectDrawer(mode, project = null) {
    document.getElementById('projectDrawerOverlay').classList.remove('hidden');
    const drawer = document.getElementById('projectDrawer');
    drawer.classList.remove('translate-x-full');

    if (mode === 'create') {
        currentProjectId = null;
        document.getElementById('drawerTitle').textContent = 'Add Project';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('projectForm').action = baseUrl;
        ['name','description','db_host','db_port','db_database','db_username','db_password'].forEach(f => {
            const el = document.getElementById('field_' + f);
            if (el) el.value = f === 'db_host' ? '127.0.0.1' : (f === 'db_port' ? '3306' : '');
        });
    } else {
        currentProjectId = project.id;
        document.getElementById('drawerTitle').textContent = 'Edit Project';
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('projectForm').action = baseUrl + '/' + project.id;
        document.getElementById('field_name').value = project.name || '';
        document.getElementById('field_description').value = project.description || '';
        document.getElementById('field_db_host').value = project.db_host || '127.0.0.1';
        document.getElementById('field_db_port').value = project.db_port || 3306;
        document.getElementById('field_db_database').value = project.db_database || '';
        document.getElementById('field_db_username').value = project.db_username || '';
        document.getElementById('field_db_password').value = '';
    }

    document.getElementById('drawerTestResult').classList.add('hidden');
    document.getElementById('drawerTestBtn').disabled = false;
}

function closeProjectDrawer() {
    document.getElementById('projectDrawer').classList.add('translate-x-full');
    document.getElementById('projectDrawerOverlay').classList.add('hidden');
}

function togglePw() {
    const f = document.getElementById('field_db_password');
    f.type = f.type === 'password' ? 'text' : 'password';
}

async function testDrawerConnection() {
    if (!currentProjectId) {
        showTestResult('drawerTestResult', false, 'Save the project first, then test.');
        return;
    }
    await doTest(currentProjectId, document.getElementById('drawerTestResult'));
}

async function testProjectConnection(id, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    const result = await fetch(baseUrl + '/' + id + '/test', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).then(r => r.json()).catch(() => ({ ok: false, message: 'Request failed' }));

    btn.disabled = false;
    btn.innerHTML = result.ok
        ? '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> OK'
        : '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Fail';
    btn.className = 'flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg transition ' +
        (result.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700');
    btn.title = result.message;
}

async function doTest(id, resultEl) {
    resultEl.textContent = 'Testing...';
    resultEl.className = 'text-xs mt-2 text-center text-slate-500';
    resultEl.classList.remove('hidden');

    const result = await fetch(baseUrl + '/' + id + '/test', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).then(r => r.json()).catch(() => ({ ok: false, message: 'Request failed' }));

    resultEl.textContent = result.message;
    resultEl.className = 'text-xs mt-2 text-center ' + (result.ok ? 'text-emerald-600' : 'text-rose-600');
}
</script>
@endsection
