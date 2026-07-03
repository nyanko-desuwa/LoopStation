# STICKERS - Từng sticker trong bộ sưu tập

## Vai trò

Định nghĩa từng sticker cụ thể trong 1 bộ sưu tập. Manager thiết lập tỉ lệ rơi (`drop_weight`), độ hiếm (`rarity`), điểm bonus lần đầu sở hữu, và bài đặc biệt được mở khóa. Hệ thống dùng `drop_weight` để random sticker thưởng khi user đọc xong bài.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `set_id` | int | NOT NULL | - | FK → STICKER_SETS |
| `name` | varchar(150) | NOT NULL | - | Tên sticker |
| `image_url` | varchar(500) | NOT NULL | - | Ảnh sticker. Upload lên server, lưu path tương đối |
| `rarity` | varchar(20) | NOT NULL | `'common'` | `common` \| `rare` \| `special` |
| `drop_weight` | int | NOT NULL | 1 | Trọng số rơi. Manager tự chỉnh % Sticker nào `drop_weight` cao hơn thì xác suất rơi cao hơn |
| `redeem_quantity_required` | int | NOT NULL | 1 | Cần đủ bao nhiêu sticker mới đổi được 1 lần vật lý (sticker dán + kẹo) |
| `bonus_points` | int | NOT NULL | 0 | Điểm tự cộng vào `POINT_EARNED` khi **lần đầu** sở hữu loại này |
| `unlocks_content_id` | int | NULL | - | FK → EDUCATIONAL_CONTENTS. Mở khóa bài đặc biệt khi lần đầu sở hữu. NULL = không có |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked: tạm ẩn khỏi vòng rơi |
| `deleted_at` | timestamp | NULL | - | Soft delete |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `set_id` | `STICKER_SETS.id` | Bộ chứa sticker này |
| `unlocks_content_id` | `EDUCATIONAL_CONTENTS.id` | Bài được mở khóa khi lần đầu có sticker này |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `USER_STICKERS` | `sticker_id` | Sticker user đang giữ |
| `STICKER_OBTAIN_LOGS` | `sticker_id` | Lịch sử nhận sticker này |
| `STICKER_REDEMPTIONS` | `sticker_id` | Lịch sử đổi vật lý sticker này |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `set_id` | Lấy tất cả sticker trong 1 bộ |
| IDX | `rarity` | Lọc theo độ hiếm |
| IDX | `unlocks_content_id` | Tra sticker mở bài nào |
| IDX | `deleted_at` | Lọc soft delete |

## Ghi chú nghiệp vụ

- Tỉ lệ rơi tính theo weighted random: `P(sticker X) = drop_weight_X / SUM(drop_weight tất cả sticker active trong bộ)`.
- `bonus_points` và `unlocks_content_id` chỉ kích hoạt **lần đầu** user sở hữu loại sticker đó (khi `USER_STICKERS.total_obtained` tăng từ 0 lên 1).
- Sticker `rarity = special` thường có `bonus_points` cao và/hoặc `unlocks_content_id` có giá trị.
