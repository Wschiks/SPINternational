<div>
    <header>
        <h2 class="text-sm font-black uppercase tracking-[0.2em] text-white">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-arcade-label for="update_password_current_password" :value="__('Current Password')" />
            <x-arcade-input id="update_password_current_password" name="current_password" type="password" class="mt-2" autocomplete="current-password" />
            <x-arcade-error class="mt-2" :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-arcade-label for="update_password_password" :value="__('New Password')" />
            <x-arcade-input id="update_password_password" name="password" type="password" class="mt-2" autocomplete="new-password" />
            <x-arcade-error class="mt-2" :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-arcade-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-arcade-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2" autocomplete="new-password" />
            <x-arcade-error class="mt-2" :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-arcade-button>{{ __('Save') }}</x-arcade-button>

            @if (session('status') === 'password-updated')
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