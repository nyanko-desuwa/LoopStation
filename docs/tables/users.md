# USERS - Tài khoản người dùng

## Vai trò

Bảng trung tâm lưu tất cả tài khoản trong hệ thống: khách hàng thông thường (`user`), nhân viên công ty (`staff`), và chủ cơ sở (`manager`). Ba role dùng chung 1 bảng, phân biệt qua cột `role`. Ngoài ra bảng còn bao gồm tài khoản vãng lai - được tạo tự động khi khách quét QR tại sự kiện mà chưa có tài khoản.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(150) | NOT NULL | - | Họ tên hiển thị |
| `phone` | varchar(20) | NULL | - | Số điện thoại. UQ. Dùng để đăng ký nhanh cho user vãng lai |
| `email` | varchar(150) | NULL | - | Email đăng nhập. UQ |
| `password_hash` | varchar(255) | NULL | - | Hash mật khẩu. Walk-in user luôn có password_hash (sinh mật khẩu tạm khi tạo tài khoản) |
| `avatar_url` | varchar(500) | NULL | - | Ảnh đại diện. Upload lên server, lưu path tương đối (vd: `avatars/abc.jpg`) |
| `must_change_password` | boolean | NOT NULL | false | `true` = đang dùng mật khẩu tạm, buộc đổi ở lần đăng nhập kế tiếp |
| `role` | varchar(20) | NOT NULL | `'user'` | `user` \| `staff` \| `manager` |
| `facility_id` | int | NULL | - | FK → FACILITIES. Bắt buộc với `staff` và `manager`. NULL với `user` |
| `is_walk_in` | boolean | NOT NULL | false | `true` = tài khoản tạo tự động từ QR sự kiện |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` |
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
- `USER_SESSIONS.user_id` - phiên đăng nhập
- `LOGIN_LOGS.user_id` - lịch sử đăng nhập
- `EDUCATIONAL_CONTENTS.author_id`, `approved_by_id` - staff soạn bài, manager duyệt
- `STICKER_REDEMPTIONS.staff_id` - staff xác nhận đổi sticker vật lý
- `SYSTEM_LOGS.performed_by_user_id` - ai thực hiện thao tác

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UQ | `email` | Đăng nhập |
| UQ | `phone` | Đăng ký nhanh walk-in |
| IDX | `role` | Lọc theo role |
| IDX | `facility_id` | Lọc staff/manager theo cơ sở |
| IDX | `deleted_at` | Lọc tài khoản chưa xóa |

## Ghi chú nghiệp vụ

- **Walk-in user**: khi khách quét QR sự kiện lần đầu mà không có tài khoản, hệ thống tự tạo tài khoản với `is_walk_in = true`. Mật khẩu tạm được sinh và hash, gửi qua email. Ngay lập tức hệ thống tạo `USER_SESSIONS` + ghi `LOGIN_LOGS` (login_method = `walk_in_auto_login`) - user vào app dùng được ngay, không cần mở email. `must_change_password = true` để nhắc đổi mật khẩu về sau.
- **Role không thay đổi thường xuyên**: role gắn với loại tài khoản, không phải quyền cụ thể. Quyền chi tiết đến từ `ROLE_PERMISSIONS`.
- `manager` chỉ quản lý sự kiện và đơn thuộc **cơ sở của mình** - xác định qua `USERS.facility_id`.
