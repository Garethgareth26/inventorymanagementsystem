{{--
    Login Page
    ──────────
    Source: Stitch export — login_cv_akuna/code.html
    Layout: x-guest-layout (guest.blade.php — centered card, no sidebar)
--}}
<x-guest-layout title="Sign In">

    {{-- Centered login card — max-w-md, white bg, border, rounded --}}
    <main class="w-full max-w-md bg-surface-container-lowest border border-border-subtle rounded-lg p-xl"
          id="login-card">

        {{-- Brand header --}}
        <div class="text-center mb-xl flex flex-col items-center">
            <div class="w-16 h-16 bg-primary rounded-lg flex items-center justify-center mb-md" aria-hidden="true">
                <span class="material-symbols-outlined text-on-primary icon-fill" style="font-size: 32px;">inventory_2</span>
            </div>
            <h1 class="text-headline-lg font-bold text-text-main">CV Akuna</h1>
            <p class="text-body-md text-text-muted mt-sm">Inventory Management System</p>
        </div>

        {{-- Session status (e.g. "Password reset link sent") --}}
        @if (session('status'))
            <div class="mb-lg p-sm bg-success/10 border border-success/30 rounded text-body-sm text-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        {{-- Validation errors banner --}}
        @if ($errors->any())
            <div class="mb-lg p-sm bg-error-container border border-error/30 rounded text-body-sm text-on-error-container" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Login form --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-lg" id="login-form">
            @csrf

            {{-- Email field --}}
            <div class="space-y-sm">
                <label for="email"
                       class="block text-label-caps text-text-muted uppercase">
                    Email or Username
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                        <span class="material-symbols-outlined text-text-muted text-[18px]">person</span>
                    </div>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        autofocus
                        required
                        value="{{ old('email') }}"
                        placeholder="admin@cvakuna.com"
                        class="block w-full pl-10 pr-3 py-2 bg-surface-container-lowest border border-border-subtle rounded
                               text-body-md text-text-main placeholder-text-muted input-glow transition-all duration-200
                               @error('email') border-error @enderror"
                    >
                </div>
                @error('email')
                    <p class="text-body-sm text-error mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password field --}}
            <div class="space-y-sm">
                <div class="flex items-center justify-between">
                    <label for="password"
                           class="block text-label-caps text-text-muted uppercase">
                        Password
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-body-sm text-primary hover:underline transition-colors">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                        <span class="material-symbols-outlined text-text-muted text-[18px]">lock</span>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="••••••••"
                        class="block w-full pl-10 pr-3 py-2 bg-surface-container-lowest border border-border-subtle rounded
                               text-body-md text-text-main placeholder-text-muted input-glow transition-all duration-200
                               @error('password') border-error @enderror"
                    >
                </div>
                @error('password')
                    <p class="text-body-sm text-error mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="pt-sm">
                <button
                    type="submit"
                    id="login-submit-btn"
                    class="btn-primary w-full justify-center py-2.5"
                >
                    Sign In
                </button>
            </div>
        </form>

        {{-- Footer note --}}
        <div class="mt-lg border-t border-border-subtle pt-lg text-center">
            <p class="text-body-sm text-text-muted">
                Authorized personnel only.<br>
                Access attempts are logged.
            </p>
        </div>

    </main>

</x-guest-layout>
