<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('menu_owner.profile.title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('menu_owner.profile.subtitle') }}</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <x-ui.field name="name"
                    label="{{ __('menu_owner.profile.name_label') }}"
                    help="{{ __('menu_owner.profile.name_readonly') }}">
            <x-ui.input name="name" type="text" :value="old('name', $user->name)" state="disabled" disabled readonly />
        </x-ui.field>

        <x-ui.field name="email"
                    label="{{ __('menu_owner.profile.email_label') }}"
                    help="{{ __('menu_owner.profile.email_readonly') }}">
            <x-ui.input name="email" type="email" :value="old('email', $user->email)" state="disabled" disabled readonly />
        </x-ui.field>
    </form>
</section>
