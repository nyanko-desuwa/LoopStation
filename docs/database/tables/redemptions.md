# REDEMPTIONS - Lịch sử đổi quà bằng điểm

## Vai trò

Ghi nhận mỗi lần user đổi quà từ `REWARD_CATALOG` bằng điểm ví. Hỗ trợ nhận tại cơ sở hoặc ship tận nhà, đồng thời chốt snapshot `points_spent` tại thời điểm đổi.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS - user đổi |
| `reward_id` | int | NOT NULL | - | FK → REWARD_CATALOG - quà được đổi |
| `points_spent` | int | NOT NULL | - | Điểm đã trừ, chốt theo `points_cost` tại thời điểm đổi |
| `quantity` | int | NOT NULL | 1 | Số phần quà đổi trong 1 lần |
| `status` | varchar(20) | NOT NULL | `'pending'` | `pending` \| `shipping` \| `fulfilled` \| `cancelled` |
| `fulfillment_method` | varchar(20) | NOT NULL | `'pickup'` | `pickup` \| `delivery` |
| `recipient_name` | varchar(150) | NULL | - | Tên người nhận khi ship |
| `recipient_phone` | varchar(20) | NULL | - | SĐT người nhận khi ship |
| `shipping_address` | varchar(500) | NULL | - | Địa chỉ giao hàng khi ship |
| `shipping_note` | varchar(300) | NULL | - | Ghi chú giao hàng |
| `transaction_id` | int | NULL | - | FK → POINT_SPENT - giao dịch trừ điểm tương ứng |
| `fulfilled_by_id` | int | NULL | - | FK → USERS - staff/manager xác nhận giao quà |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User đổi |
| `reward_id` | `REWARD_CATALOG.id` | Quà được đổi |
| `transaction_id` | `POINT_SPENT.id` | Giao dịch trừ điểm |
| `fulfilled_by_id` | `USERS.id` | Người giao quà |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, status)` | Đơn đổi đang chờ của user |
| IDX | `reward_id` | Thống kê quà nào đổi nhiều |
| IDX | `transaction_id` | Truy giao dịch điểm |
| IDX | `fulfillment_method` | Lọc pickup/delivery |

## Ghi chú nghiệp vụ

- Đổi quà là 1 transaction: tạo `REDEMPTIONS` + tạo `POINT_SPENT` (link `transaction_id`) + giảm `REWARD_CATALOG.stock`.
- `points_spent` chốt tại thời điểm đổi - nếu manager đổi `points_cost` sau, đơn cũ giữ nguyên.
- `fulfillment_method = delivery` bắt buộc có `recipient_name`, `recipient_phone`, `shipping_address`; `pickup` thì các cột này để NULL.
- Đơn ship đi qua trạng thái `shipping` trước khi `fulfilled`; đơn pickup có thể nhảy thẳng `pending => fulfilled`.
- Khi `cancelled`: hoàn điểm qua `POINT_EARNED` (source_type = `redemption_refund`) và cộng lại stock.
