<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid px-4">
        <div class="row">
            <!-- Left Side: Login Form -->
            <div class="col-md-6 border-end" style="min-height: 100vh;">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8 col-lg-6">
                        <div class="card">
                            <div class="card-header text-center">Admin Login</div>
                            <div class="card-body">
                                @if($errors->any())
                                    <div class="alert alert-danger">
                                        {{ $errors->first() }}
                                    </div>
                                @endif
                                <form action="{{ route('login') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" name="email" id="email" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" name="password" id="password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: API Logs -->
            <div class="col-md-6 bg-white no-print" id="apiResponseLogsContainer" style="min-height: 100vh;">
                <div class="p-3">
                    <h5 class="text-muted border-bottom pb-2">API Response Logs</h5>
                    <div id="apiResponseLogs">
                        @if(isset($api_logs) && count($api_logs) > 0)
                            @foreach($api_logs as $log)
                                <div class="mb-3">
                                    <div class="small text-muted mb-1">{{ $log['method'] }} {{ $log['url'] }} (Status: {{ $log['status'] }})</div>
                                    <textarea class="form-control bg-dark text-success font-monospace" rows="40" readonly>{{ json_encode($log['body'], JSON_PRETTY_PRINT) }}</textarea>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
