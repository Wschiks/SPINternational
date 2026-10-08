<div>
    <header>
        <h2 class="text-sm font-black uppercase tracking-[0.2em] text-white">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-arcade-label for="name" :value="__('Name')" />
            <x-arcade-input id="name" name="name" type="text" class="mt-2" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-arcade-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-arcade-label for="email" :value="__('Email')" />
            <x-arcade-input id="email" name="email" type="email" class="mt-2" :value="old('email', $user->email)" required autocomplete="username" />
            <x-arcade-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-xl border border-amber-300/30 bg-amber-300/10 p-3">
                    <p class="text-sm text-amber-100">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="font-semibold underline decoration-amber-300/60 underline-offset-2 transition hover:decoration-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-300">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-semibold text-amber-200">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-arcade-button>{{ __('Save') }}</x-arcade-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-xs font-black uppercase tracking-[0.2em] text-emerald-300"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</div>