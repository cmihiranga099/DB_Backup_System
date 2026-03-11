@extends('backup-suite::layouts.app')

@section('content')
@php
    $remoteDisk  = config('backup-suite.remote_disk', 'gdrive');
    $retention   = config('backup-suite.retention.per_schedule', 10);
    $localDisk   = config('backup-suite.local_disk', 'local');
    $localFolder = config('backup-suite.local_folder', 'backups');
@endphp

<div class="space-y-5">

    {{-- ── GLOBAL TOGGLE ── --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-1">Automatic Backups</h2>
        <p class="text-sm text-slate-500 mb-5">Enable or disable all scheduled backups globally.</p>
        <form method="POST" action="{{ route('backup-suite.settings.update') }}" class="flex items-center gap-4">
            @csrf
            <input type="hidden" name="enabled" value="0">
            <label class="inline-flex items-center gap-3 cursor-pointer">
                <div class="relative">
                    <input type="checkbox" name="enabled" value="1" @checked($enabled) class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-checked:bg-emerald-500 rounded-full transition-colors peer-focus:outline-none"></div>
                    <div class="absolute top-0.5 left-0.5 bg-white w-5 h-5 rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                </div>
                <span class="text-sm font-medium text-slate-800">
                    {{ $enabled ? 'Automatic backups are ENABLED' : 'Automatic backups are DISABLED' }}
                </span>
            </label>
            <button class="px-4 py-2 bg-slate-900 text-white rounded-xl hover:bg-slate-700 transition text-sm font-medium">Save</button>
        </form>
    </div>

    {{-- ── GOOGLE DRIVE CREDENTIALS ── --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
        <div class="flex items-start justify-between mb-2">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Google Drive</h2>
                <p class="text-sm text-slate-500">Enter your Google API credentials to enable automatic upload after each backup.</p>
            </div>
            {{-- Connection Status Badge --}}
            @if($driveOk)
                <span id="drive-status" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Configured
                </span>
            @else
                <span id="drive-status" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Not configured
                </span>
            @endif
        </div>

        {{-- How to get credentials guide --}}
        <details class="mb-5 group">
            <summary class="text-sm text-blue-600 cursor-pointer hover:text-blue-800 select-none list-none flex items-center gap-1">
                <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                How to get Google API credentials (step-by-step)
            </summary>
            <div class="mt-3 p-4 bg-blue-50 border border-blue-100 rounded-xl text-sm text-blue-900 space-y-2">
                <ol class="list-decimal list-inside space-y-2">
                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="underline font-medium">Google Cloud Console</a> and create a new project.</li>
                    <li>Enable the <strong>Google Drive API</strong> for your project.</li>
                    <li>Go to <strong>APIs & Services → Credentials → Create Credentials → OAuth 2.0 Client ID</strong>.</li>
                    <li>Set Application type to <strong>Web application</strong>. Add <code class="bg-blue-100 px-1 rounded">https://developers.google.com/oauthplayground</code> as Authorized redirect URI.</li>
                    <li>Copy your <strong>Client ID</strong> and <strong>Client Secret</strong>.</li>
                    <li>Go to <a href="https://developers.google.com/oauthplayground" target="_blank" class="underline font-medium">OAuth 2.0 Playground</a>. Click the gear icon → enable "Use your own OAuth credentials" → paste your Client ID and Secret.</li>
                    <li>In Step 1 find <strong>Drive API v3</strong> → select <code>https://www.googleapis.com/auth/drive</code> → click Authorize APIs.</li>
                    <li>In Step 2 click <strong>Exchange authorization code for tokens</strong> → copy the <strong>Refresh Token</strong>.</li>
                    <li>(Optional) Create a folder in your Google Drive and copy its ID from the URL: <code class="bg-blue-100 px-1 rounded">drive.google.com/drive/folders/<strong>FOLDER_ID</strong></code></li>
                </ol>
            </div>
        </details>

        <form method="POST" action="{{ route('backup-suite.settings.drive.update') }}" class="space-y-4">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Client ID <span class="text-rose-500">*</span></label>
                    <input name="client_id" value="{{ $driveCreds['client_id'] }}"
                        placeholder="xxxxx.apps.googleusercontent.com"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Client Secret <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input name="client_secret" id="field-secret" type="password" value="{{ $driveCreds['client_secret'] }}"
                            placeholder="GOCSPX-..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400 focus:outline-none pr-10" />
                        <button type="button" onclick="toggleVisibility('field-secret', this)"
                            class="absolute right-2 top-2 text-slate-400 hover:text-slate-700 text-xs px-1">Show</button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Refresh Token <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input name="refresh_token" id="field-token" type="password" value="{{ $driveCreds['refresh_token'] }}"
                            placeholder="1//0eABCD..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400 focus:outline-none pr-10" />
                        <button type="button" onclick="toggleVisibility('field-token', this)"
                            class="absolute right-2 top-2 text-slate-400 hover:text-slate-700 text-xs px-1">Show</button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Drive Folder ID <span class="text-slate-400">(optional)</span></label>
                    <input name="folder_id" value="{{ $driveCreds['folder_id'] }}"
                        placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400 focus:outline-none" />
                    <p class="text-xs text-slate-400 mt-1">Leave blank to upload to your Drive root folder.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition">
                    Save Credentials
                </button>
                <button type="button" id="test-btn" onclick="testDrive()"
                    class="px-5 py-2 border border-slate-200 rounded-xl text-sm text-slate-700 hover:bg-slate-50 transition">
                    Test Connection
                </button>
                <span id="test-result" class="text-sm hidden"></span>
            </div>
        </form>
    </div>

    {{-- ── CRON SETUP ── --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-1">Server Cron Setup</h2>
        <p class="text-sm text-slate-500 mb-4">Add this line to your server crontab (<code>crontab -e</code>):</p>
        <div class="bg-slate-900 rounded-xl p-4 font-mono text-sm text-emerald-300 overflow-x-auto select-all">
            * * * * * cd /path-to-your-project &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1
        </div>
        <p class="text-xs text-slate-400 mt-2">Replace <code>/path-to-your-project</code> with the full absolute path to your Laravel project root.</p>
        <div class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-800">
            <p class="font-semibold mb-1">Queue worker also required</p>
            <pre class="bg-blue-100 rounded-lg p-2 text-xs font-mono">php artisan queue:work --tries=3</pre>
            <p class="text-xs mt-1 text-blue-600">Or set <code>QUEUE_CONNECTION=sync</code> in <code>.env</code> to run backups synchronously (no worker needed, but blocks the request).</p>
        </div>
    </div>

    {{-- ── RETENTION ── --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-1">Retention Policy</h2>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200 mt-3">
            <svg class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <p class="text-sm text-slate-700">
                Keeping the <strong>latest {{ $retention }} backups</strong> per schedule. Older files are deleted from both local storage and Google Drive automatically.
                Change via <code class="bg-slate-100 px-1 rounded">BACKUP_SUITE_RETENTION</code> in <code>.env</code>.
            </p>
        </div>
    </div>

    {{-- ── STORAGE PATHS ── --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Storage Configuration</h2>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                <p class="text-xs uppercase tracking-wider text-slate-400 mb-2">Local Storage</p>
                <p><span class="text-slate-500">Disk:</span> <code class="bg-slate-100 px-1 rounded">{{ $localDisk }}</code></p>
                <p><span class="text-slate-500">Folder:</span> <code class="bg-slate-100 px-1 rounded">{{ $localFolder }}</code></p>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                <p class="text-xs uppercase tracking-wider text-slate-400 mb-2">Google Drive</p>
                <p><span class="text-slate-500">Disk name:</span> <code class="bg-slate-100 px-1 rounded">{{ $remoteDisk }}</code></p>
                <p><span class="text-slate-500">Folder:</span> <code class="bg-slate-100 px-1 rounded">{{ config('backup-suite.remote_folder', 'backups') }}</code></p>
                <p><span class="text-slate-500">Status:</span>
                    @if($driveOk)
                        <span class="text-emerald-600 font-medium">Credentials saved</span>
                    @else
                        <span class="text-amber-600 font-medium">Not configured</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

</div>

<script>
function toggleVisibility(id, btn) {
    const field = document.getElementById(id);
    const showing = field.type === 'text';
    field.type = showing ? 'password' : 'text';
    btn.textContent = showing ? 'Show' : 'Hide';
}

function testDrive() {
    const btn    = document.getElementById('test-btn');
    const result = document.getElementById('test-result');
    btn.disabled = true;
    btn.textContent = 'Testing…';
    result.className = 'text-sm text-slate-500';
    result.textContent = '';
    result.classList.remove('hidden');

    fetch('{{ route('backup-suite.settings.drive.test') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            result.className = 'text-sm font-medium text-emerald-600';
            result.textContent = '✓ ' + data.message;
        } else {
            result.className = 'text-sm font-medium text-rose-600';
            result.textContent = '✗ ' + data.message;
        }
    })
    .catch(() => {
        result.className = 'text-sm font-medium text-rose-600';
        result.textContent = '✗ Request failed. Check the browser console.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Test Connection';
    });
}
</script>
@endsection
