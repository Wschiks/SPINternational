<div>
    <header>
        <h2 class="text-sm font-black uppercase tracking-[0.2em] text-red-300">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <div class="mt-6">
        <x-arcade-button
            variant="danger"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >{{ __('Delete Account') }}</x-arcade-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-base font-black uppercase tracking-[0.2em] text-white">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-3 text-sm leading-relaxed text-slate-400">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-arcade-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-arcade-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-2"
                    placeholder="{{ __('Password') }}"
                />

                <x-arcade-error class="mt-2" :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-arcade-button variant="secondary" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-arcade-button>

                <x-arcade-button variant="danger">{{ __('Delete Account') }}</x-arcade-button>
            </div>
        </form>
    </x-modal>
</div>