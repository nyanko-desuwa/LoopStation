<x-auth-layout :title="__('auth.pages.verify_email')">
    <p>{{ __('auth.descriptions.verify_email') }}</p>

    <form method="post" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">{{ __('auth.buttons.resend_verification_email') }}</button>
    </form>
</x-auth-layout>
