<x-auth-layout :title="__('pages.register')">
    <form method="post" action="{{ route('register.store') }}">
        @csrf

        <x-auth.field name="name" :label="__('auth.labels.name')" :placeholder="__('auth.labels.name')" />

        <x-auth.field name="phone" :label="__('auth.labels.phone')" :placeholder="__('auth.labels.phone')" />

        <x-auth.field name="email" :label="__('auth.labels.email')" type="email" :placeholder="__('auth.labels.email')" />

        <x-auth.field name="password" :label="__('auth.labels.password')" type="password" :placeholder="__('auth.labels.password')" />

        <x-auth.field name="password_confirmation" :label="__('auth.labels.password_confirmation')" type="password" :placeholder="__('auth.labels.password_confirmation')" value="" />

        <button type="submit">{{ __('auth.buttons.register') }}</button>
    </form>
</x-auth-layout>
