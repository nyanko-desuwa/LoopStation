<x-auth-layout title="Đăng ký">
    <form method="post" action="{{ route('register.store') }}">
        @csrf

        <x-auth.field
            name="name"
            label="Họ và tên"
            placeholder="Họ và tên"
        />

        <x-auth.field
            name="phone"
            label="Số điện thoại"
            placeholder="Số điện thoại"
        />

        <x-auth.field
            name="email"
            label="Email"
            type="email"
            placeholder="Email"
        />

        <x-auth.field
            name="password"
            label="Mật khẩu"
            type="password"
            placeholder="Mật khẩu"
        />

        <x-auth.field
            name="password_confirmation"
            label="Nhập lại mật khẩu"
            type="password"
            placeholder="Nhập lại mật khẩu"
            value=""
        />

        <button type="submit">Đăng ký</button>
    </form>
</x-auth-layout>
