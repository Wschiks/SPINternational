<x-guest-layout dark>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h1 class="text-center text-lg font-black uppercase tracking-[0.2em] text-white">{{ __('Inloggen') }}</h1>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-arcade-label for="email" :value="__('Email')" />
            <x-arcade-input id="email" class="mt-2" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-arcade-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-arcade-label for="password" :value="__('Password')" />
            <x-arcade-input id="password" class="mt-2" type="password" name="password" required autocomplete="current-password" />
            <x-arcade-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-white/20 bg-[#0d0d24] text-fuchsia-500 focus:ring-fuchsia-400 focus:ring-offset-0" name="remember">
                <span class="ms-2 text-sm text-slate-300">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-cyan-300 underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-cyan-300 rounded-md" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="arcade-btn w-full rounded-xl border-fuchsia-300 bg-fuchsia-600 px-7 py-3.5 text-sm font-black uppercase tracking-[0.15em] text-white hover:bg-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">
            {{ __('Log in') }}
        </button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-400">
                {{ __('Nog geen account?') }}
                <a href="{{ route('register') }}" class="font-bold text-cyan-300 underline-offset-4 hover:underline">{{ __('Account aanmaken') }}</a>
            </p>
        @endif
    </form>
</x-guest-layout>
