<x-auth-layout :title="__('auth.pages.login')">
    <form method="post" action="{{ route('login.store') }}">
        @csrf

        <x-auth.field name="login" :label="__('auth.labels.login')" :placeholder="__('auth.labels.login')" />

        <x-auth.field name="password" :label="__('auth.labels.password')" type="password" :placeholder="__('auth.labels.password')" />

        <label>
            <input type="checkbox" name="remember" value="1">
            {{ __('auth.labels.remember') }}
        </label>

        <button type="submit">{{ __('auth.buttons.login') }}</button>
    </form>
</x-auth-layout>
