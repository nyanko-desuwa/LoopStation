<x-auth-layout :title="__('pages.forgot_password')">
    <form method="post" action="{{ route('password.email') }}">
        @csrf

        <x-auth.field name="email" :label="__('auth.labels.email')" type="email" :placeholder="__('auth.labels.email')" />

        <button type="submit">{{ __('auth.buttons.send_reset_link') }}</button>
    </form>
</x-auth-layout>
