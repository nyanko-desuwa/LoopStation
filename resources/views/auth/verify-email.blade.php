<x-auth-layout title="Xác minh email">
    <p>Kiểm tra hộp thư rồi bấm vào link xác minh là xong.</p>

    <form method="post" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">Gửi lại email xác minh</button>
    </form>
</x-auth-layout>
