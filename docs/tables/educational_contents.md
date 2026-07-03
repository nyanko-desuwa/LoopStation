# EDUCATIONAL_CONTENTS - Bài học giáo dục môi trường

## Vai trò

Lưu các bài viết/bài học giáo dục về môi trường. Staff soạn bài, manager duyệt trước khi publish cho user đọc. Nội dung dạng HTML (rich text) để hỗ trợ ảnh nhúng. Sau khi đọc đủ thời gian tối thiểu, user nhận điểm và có thể nhận sticker ngẫu nhiên từ bộ sticker gắn với bài.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `title` | varchar(200) | NOT NULL | - | Tiêu đề bài học |
| `content` | text | NOT NULL | - | Nội dung dạng HTML (rich text). Ảnh minh họa embed trực tiếp qua `<img src="...">`. Bài gốc PDF: ảnh bìa để ở `thumbnail_url` |
| `author_id` | int | NOT NULL | - | FK → USERS (role = staff) - người soạn |
| `approved_by_id` | int | NULL | - | FK → USERS (role = manager) - người duyệt. NULL = chưa duyệt |
| `thumbnail_url` | varchar(500) | NULL | - | Ảnh bìa/thumbnail. Upload lên server, lưu path tương đối |
| `status` | varchar(20) | NOT NULL | `'pending'` | `pending` \| `published` \| `rejected` |
| `timer_seconds` | int | NOT NULL | - | Thời gian tối thiểu đọc để nhận điểm (giây) |
| `points_reward` | int | NOT NULL | - | Số điểm cộng khi đọc xong (đủ timer + còn quota) |
| `sticker_set_id` | int | NULL | - | FK → STICKER_SETS. Bộ sticker thưởng khi đọc xong. NULL = bài không thưởng sticker |
| `deleted_at` | timestamp | NULL | - | Soft delete. NULL = còn hoạt động |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `author_id` | `USERS.id` | Staff soạn bài |
| `approved_by_id` | `USERS.id` | Manager duyệt bài |
| `sticker_set_id` | `STICKER_SETS.id` | Bộ sticker thưởng |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `CONTENT_READS` | `content_id` | Lượt đọc của user |
| `STICKERS` | `unlocks_content_id` | Sticker mở khóa bài đặc biệt này |
| `STICKER_OBTAIN_LOGS` | `source_content_id` | Sticker rơi ra từ bài này |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `status` | Lọc bài published |
| IDX | `author_id` | Bài của 1 staff |
| IDX | `sticker_set_id` | Bài gắn với bộ sticker |
| IDX | `deleted_at` | Lọc soft delete |

## Ghi chú nghiệp vụ

- Quy trình: Staff tạo bài (`status = pending`) → Manager duyệt (`published`) hoặc từ chối (`rejected`) → User chỉ thấy bài `published`.
- Ảnh minh họa trong bài: upload lên server → server trả về path → nhúng `<img src="/storage/contents/xxx.jpg">` vào `content` HTML.
- `thumbnail_url`: ảnh bìa hiển thị ở danh sách bài, tách riêng khỏi `content`.
- Quota đọc nhận điểm: tối đa 10 lượt rewarded/ngày/user, mỗi content tối đa 2 lượt rewarded/ngày/user - enforce tại `CONTENT_READS`.
