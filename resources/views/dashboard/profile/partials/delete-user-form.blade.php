<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('menu_owner.profile.delete_account_title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('menu_owner.profile.delete_account_desc') }}</p>
    </header>

    <button class="ui-btn ui-btn-danger"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('menu_owner.profile.delete_account_title') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('menu_owner.profile.delete_confirm_title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ auth()->user()->password
                    ? __('menu_owner.profile.delete_confirm_desc')
                    : __('menu_owner.profile.delete_confirm_desc_no_password') }}
            </p>

            @if(auth()->user()->password)
            <div class="mt-6">
                <x-ui.field name="password"
                            label="{{ __('menu_owner.profile.password_label') }}"
                            class="sr-only"
                            :error="$errors->userDeletion->first('password')">
                    <x-ui.input name="password" type="password" reveal
                                placeholder="{{ __('menu_owner.profile.password_label') }}" />
                </x-ui.field>
            </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="ui-btn ui-btn-secondary"
                        x-on:click="$dispatch('close')">{{ __('menu_owner.common.cancel') }}</button>
                <button type="submit" class="ui-btn ui-btn-danger">{{ __('menu_owner.profile.delete_account_title') }}</button>
            </div>
        </form>
    </x-modal>
</section>
