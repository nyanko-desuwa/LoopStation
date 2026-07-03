# EMAIL_OTP_CODES - Mã OTP xác thực qua email

## Vai trò

Lưu mã OTP một lần dùng, gửi qua email cho các mục đích: đăng nhập, đặt lại mật khẩu, xác minh email. Chỉ hỗ trợ OTP qua email - không qua SMS. Mã lưu dạng hash, không plaintext.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `email` | varchar(150) | NOT NULL | - | Email nhận OTP (không nhất thiết đã có tài khoản) |
| `user_id` | int | NULL | - | FK → USERS. NULL nếu email chưa gắn tài khoản |
| `code_hash` | varchar(255) | NOT NULL | - | Hash của mã OTP, không lưu plaintext |
| `purpose` | varchar(20) | NOT NULL | - | `login` \| `password_reset` \| `email_verify` |
| `expires_at` | timestamp | NOT NULL | - | Hết hạn (thường 5–10 phút) |
| `consumed_at` | timestamp | NULL | - | Thời điểm dùng. NULL = chưa dùng |
| `attempt_count` | int | NOT NULL | 0 | Số lần nhập sai, khóa khi vượt ngưỡng |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User sở hữu mã (nếu có tài khoản) |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(email, purpose)` | Tra mã theo email + mục đích |
| IDX | `expires_at` | Dọn mã hết hạn |

## Ghi chú nghiệp vụ

- Mã dùng 1 lần: set `consumed_at` khi verify thành công, không cho dùng lại.
- `attempt_count` chống brute-force: vượt ngưỡng (VD 5 lần) thì vô hiệu mã, buộc yêu cầu mã mới.
- `user_id` nullable để hỗ trợ `email_verify` khi đăng ký (email chưa gắn tài khoản chính thức).
- Nên có job dọn mã đã `consumed_at` hoặc `expires_at < NOW()` định kỳ.
