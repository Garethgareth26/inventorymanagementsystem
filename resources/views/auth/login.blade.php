{{--
    Login Page
    ──────────
    Source: Stitch export — login_cv_akuna/code.html
    Layout: x-guest-layout (guest.blade.php — centered card with brand header)
--}}
<x-guest-layout title="Sign In">

    {{-- Session status (e.g. "Password reset link sent") --}}
    @if (session('status'))
        <div class="mb-lg p-sm bg-primary-fixed text-on-primary-fixed-variant border border-on-primary-container rounded text-body-sm" role="alert">
            {{ session('status') }}
        </div>
    @endif

    {{-- Validation errors banner --}}
    @if ($errors->any())
        <div class="mb-lg p-sm bg-negative-bg text-negative-rose border border-negative-rose/30 rounded text-body-sm" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Login form --}}
    <form method="POST" action="{{ route('login') }}" class="flex flex-col space-y-lg w-full" id="login-form">
        @csrf

        {{-- Email Address Input --}}
        <div class="flex flex-col space-y-xs">
            <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="email">Email Address</label>
            <div class="relative w-full">
                <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none" style="font-size: 20px;">mail</span>
                <input 
                    id="email" 
                    name="email" 
                    type="email" 
                    value="{{ old('email') }}" 
                    placeholder="name@company.com" 
                    required 
                    autofocus 
                    class="w-full rounded-full border border-border-divider bg-transparent py-sm pl-[40px] pr-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-shadow"
                />
            </div>
        </div>

        {{-- Password Input --}}
        <div class="flex flex-col space-y-xs">
            <div class="flex justify-between items-center pl-sm pr-sm">
                <label class="font-label-sm text-label-sm text-text-secondary" for="password">Password</label>
                @if (Route::has('password.request'))
                    <a class="font-label-sm text-label-sm text-surface-tint hover:text-primary transition-colors" href="{{ route('password.request') }}">Forgot Password?</a>
                @endif
            </div>
            <div class="relative w-full">
                <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none" style="font-size: 20px;">lock</span>
                <input 
                    id="password" 
                    name="password" 
                    type="password" 
                    placeholder="••••••••" 
                    required 
                    autocomplete="current-password"
                    class="w-full rounded-full border border-border-divider bg-transparent py-sm pl-[40px] pr-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-shadow"
                />
            </div>
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center pl-sm">
            <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    name="remember" 
                    class="rounded-full border-border-divider text-primary focus:ring-surface-tint focus:ring-offset-0 focus:ring-1"
                />
                <span class="ms-2 font-label-sm text-label-sm text-text-secondary">Remember me</span>
            </label>
        </div>

        {{-- Actions --}}
        <div class="pt-sm">
            <button class="w-full flex items-center justify-center rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm py-md hover:bg-surface-tint transition-colors active:scale-[0.98] duration-150 cursor-pointer shadow-sm" type="submit" id="login-submit-btn">
                Sign In
            </button>
        </div>
    </form>

    {{-- Footer note --}}
    <div class="mt-lg border-t border-border-divider pt-lg text-center select-none">
        <p class="text-body-sm text-text-secondary leading-relaxed">
            Authorized personnel only.<br>
            Access attempts are monitored.
        </p>
    </div>

</x-guest-layout>
