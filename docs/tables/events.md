# EVENTS - Sự kiện Ngày hội sống xanh

## Vai trò

Lưu thông tin sự kiện tái chế tổ chức tại địa điểm bên ngoài (trường học, công viên, khu dân cư). Manager tạo sự kiện - hành động tạo được ghi vào `SYSTEM_LOGS` thay vì lưu `manager_id` trực tiếp trên bảng này. Cơ sở tổ chức suy ra từ `facility_id` của manager đó.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `title` | varchar(200) | NOT NULL | - | Tên sự kiện |
| `description` | text | NULL | - | Mô tả chi tiết |
| `location` | varchar(300) | NOT NULL | - | Địa điểm tổ chức bên ngoài |
| `qr_code` | varchar(100) | NOT NULL | - | Mã QR định danh. UQ. Chỉ active trong khung giờ sự kiện |
| `image_url` | varchar(500) | NULL | - | Ảnh poster/banner. Upload lên server, lưu path tương đối |
| `start_time` | timestamp | NOT NULL | - | Giờ bắt đầu |
| `end_time` | timestamp | NOT NULL | - | Giờ kết thúc |
| `expired_at` | timestamp | NULL | - | Thời điểm hết hạn đăng ký (nếu có) |
| `status` | varchar(20) | NOT NULL | `'upcoming'` | `upcoming` \| `active` \| `ended` \| `cancelled` |
| `deleted_at` | timestamp | NULL | - | Soft delete |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `EVENT_STAFF_ASSIGNMENTS` | `event_id` | Staff được phân công tại sự kiện |
| `EVENT_REWARDS` | `event_id` | Quà minigame của sự kiện |
| `EVENT_REGISTRATIONS` | `event_id` | Người đăng ký tham dự |
| `HANDOVER_REQUESTS` | `event_id` | Đơn hỏa tốc phát sinh tại sự kiện |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UQ | `qr_code` | QR duy nhất |
| IDX | `(status, start_time)` | Lọc sự kiện sắp diễn ra / đang diễn ra |
| IDX | `deleted_at` | Lọc chưa xóa |

## Ghi chú nghiệp vụ

- Manager tạo event được ghi vào `SYSTEM_LOGS` (`entity_type = 'event'`, `action = 'create'`, `performed_by_user_id = manager_id`). Từ đó suy ra cơ sở tổ chức.
- Khi `status = active`, QR code được phép quét để check-in và tạo đơn.
- Khi ngoài khung `start_time`–`end_time`, backend từ chối xử lý QR dù QR vẫn tồn tại trong DB.
- Chỉ staff thuộc cùng cơ sở của manager tạo event mới được phân công qua `EVENT_STAFF_ASSIGNMENTS`.
