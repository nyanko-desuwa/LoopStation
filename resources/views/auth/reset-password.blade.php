<x-auth-layout :title="__('pages.reset_password')">
    <form method="post" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-auth.field name="email" :label="__('auth.labels.email')" type="email" :value="$email" :placeholder="__('auth.labels.email')" />

        <x-auth.field name="password" :label="__('auth.labels.new_password')" type="password" :placeholder="__('auth.labels.new_password')" />

        <x-auth.field name="password_confirmation" :label="__('auth.labels.new_password_confirmation')" type="password" value="" :placeholder="__('auth.labels.new_password_confirmation')" />

        <button type="submit">{{ __('auth.buttons.update_password') }}</button>
    </form>
</x-auth-layout>
