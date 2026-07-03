# STICKER_REDEMPTION_ITEMS - Snapshot vật phẩm của đơn đổi sticker

## Vai trò

Lưu snapshot chính xác các vật phẩm đã giao trong 1 lần đổi sticker. Bảng này giữ lịch sử bất biến, không đổi dù rule hoặc vật phẩm gốc thay đổi sau này.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `redemption_id` | int | NOT NULL | - | FK → STICKER_REDEMPTIONS - lần đổi nào |
| `reward_item_id` | int | NULL | - | FK → STICKER_REWARD_ITEMS - vật phẩm gốc, NULL nếu bị xóa sau này |
| `item_name` | varchar(150) | NOT NULL | - | Snapshot tên vật phẩm tại thời điểm đổi |
| `item_image_url` | varchar(500) | NULL | - | Snapshot ảnh vật phẩm tại thời điểm đổi |
| `quantity` | int | NOT NULL | - | Số lượng vật phẩm đã giao |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `redemption_id` | `STICKER_REDEMPTIONS.id` | Thuộc lần đổi nào |
| `reward_item_id` | `STICKER_REWARD_ITEMS.id` | Vật phẩm gốc trong catalog |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `redemption_id` | Lấy toàn bộ vật phẩm của 1 đơn đổi |
| IDX | `reward_item_id` | Thống kê vật phẩm nào được giao nhiều |

## Ghi chú nghiệp vụ

- Mỗi vật phẩm trong 1 lần đổi = 1 dòng snapshot.
- `item_name` và `item_image_url` là dữ liệu chốt lịch sử, không đổi theo catalog gốc.
- Nếu vật phẩm gốc bị xóa, `reward_item_id` có thể về NULL nhưng snapshot vẫn còn nguyên.
