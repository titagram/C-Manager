<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - C-Manager Demo</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-xl bg-primary mx-auto flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-primary-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64" />
                </svg>
            </div>
            <h1 class="text-2xl font-semibold">C-Manager Demo</h1>
            <p class="text-muted-foreground mt-1">Accedi al gestionale</p>
        </div>

        <!-- Login Form -->
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/login" class="space-y-4">
                    @csrf

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input @error('email') border-destructive @enderror"
                            placeholder="nome@esempio.it"
                            required
                            autofocus
                        >
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input @error('password') border-destructive @enderror"
                            placeholder="********"
                            required
                        >
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="rounded border-input text-primary focus:ring-primary"
                        >
                        <label for="remember" class="text-sm">Ricordami</label>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        Accedi
                    </button>
                </form>
            </div>
        </div>

        <!-- Demo Credentials -->
        <div class="mt-6 text-center text-sm text-muted-foreground">
            <p>Credenziali demo:</p>
            <p class="mt-1">
                <strong>Admin:</strong> admin@demo.test / password<br>
                <strong>Operatore:</strong> operatore@demo.test / password
            </p>
        </div>
    </div>
</body>
</html>
