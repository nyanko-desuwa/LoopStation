# PASSWORD_RESET_TOKENS - Token reset mật khẩu (Laravel)

## Vai trò

Bảng chuẩn Laravel cho **Password Broker** — là **kênh đặt lại mật khẩu duy nhất** của hệ thống. Khi user quên mật khẩu, hệ thống gửi 1 **link** chứa token qua email; user bấm link để mở form đặt mật khẩu mới. Hệ thống **không dùng OTP** cho bất kỳ luồng auth nào (đã bỏ bảng `EMAIL_OTP_CODES`).

Mỗi email chỉ giữ 1 token mới nhất (email là PK). Token lưu ở dạng hash, không plaintext.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `email` | varchar(150) | NOT NULL | - | PK. Email yêu cầu reset. Mỗi email chỉ giữ token mới nhất |
| `token` | varchar(255) | NOT NULL | - | Hash token reset (không lưu plaintext). Bản gốc chỉ gửi qua email 1 lần |
| `created_at` | timestamp | NULL | - | Thời điểm phát hành, dùng tính hết hạn theo `config('auth.passwords.users.expire')` |

## Quan hệ khóa ngoại

Không có FK — Laravel dùng `email` làm khóa tự nhiên, không link cứng với `USERS.id`. Cho phép khởi tạo reset ngay cả khi user đã bị xóa mềm mà email vẫn hợp lệ (Password Broker sẽ tự kiểm tra user tồn tại ở bước cuối).

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `email` | Tra token theo email |

## Luồng đặt lại mật khẩu bằng link (chi tiết)

### Bước 1 - User yêu cầu reset

1. User bấm "Quên mật khẩu", nhập `email` (VD `an@gmail.com`)
2. Backend gọi `Password::sendResetLink(['email' => ...])`
3. Laravel sinh 1 token ngẫu nhiên dài (VD `a3f9c8b2...`, 64 ký tự)
4. **Upsert** vào `password_reset_tokens` (ghi đè token cũ của email này nếu có):

   ```
   email:       an@gmail.com
   token:       $2y$10$... (hash của a3f9c8b2...)
   created_at:  2026-07-09 14:00:00
   ```

5. Gửi email chứa link:
   `https://loopstation.com/reset-password/a3f9c8b2...?email=an@gmail.com`
6. Ghi `SYSTEM_LOGS` (entity_type = user, action = request_password_reset) nếu cần audit

### Bước 2 - User đặt mật khẩu mới

7. User bấm link trong email → mở trang đặt mật khẩu mới
8. User nhập mật khẩu mới + xác nhận → submit (token + email đi kèm trong request)
9. Backend gọi `Password::reset(...)`:
   - Tra `password_reset_tokens` theo `email`
   - So `hash(token gửi lên)` với `token` trong DB → phải khớp
   - Kiểm tra `created_at` chưa quá hạn (mặc định 60 phút)
10. Nếu hợp lệ:
    - Cập nhật `USERS.password = Hash::make(mật khẩu mới)`
    - Đặt `USERS.must_change_password = 0` (nếu đang là mật khẩu tạm)
    - **Xóa dòng token** khỏi `password_reset_tokens` (dùng 1 lần)
    - Revoke toàn bộ `USER_SESSIONS` của user với `revoke_reason = password_change` (buộc đăng nhập lại mọi thiết bị)
    - Ghi `LOGIN_LOGS` / `SYSTEM_LOGS` tùy chính sách audit

### Trường hợp lỗi

- Token sai / email không khớp → `INVALID_TOKEN`, từ chối
- Token quá hạn (`created_at` cũ hơn `expire`) → `INVALID_TOKEN`, yêu cầu gửi lại
- Email không tồn tại trong `USERS` → `INVALID_USER` (Laravel kiểm tra ở bước reset)

## Ghi chú nghiệp vụ

- **Upsert theo email**: mỗi lần yêu cầu reset ghi đè token cũ, chỉ token gần nhất còn hiệu lực. Không tích lũy nhiều token cho 1 email.
- **Dùng 1 lần**: reset thành công thì xóa dòng ngay.
- **Hết hạn**: Laravel tự loại token quá `config('auth.passwords.users.expire')` (mặc định 60 phút). Có thể chạy `php artisan auth:clear-resets` định kỳ để dọn token rác, hoặc để Password Broker tự bỏ qua khi verify.
- **Không có FK, không cột purpose**: bảng chỉ phục vụ đúng 1 việc là reset mật khẩu. Các luồng auth khác (đăng nhập, xác minh email) không đi qua bảng này:
  - **Đăng nhập**: chỉ bằng mật khẩu (`login_method = password`) hoặc walk-in auto-login (`login_method = walk_in_auto_login`).
  - **Xác minh email**: dùng cơ chế `MustVerifyEmail` chuẩn Laravel (gửi link xác minh), set `USERS.email_verified_at` khi user bấm link — không dùng OTP.
- **Bảo mật**: chỉ gửi token gốc qua email 1 lần. Lộ DB chỉ lộ hash, không tái tạo được link.
