<x-auth-layout title="Đăng nhập">
    <form method="post" action="{{ route('login.store') }}">
        @csrf

        <x-auth.field
            name="login"
            label="Email hoặc số điện thoại"
            placeholder="Email hoặc số điện thoại"
        />

        <x-auth.field
            name="password"
            label="Mật khẩu"
            type="password"
            placeholder="Mật khẩu"
        />

        <label>
            <input type="checkbox" name="remember" value="1">
            Ghi nhớ đăng nhập
        </label>

        <button type="submit">Đăng nhập</button>
    </form>
</x-auth-layout>
