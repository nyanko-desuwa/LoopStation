# STICKER_REDEMPTIONS - Lịch sử đổi sticker vật lý

## Vai trò

Ghi lại mỗi lần user mang sticker ảo đến cơ sở để đổi lấy vật phẩm vật lý. Có thể nhận tại cơ sở hoặc ship tận nhà. Staff xác nhận và ghi nhận tại đây.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS - user đổi |
| `sticker_id` | int | NOT NULL | - | FK → STICKERS - loại sticker đổi |
| `quantity_used` | int | NOT NULL | - | Số sticker ảo đã trừ để đổi 1 lần |
| `fulfillment_method` | varchar(20) | NOT NULL | `'pickup'` | `pickup` \| `delivery` |
| `status` | varchar(20) | NOT NULL | `'pending'` | `pending` \| `shipping` \| `fulfilled` \| `cancelled` |
| `facility_id` | int | NULL | - | FK → FACILITIES - cơ sở nhận khi pickup |
| `staff_id` | int | NULL | - | FK → USERS - staff xác nhận giao/đóng gói |
| `recipient_name` | varchar(150) | NULL | - | Tên người nhận khi delivery |
| `recipient_phone` | varchar(20) | NULL | - | SĐT người nhận khi delivery |
| `shipping_address` | varchar(500) | NULL | - | Địa chỉ giao hàng khi delivery |
| `shipping_note` | varchar(300) | NULL | - | Ghi chú giao hàng |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | Thời điểm đổi |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User đổi |
| `sticker_id` | `STICKERS.id` | Loại sticker đổi |
| `facility_id` | `FACILITIES.id` | Cơ sở nhận |
| `staff_id` | `USERS.id` | Staff xác nhận |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, created_at)` | Lịch sử đổi của 1 user |
| IDX | `(user_id, status)` | Đơn đổi đang chờ của user |
| IDX | `sticker_id` | Thống kê sticker nào được đổi nhiều |
| IDX | `facility_id` | Thống kê đổi theo cơ sở |
| IDX | `fulfillment_method` | Lọc pickup/delivery |

## Ghi chú nghiệp vụ

- Khi tạo bản ghi này, đồng thời giảm `USER_STICKERS.quantity -= quantity_used`.
- `quantity_used` chốt theo `STICKERS.redeem_quantity_required` tại thời điểm đổi - nếu manager thay đổi sau này, lịch sử không bị ảnh hưởng.
- `pickup`: `facility_id` bắt buộc, các cột ship để NULL.
- `delivery`: `recipient_name`, `recipient_phone`, `shipping_address` bắt buộc, `facility_id` để NULL.
- Không có cơ chế hoàn lại sticker sau khi đổi - phát tay xong là xong.
