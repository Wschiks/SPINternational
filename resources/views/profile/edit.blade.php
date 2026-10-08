<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-black uppercase tracking-[0.15em] text-white sm:text-3xl">
            {{ __('Profile') }}
        </h1>
    </x-slot>

    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-arcade-panel>
                    @include('profile.partials.update-profile-information-form')
                </x-arcade-panel>

                <x-arcade-panel>
                    @include('profile.partials.update-password-form')
                </x-arcade-panel>
            </div>

            <x-arcade-panel tone="danger" class="h-fit lg:sticky lg:top-24">
                @include('profile.partials.delete-user-form')
            </x-arcade-panel>
        </div>
    </div>
</x-app-layout>