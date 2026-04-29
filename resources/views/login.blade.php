<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ICP Analytics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <div class="grid min-h-screen w-full px-4 lg:grid-cols-[minmax(0,1.05fr)_minmax(24rem,0.95fr)] lg:px-6">
        <section class="flex min-h-screen items-start justify-center border-b border-slate-800 py-12 lg:border-b-0 lg:border-r lg:py-24">
            <div class="w-full">
                <div class="mb-8">
                                    <h1 class="mt-3 text-4xl font-semibold tracking-tight text-white">Analytics control panel</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-400">
                        Sign in to browse dealers, inspect raw API payloads, and jump into the ordering portal.
                    </p>
                </div>

                <div class="surface-panel">
                    <div class="surface-panel__header">
                        <div>
                            <h2 class="text-xl font-semibold text-white">Login</h2>
                            <p class="section-subtitle">Use your Admin/Dealer logins to start a session.</p>
                        </div>
                    </div>

                    <div class="surface-panel__body">
                        @if($errors->any())
                            <div class="alert-error mb-5">
                                {{ $errors->first() }}
                            </div>
                        @endif

                            <form id="loginForm" method="POST" class="space-y-4">
                                @csrf

                                <div>
                                    <label for="email" class="label-text">Email</label>
                                    <input type="email" name="email" id="email" class="input-field" required>
                                </div>

                                <div>
                                    <label for="password" class="label-text">Password</label>
                                    <input type="password" name="password" id="password" class="input-field" required>
                                </div>

                                <div class="login-actions">
                                    <button
                                        type="submit"
                                        class="button-primary"
                                        formaction="{{ route('login') }}"
                                    >
                                        Admin Login
                                    </button>

                                    <button
                                        type="submit"
                                        class="button-secondary"
                                        formaction="{{ route('dealer.login') }}"
                                    >
                                        Dealer Login
                                    </button>
                                </div>
                            </form>
                    </div>
                </div>
            </div>
        </section>

        <aside class="min-h-screen bg-slate-900/60" id="apiResponseLogsContainer">
            <div class="log-panel lg:sticky lg:top-0">
                <div class="mb-6">
                    <p class="section-heading">Diagnostics</p>
                    <h2 class="mt-3 text-2xl font-semibold text-white">API Response Logs</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Login requests and payloads appear here for quick inspection.
                    </p>
                </div>

                <div id="apiResponseLogs" class="space-y-4">
                    @if(isset($api_logs) && count($api_logs) > 0)
                        @foreach($api_logs as $log)
                            <div class="log-entry">
                                <div class="log-meta">{{ $log['method'] }} {{ $log['url'] }} (Status: {{ $log['status'] }})</div>
                                <textarea class="log-textarea" rows="18" readonly>{{ json_encode($log['body'], JSON_PRETTY_PRINT) }}</textarea>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state rounded-2xl border border-dashed border-slate-800 bg-slate-950/40">
                            No API responses yet.
                        </div>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</body>
</html>
