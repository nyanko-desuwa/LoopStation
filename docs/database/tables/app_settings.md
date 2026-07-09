# APP_SETTINGS - Cấu hình ứng dụng (key-value)

## Vai trò

Bảng key-value tập trung cho toàn bộ cấu hình do manager chỉnh qua UI. Mọi setting đều lưu ở đây.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `setting_key` | varchar(100) | NOT NULL, UNIQUE | - | Khóa setting (VD: `storage_base_path`, `upload_max_size`) |
| `setting_value` | text | NULL | - | Giá trị setting. NULL = chưa cấu hình |
| `description` | varchar(300) | NULL | - | Mô tả cho manager hiểu ý nghĩa setting |
| `updated_by` | int | NULL | - | FK → USERS - manager cập nhật lần cuối |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `updated_by` | `USERS.id` | Manager cập nhật cuối |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UNIQUE | `setting_key` | Mỗi khóa duy nhất |

## Ghi chú nghiệp vụ

- `setting_value` là text - backend tự parse kiểu (số, boolean, JSON) theo từng `setting_key`.
- Ví dụ setting: `storage_base_path`, `upload_max_size`, quota đọc bài/ngày, ngưỡng brute-force, timeout reset mật khẩu.
- Nên cache tầng ứng dụng và invalidate khi có cập nhật, tránh query DB mỗi request.
