# MINI_GAMES - Định nghĩa mini game real-time

## Vai trò

Định nghĩa mini game tương tác real-time (kiểu Kahoot): giáo viên lồng vào bài giảng, học sinh chơi cùng trên web. Dùng `game_type + config_json` để thêm loại game mới mà không cần đổi schema.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `title` | varchar(200) | NOT NULL | - | Tên game |
| `game_type` | varchar(50) | NOT NULL | - | `quiz` \| `sorting_race` \| `bingo` \| `matching` \| `wheel` \| `guess_image` ... - mở rộng được, không ràng ENUM cứng |
| `description` | text | NULL | - | Mô tả |
| `config_json` | json | NULL | - | Cấu hình + dữ liệu game tùy theo `game_type` (câu hỏi, đáp án, thời gian, hình ảnh...). Backend parse theo `game_type` |
| `content_id` | int | NULL | - | FK → EDUCATIONAL_CONTENTS - gắn với bài học nào. NULL = game độc lập |
| `created_by` | int | NULL | - | FK → USERS - staff/manager tạo game |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked: tạm ẩn khỏi danh sách |
| `deleted_at` | timestamp | NULL | - | Soft delete |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `content_id` | `EDUCATIONAL_CONTENTS.id` | Game gắn với bài học nào |
| `created_by` | `USERS.id` | Ai tạo game |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `game_type` | Lọc theo loại game |
| IDX | `content_id` | Lấy game theo bài học |
| IDX | `created_by` | Thống kê game theo người tạo |
| IDX | `status` | Lọc active/locked |
| IDX | `deleted_at` | Lọc soft delete |

## Ghi chú nghiệp vụ

- Backend parse `config_json` theo `game_type`.
- Thêm loại game mới chỉ cần thêm kiểu `game_type` và format `config_json` tương ứng, không cần đổi schema.
- Staff tạo/host game, manager CRUD toàn bộ (quyền `mini_game.*`).
- `status = locked` dùng để tạm ẩn game mà không xóa dữ liệu.
