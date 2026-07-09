<x-auth-layout title="Đặt lại mật khẩu">
    <form method="post" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-auth.field
            name="email"
            label="Email"
            type="email"
            :value="$email"
            placeholder="Email"
        />

        <x-auth.field
            name="password"
            label="Mật khẩu mới"
            type="password"
            placeholder="Mật khẩu mới"
        />

        <x-auth.field
            name="password_confirmation"
            label="Nhập lại mật khẩu mới"
            type="password"
            value=""
            placeholder="Nhập lại mật khẩu mới"
        />

        <button type="submit">Cập nhật mật khẩu</button>
    </form>
</x-auth-layout>
