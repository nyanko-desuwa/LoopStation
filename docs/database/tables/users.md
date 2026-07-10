# USERS - Tài khoản người dùng

## Vai trò

Bảng trung tâm lưu tất cả tài khoản trong hệ thống: khách hàng thông thường (`user`), nhân viên công ty (`staff`), và chủ cơ sở (`manager`). Ba role dùng chung 1 bảng, phân biệt qua cột `role`. Ngoài ra bảng còn bao gồm tài khoản vãng lai - được tạo tự động khi khách quét QR tại sự kiện mà chưa có tài khoản.

Bảng tương thích đầy đủ với **Laravel Auth**: dùng đúng tên cột `password`, `email_verified_at`, `remember_token` để các guard, middleware và helper Auth mặc định hoạt động không cần cấu hình thêm.

Hệ thống đã bỏ OTP email; reset mật khẩu dùng link qua `PASSWORD_RESET_TOKENS`, còn xác minh email dùng luồng link xác minh chuẩn Laravel (set `email_verified_at` khi user bấm link).

**Cùng mailbox (same-mailbox):** Hệ thống phát hiện "hai email thực ra là cùng 1 mailbox" theo rule riêng của từng email provider. `email_canonical` lưu bản chuẩn hóa: bỏ dấu chấm chỉ với Gmail, bỏ `+tag` với các provider hỗ trợ (Outlook, iCloud...), giữ nguyên với Yahoo và domain khác. Ví dụ: `test.1@gmail.com` và `test1@gmail.com` đều canonical về `test1@gmail.com` → bị chặn trùng tài khoản, nhưng `test.1@yahoo.com` và `test1@yahoo.com` được coi là khác mailbox.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | bigint | NOT NULL | auto_increment | PK |
| `name` | varchar(150) | NOT NULL | - | Họ tên hiển thị |
| `phone` | varchar(20) | NULL | - | Số điện thoại. UQ. Dùng để đăng ký nhanh cho user vãng lai |
| `email` | varchar(150) | NULL | - | Email đăng nhập (bản thô như user nhập, đã trim). Kênh xác thực chính. Có index thường để tra cứu |
| `email_canonical` | varchar(150) | NULL | - | Bản chuẩn hóa của `email` theo rule từng provider (bỏ dấu chấm Gmail, bỏ `+tag`...). UQ - dùng để phát hiện trùng mailbox và tra cứu khi login. Tự sinh khi lưu |
| `locale` | varchar(10) | NOT NULL | `'vi'` | Locale UI mặc định của user. Dùng cho dropdown `vi` / `en` và i18n cá nhân hóa |
| `email_verified_at` | timestamp | NULL | - | Chuẩn Laravel. Thời điểm email xác minh qua link xác minh (`MustVerifyEmail`). NULL = chưa xác minh |
| `password` | varchar(255) | NULL | - | Hash mật khẩu (chuẩn Laravel). NULL với tài khoản walk-in trước khi đặt mật khẩu |
| `remember_token` | varchar(100) | NULL | - | Chuẩn Laravel "remember me" cho web guard |
| `avatar_url` | varchar(500) | NULL | - | Ảnh đại diện. Upload lên server, lưu path tương đối (vd: `avatars/abc.jpg`) |
| `must_change_password` | boolean | NOT NULL | false | `true` = đang dùng mật khẩu tạm, buộc đổi ở lần đăng nhập kế tiếp |
| `role` | enum(user,staff,manager) | NOT NULL | `'user'` | `user` \| `staff` \| `manager` |
| `facility_id` | bigint | NULL | - | FK → FACILITIES. Bắt buộc với `staff` và `manager`. NULL với `user` |
| `is_walk_in` | boolean | NOT NULL | false | `true` = tài khoản tạo tự động từ QR sự kiện |
| `status` | enum(active,locked) | NOT NULL | `'active'` | `active` \| `locked` |
| `deleted_at` | timestamp | NULL | - | Soft delete. NULL = còn hoạt động |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | Thời điểm tạo tài khoản |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | Lần cập nhật cuối |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `facility_id` | `FACILITIES.id` | Staff/manager thuộc về 1 cơ sở |

**Được tham chiếu bởi:**
- `HANDOVER_REQUESTS.user_id`, `staff_id` - user tạo đơn, staff xử lý đơn
- `EVENT_STAFF_ASSIGNMENTS.staff_id` - staff phân công tại sự kiện
- `USER_WALLETS.user_id` - ví điểm của user (1-1)
- `USER_SESSIONS.user_id` - phiên đăng nhập API/JWT
- `SESSIONS.user_id` - phiên web Laravel
- `LOGIN_LOGS.user_id` - lịch sử đăng nhập
- `EDUCATIONAL_CONTENTS.author_id`, `approved_by_id` - staff soạn bài, manager duyệt
- `STICKER_REDEMPTIONS.staff_id` - staff xác nhận đổi sticker vật lý
- `SYSTEM_LOGS.performed_by_user_id` - ai thực hiện thao tác

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UQ | `email_canonical` | Khóa duy nhất để xác định cùng mailbox |
| UQ | `phone` | Đăng ký nhanh walk-in |
| IDX | `email` | Tra cứu email thô / gửi thông báo |
| IDX | `role` | Lọc theo role |
| IDX | `facility_id` | Lọc staff/manager theo cơ sở |
| IDX | `deleted_at` | Lọc tài khoản chưa xóa |

## Ghi chú nghiệp vụ

- **Walk-in user**: khi khách quét QR sự kiện lần đầu mà không có tài khoản, hệ thống tự tạo tài khoản với `is_walk_in = true`. Mật khẩu tạm được sinh và hash, gửi qua email. Ngay lập tức hệ thống tạo `USER_SESSIONS` + ghi `LOGIN_LOGS` (login_method = `walk_in_auto_login`) - user vào app dùng được ngay, không cần mở email. `must_change_password = true` để nhắc đổi mật khẩu về sau.
- **Role không thay đổi thường xuyên**: role gắn với loại tài khoản, không phải quyền cụ thể. Quyền chi tiết đến từ `ROLE_PERMISSIONS`.
- `manager` chỉ quản lý sự kiện và đơn thuộc **cơ sở của mình** - xác định qua `USERS.facility_id`.
- **email_verified_at**: Laravel dùng cột này cho middleware `verified` (`MustVerifyEmail`). Hệ thống xác minh email bằng link xác minh chuẩn Laravel — set `email_verified_at = NOW()` khi user bấm link trong email.
- **Reset mật khẩu**: dùng link qua `PASSWORD_RESET_TOKENS` (Laravel Password Broker). Khi reset thành công, hạ `must_change_password = 0` và revoke toàn bộ `USER_SESSIONS` với lý do `password_change`.
- **Tương thích Authenticatable**: `password` (không phải `password_hash`) và `remember_token` là tên cột được Laravel Authenticatable contract dùng mặc định — không cần override `getAuthPassword()` hay `getRememberTokenName()`.
- **`email` vs `email_canonical`**: `email` là email gốc user nhập để hiển thị và gửi mail; `email_canonical` là bản chuẩn hóa để xác định cùng mailbox và chặn trùng tài khoản. `email_canonical` có UNIQUE constraint, còn `email` chỉ có INDEX thường. Khi đăng ký/login/reset, backend luôn normalize rồi tra `email_canonical`. `email_canonical` tự sinh trong model hook `saving` bởi `EmailBox::normalize()` — không cần set trong code nghiệp vụ.
'}},{