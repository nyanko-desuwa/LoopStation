# STICKER_SETS - Bộ sưu tập sticker

## Vai trò

Nhóm các sticker theo chủ đề (VD: "Đại dương", "Rừng xanh"). Manager CRUD toàn bộ bộ sưu tập. Mỗi bài học giáo dục có thể gắn với 1 bộ - khi user đọc xong, hệ thống random 1 sticker từ bộ đó làm phần thưởng vui.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(150) | NOT NULL | - | Tên bộ sưu tập |
| `theme` | varchar(100) | NULL | - | Chủ đề mô tả thêm |
| `cover_image_url` | varchar(500) | NULL | - | Ảnh đại diện bộ. Upload lên server, lưu path tương đối |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked: tạm ẩn, sticker từ bộ này không rơi |
| `deleted_at` | timestamp | NULL | - | Soft delete. NULL = còn hoạt động |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `STICKERS` | `set_id` | Sticker thuộc bộ này |
| `EDUCATIONAL_CONTENTS` | `sticker_set_id` | Bài học gắn bộ này làm phần thưởng |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `status` | Lọc bộ đang active |
| IDX | `deleted_at` | Lọc soft delete |

## Ghi chú nghiệp vụ

- Khi `status = locked`, sticker trong bộ không xuất hiện trong vòng rơi - bài học vẫn hiển thị nhưng không trao sticker cho đến khi unlock lại.
- Xóa bộ dùng soft delete (`deleted_at`). Các sticker đã được user sở hữu vẫn giữ nguyên.
