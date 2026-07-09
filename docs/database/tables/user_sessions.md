# USER_SESSIONS - Phiên đăng nhập theo thiết bị

## Vai trò

Mỗi dòng là 1 phiên đăng nhập trên 1 thiết bị. Lưu hash của refresh token (access token là JWT stateless, không lưu). Hỗ trợ đăng nhập đa thiết bị, revoke từng phiên hoặc tất cả, detect token reuse.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS - chủ session |
| `refresh_token_hash` | varchar(255) | NOT NULL | - | Hash của refresh token, KHÔNG lưu plaintext |
| `refresh_token_jti` | char(36) | NOT NULL, UNIQUE | - | Token ID để detect rotation/reuse |
| `device_type` | varchar(20) | NOT NULL | `'unknown'` | `web` \| `mobile` \| `tablet` \| `desktop` \| `unknown` |
| `device_name` | varchar(150) | NULL | - | Tên thiết bị thân thiện (VD: iPhone 15) |
| `device_os` | varchar(80) | NULL | - | Hệ điều hành |
| `ip_address` | varchar(45) | NULL | - | IPv4 / IPv6 |
| `user_agent` | varchar(500) | NULL | - | User-Agent string |
| `issued_at` | timestamp | NOT NULL | - | Thời điểm phát hành refresh token |
| `refresh_token_expires_at` | timestamp | NOT NULL | - | Hết hạn refresh token (~60 ngày) |
| `last_activity_at` | timestamp | NOT NULL | - | Lần cuối session hoạt động (login/refresh) |
| `revoked_at` | timestamp | NULL | - | NULL = đang hoạt động. Có giá trị = đã revoke |
| `revoked_by_user_id` | int | NULL | - | FK → USERS - manager/user revoke session |
| `revoke_reason` | varchar(50) | NULL | - | `logout` \| `logout_all` \| `password_change` \| `manager_force_logout` \| `suspicious` \| `token_reuse` |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | Chủ phiên |
| `revoked_by_user_id` | `USERS.id` | Người revoke phiên |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `LOGIN_LOGS` | `session_id` | Log đăng nhập gắn với phiên |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, revoked_at, refresh_token_expires_at)` | Lấy phiên đang active của user |
| IDX | `last_activity_at` | Dọn phiên cũ / hiển thị hoạt động |
| IDX | `ip_address` | Điều tra theo IP |
| IDX | `revoked_by_user_id` | Audit ai revoke |

## Ghi chú nghiệp vụ

- Session active khi `revoked_at IS NULL AND refresh_token_expires_at > NOW()`.
- Refresh token rotation: mỗi lần refresh cấp jti mới; nếu jti cũ được dùng lại → nghi ngờ reuse, revoke với `revoke_reason = token_reuse`.
- Đổi mật khẩu: revoke toàn bộ phiên với `revoke_reason = password_change`.
- Chỉ lưu hash refresh token - lộ DB cũng không tái tạo được token gốc.
