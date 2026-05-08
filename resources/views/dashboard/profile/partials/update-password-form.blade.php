@php $hasPassword = ! is_null(auth()->user()->password); @endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ $hasPassword ? __('menu_owner.profile.update_password_title') : __('menu_owner.profile.set_password_title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ $hasPassword
                ? __('menu_owner.profile.update_password_desc')
                : __('menu_owner.profile.set_password_desc') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        @if ($hasPassword)
            <x-ui.field name="current_password"
                        label="{{ __('menu_owner.profile.current_password_label') }}"
                        :error="$errors->updatePassword->first('current_password')">
                <x-ui.input name="current_password" type="password" reveal
                            placeholder="{{ __('menu_owner.profile.current_password_placeholder') }}"
                            autocomplete="current-password" required />
            </x-ui.field>
        @endif

        <x-ui.field name="password"
                    label="{{ __('menu_owner.profile.new_password_label') }}"
                    :error="$errors->updatePassword->first('password')">
            <x-ui.input name="password" type="password" reveal
                        placeholder="{{ __('menu_owner.profile.new_password_placeholder') }}"
                        autocomplete="new-password" required />
        </x-ui.field>

        <x-ui.field name="password_confirmation"
                    label="{{ __('menu_owner.profile.confirm_password_label') }}"
                    :error="$errors->updatePassword->first('password_confirmation')">
            <x-ui.input name="password_confirmation" type="password" reveal
                        placeholder="{{ __('menu_owner.profile.confirm_password_placeholder') }}"
                        autocomplete="new-password" required />
        </x-ui.field>

        <div class="flex items-center gap-4">
            <button type="submit" class="ui-btn ui-btn-primary">{{ __('menu_owner.common.save') }}</button>
            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600">{{ __('menu_owner.profile.saved') }}</p>
            @endif
        </div>
    </form>
</section>
