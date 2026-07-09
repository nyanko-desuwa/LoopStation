# FACILITIES - Cơ sở / Trạm thu hồi

## Vai trò

Lưu danh sách cơ sở và trạm thu hồi rác của công ty. Đơn chuyển giao (`HANDOVER_REQUESTS`) trỏ về đây để xác định khách giao rác tại cơ sở nào. Staff và manager cũng gắn với một cơ sở cụ thể qua `USERS.facility_id`.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(200) | NOT NULL | - | Tên cơ sở hiển thị |
| `type` | varchar(20) | NOT NULL | - | `station` = trạm thu hồi \| `office` = cơ sở/văn phòng công ty |
| `address` | varchar(300) | NULL | - | Địa chỉ đầy đủ |
| `latitude` | decimal(10,7) | NULL | - | Vĩ độ - dùng để lọc cơ sở gần nhất |
| `longitude` | decimal(10,7) | NULL | - | Kinh độ |
| `image_url` | varchar(500) | NULL | - | Ảnh cơ sở. Upload lên server, lưu path tương đối |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked ẩn khỏi portal người dùng |
| `deleted_at` | timestamp | NULL | - | Soft delete. NULL = còn hoạt động |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `USERS` | `facility_id` | Staff/manager trực thuộc cơ sở này |
| `HANDOVER_REQUESTS` | `facility_id` | Đơn chuyển giao nộp tại cơ sở này |
| `STICKER_REDEMPTIONS` | `facility_id` | Đổi sticker vật lý tại cơ sở này |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `status` | Lọc cơ sở đang hoạt động |
| IDX | `type` | Lọc theo loại (trạm / văn phòng) |
| IDX | `deleted_at` | Lọc chưa xóa |

## Ghi chú nghiệp vụ

- Khi `status = locked` hoặc `deleted_at IS NOT NULL`, cơ sở không hiển thị cho người dùng chọn khi tạo đơn.
- Cơ sở tổ chức sự kiện không được lưu trực tiếp trên `EVENTS` - suy ra từ `facility_id` của manager tạo event (tra qua `SYSTEM_LOGS`).
- Toạ độ `latitude`/`longitude` dùng để tính khoảng cách, gợi ý cơ sở gần nhất cho user khi tạo đơn.
