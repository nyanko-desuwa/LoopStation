# REWARD_CATALOG - Danh mục quà đổi bằng điểm

## Vai trò

Quản lý danh sách quà vật lý mà user có thể đổi bằng điểm ví xanh. Khác `EVENT_REWARDS` (quà minigame tại sự kiện) - quà ở đây đổi bất kỳ lúc nào qua ứng dụng, trừ điểm từ `USER_WALLETS`.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(150) | NOT NULL | - | Tên quà |
| `description` | text | NULL | - | Mô tả quà |
| `image_url` | varchar(500) | NULL | - | Ảnh quà. Upload lên server, lưu path tương đối |
| `points_cost` | int | NOT NULL | - | Số điểm cần để đổi 1 phần quà |
| `stock` | int | NOT NULL | 0 | Số lượng còn lại, trừ dần khi user đổi thành công |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked: tạm ngừng cho đổi |
| `deleted_at` | timestamp | NULL | - | Soft delete |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `REDEMPTIONS` | `reward_id` | Lịch sử đổi quà này |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `status` | Lọc quà đang active |
| IDX | `deleted_at` | Lọc soft delete |

## Ghi chú nghiệp vụ

- Khi user đổi thành công: `stock -= quantity` (trong cùng 1 transaction với POINT_SPENT).
- Cần kiểm tra `stock >= quantity` và `status = active` trước khi cho phép đổi.
- Khi `stock = 0`, nên hiển thị "hết hàng" thay vì ẩn - user biết quà đã tồn tại.
