# LOGIN_LOGS - Audit trail đăng nhập

## Vai trò

Ghi lại mọi lần đăng nhập, cả thành công lẫn thất bại. Append-only. Phục vụ detect brute-force, điều tra bảo mật, thống kê đăng nhập.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | bigint | NOT NULL | auto_increment | PK (bigint vì lượng ghi lớn) |
| `user_id` | int | NULL | - | FK → USERS. NULL nếu email chưa có tài khoản |
| `login_identifier` | varchar(150) | NOT NULL | - | Email thử đăng nhập |
| `login_method` | varchar(30) | NOT NULL | - | `password` \| `walk_in_auto_login` |
| `success` | boolean | NOT NULL | - | true = đăng nhập thành công |
| `failure_reason` | varchar(100) | NULL | - | `wrong_password` \| `account_locked` \| `user_not_found` \| `must_change_password` ... |
| `ip_address` | varchar(45) | NULL | - | IPv4 / IPv6 |
| `user_agent` | varchar(500) | NULL | - | User-Agent string |
| `session_id` | int | NULL | - | FK → USER_SESSIONS. Chỉ có giá trị khi login thành công |
| `attempted_at` | timestamp | NOT NULL | - | Thời điểm thử đăng nhập |
| `metadata_json` | json | NULL | - | JSON tuỳ ý: `event_id`, `device_info`, ... |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User đăng nhập (nếu có tài khoản) |
| `session_id` | `USER_SESSIONS.id` | Phiên tạo ra khi login thành công |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, attempted_at)` | Lịch sử đăng nhập của user |
| IDX | `(login_identifier, attempted_at)` | Theo dõi 1 email bị dò |
| IDX | `(ip_address, attempted_at)` | Detect brute-force theo IP |
| IDX | `(success, attempted_at)` | Thống kê thành công/thất bại |
| IDX | `session_id` | Truy phiên từ log |

## Ghi chú nghiệp vụ

- Append-only: không sửa/xóa dòng - đảm bảo tính toàn vẹn audit.
- `login_method = walk_in_auto_login`: ghi khi hệ thống tự tạo phiên cho user vãng lai lúc quét QR sự kiện (auto-login, không cần user nhập mật khẩu).
- `user_id` nullable để log cả lần thử với email không tồn tại (`failure_reason = user_not_found`).
- Detect brute-force: đếm số dòng `success = false` theo `ip_address`/`login_identifier` trong khoảng thời gian.
