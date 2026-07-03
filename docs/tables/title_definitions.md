# TITLE_DEFINITIONS - Định nghĩa danh hiệu

## Vai trò

Lưu các loại danh hiệu theo kỳ mà user có thể đạt được (VD: "Nhà sưu tập nhí", "Người đọc chăm chỉ"). Manager cấu hình toàn bộ tiêu chí, ngưỡng và số ngày xét - Danh hiệu mang tính luân phiên: đạt trong 30 ngày, hết hạn, cần đạt lại để giữ.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(150) | NOT NULL | - | Tên danh hiệu (VD: Nhà sưu tập nhí) |
| `description` | text | NULL | - | Mô tả danh hiệu |
| `icon_url` | varchar(500) | NULL | - | Icon hiển thị. Upload lên server, lưu path tương đối |
| `criteria_type` | varchar(50) | NOT NULL | - | Loại tiêu chí: `sticker_count` \| `rare_sticker_count` \| `content_read_count`. Mở rộng được, không ràng ENUM cứng |
| `threshold` | int | NOT NULL | - | Ngưỡng cần đạt trong kỳ (VD: 10 sticker) |
| `period_days` | int | NOT NULL | 30 | Số ngày tính ngược để xét. Manager đổi được |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked: tạm ngừng cấp danh hiệu này |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `USER_TITLES` | `title_id` | Danh hiệu này đã cấp cho ai |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `status` | Lọc danh hiệu đang active |
| IDX | `criteria_type` | Nhóm theo loại tiêu chí |

## Ghi chú nghiệp vụ

- `criteria_type` là varchar tự do - backend đọc và dispatch logic xét tương ứng. Thêm loại tiêu chí mới không cần migration.
- Thay đổi `period_days` hay `threshold` chỉ ảnh hưởng đến lần cấp tiếp theo - `USER_TITLES` đã cấp giữ nguyên `expires_at` gốc.
- Logic xét: `COUNT(bảng_liên_quan) WHERE user_id = X AND time_field >= (NOW() - period_days * INTERVAL 1 DAY) >= threshold`.
