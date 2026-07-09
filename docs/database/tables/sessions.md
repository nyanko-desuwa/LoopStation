# SESSIONS - Web session (Laravel)

## Vai trò

Bảng chuẩn Laravel khi dùng **session driver = database**. Lưu toàn bộ trạng thái session của HTTP request: CSRF token, flash messages, dữ liệu form cũ, và user đang đăng nhập (với web guard). Laravel tự ghi/đọc bảng này qua `SessionManager`.

Khác với [USER_SESSIONS](user_sessions.md): bảng đó lưu **refresh token JWT** cho luồng API đa thiết bị (mobile/SPA). Bảng `SESSIONS` phục vụ **web guard** dùng cookie session truyền thống. Hai bảng song song, phục vụ 2 tầng khác nhau.

| Bảng | Dùng cho | Guard | Cơ chế |
| --- | --- | --- | --- |
| `sessions` | Web (server-side state) | `web` | Session cookie |
| `user_sessions` | API (stateless JWT) | `api` / Sanctum | Refresh token |

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | varchar(255) | NOT NULL | - | PK. Session ID ngẫu nhiên do Laravel sinh |
| `user_id` | bigint | NULL | - | FK → USERS. NULL với khách chưa đăng nhập |
| `ip_address` | varchar(45) | NULL | - | IPv4 / IPv6 của request |
| `user_agent` | text | NULL | - | User-Agent string |
| `payload` | longtext | NOT NULL | - | Dữ liệu session đã serialize (base64 + PHP serialize). Laravel quản lý toàn bộ |
| `last_activity` | int | NOT NULL | - | Unix timestamp lần hoạt động cuối — dùng để dọn session hết hạn |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User đang đăng nhập (web guard) |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | Tra session theo ID |
| IDX | `user_id` | Tìm session của 1 user |
| IDX | `last_activity` | Dọn session hết hạn |

## Ghi chú nghiệp vụ

- **Garbage collection**: Laravel tự chạy GC để xóa session có `last_activity < (now - lifetime)`. Tần suất GC cấu hình qua `config('session.gc_probability')` và `gc_divisor`.
- **Không sửa trực tiếp**: cột `payload` do Laravel serialize/unserialize — không đọc/ghi thẳng bằng raw SQL.
- **Timeout**: `config('session.lifetime')` (phút) xác định session còn hiệu lực bao lâu sau `last_activity`.
- **Kết hợp với USER_SESSIONS**: nếu dự án dùng cả web guard (blade) lẫn API (mobile/SPA), `sessions` và `user_sessions` tồn tại song song, không xung đột. Web request dùng `sessions`; API call dùng JWT và `user_sessions`.
