# STICKER_REWARD_RULES - Rule đổi sticker ra vật phẩm

## Vai trò

Bảng cấu hình rule cho quà đổi sticker: 1 sticker ảo đổi ra vật phẩm nào, mỗi thứ bao nhiêu. Manager tạo rule để thay cho hardcode “kẹo”.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `sticker_id` | int | NOT NULL | - | FK → STICKERS - loại sticker ảo dùng để đổi |
| `reward_item_id` | int | NOT NULL | - | FK → STICKER_REWARD_ITEMS - vật phẩm nhận được |
| `quantity` | int | NOT NULL | 1 | Đổi 1 lần sticker này ra bao nhiêu vật phẩm |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked: tạm ngừng áp dụng rule |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `sticker_id` | `STICKERS.id` | Sticker nào dùng rule này |
| `reward_item_id` | `STICKER_REWARD_ITEMS.id` | Vật phẩm được trả |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UNIQUE | `(sticker_id, reward_item_id)` | Mỗi cặp sticker-vật phẩm chỉ có 1 rule |
| IDX | `sticker_id` | Lấy toàn bộ rule của 1 sticker |
| IDX | `reward_item_id` | Thống kê vật phẩm nào xuất hiện trong rule |

## Ghi chú nghiệp vụ

- Manager có thể bật/tắt rule bằng `status` mà không cần xóa.
- Khi user đổi sticker, hệ thống đọc các rule `active` của sticker đó rồi chốt snapshot sang `STICKER_REDEMPTION_ITEMS`.
- Nếu rule bị sửa sau này, các đơn đã đổi không bị ảnh hưởng.
