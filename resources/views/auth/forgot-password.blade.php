<x-auth-layout title="Quên mật khẩu">
    <form method="post" action="{{ route('password.email') }}">
        @csrf

        <x-auth.field
            name="email"
            label="Email"
            type="email"
            placeholder="Email"
        />

        <button type="submit">Gửi link đặt lại</button>
    </form>
</x-auth-layout>
