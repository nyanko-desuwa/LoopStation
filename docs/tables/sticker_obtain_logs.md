# STICKER_OBTAIN_LOGS - Lịch sử nhận sticker

## Vai trò

Append-only log ghi lại mỗi lần user nhận được 1 sticker, kể cả khi trùng. Phục vụ tính danh hiệu theo kỳ (đếm sticker nhận được trong N ngày) và đối soát khi cần kiểm tra tại sao user có bao nhiêu sticker.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS |
| `sticker_id` | int | NOT NULL | - | FK → STICKERS - sticker nào rơi |
| `source_content_id` | int | NULL | - | FK → EDUCATIONAL_CONTENTS - bài đọc đã ra sticker này. NULL nếu từ nguồn khác |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | Thời điểm nhận |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User nhận sticker |
| `sticker_id` | `STICKERS.id` | Sticker được nhận |
| `source_content_id` | `EDUCATIONAL_CONTENTS.id` | Bài tạo ra sticker này |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, created_at)` | Lấy lịch sử nhận sticker của 1 user, đếm trong kỳ |
| IDX | `sticker_id` | Thống kê sticker nào rơi nhiều |
| IDX | `source_content_id` | Bài nào tạo ra nhiều sticker |

## Ghi chú nghiệp vụ

- Append-only - không sửa, không xóa.
- Dùng để tính danh hiệu: `COUNT(*) WHERE user_id = X AND created_at >= (NOW() - period_days)`.
- Khác `USER_STICKERS` (số dư hiện tại) - bảng này là log đầy đủ từ đầu đến nay.
