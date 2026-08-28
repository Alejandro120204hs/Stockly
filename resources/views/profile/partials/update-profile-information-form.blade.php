<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="nombres" :value="__('Nombres')" />
            <x-text-input id="nombres" name="nombres" type="text" class="mt-1 block w-full" :value="old('nombres', $user->nombres)" required autofocus autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('nombres')" />
        </div>

        <div>
            <x-input-label for="apellidos" :value="__('Apellidos')" />
            <x-text-input id="apellidos" name="apellidos" type="text" class="mt-1 block w-full" :value="old('apellidos', $user->apellidos)" required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('apellidos')" />
        </div>

        <div>
            <x-input-label for="correo" :value="__('Correo')" />
            <x-text-input id="correo" name="correo" type="email" class="mt-1 block w-full" :value="old('correo', $user->correo)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('correo')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
