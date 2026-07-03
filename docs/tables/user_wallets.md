# USER_WALLETS - Ví điểm xanh

## Vai trò

Lưu số dư điểm hiện tại của user. Quan hệ 1-1 với `USERS` - mỗi user có đúng 1 ví. Bảng này chỉ lưu `balance` (số dư tổng hợp); chi tiết từng giao dịch ở `POINT_EARNED` và `POINT_SPENT`.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS. UQ (quan hệ 1-1) |
| `balance` | int | NOT NULL | 0 | Số điểm hiện tại |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | Lần cuối cập nhật số dư |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` (UQ) | Chủ ví |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `POINT_EARNED` | `wallet_id` | Các lần cộng điểm |
| `POINT_SPENT` | `wallet_id` | Các lần trừ điểm |

## Ghi chú nghiệp vụ

- `balance` phải luôn bằng `SUM(POINT_EARNED.points) - SUM(POINT_SPENT.points)` cho ví đó.
- Mọi thao tác thay đổi điểm phải đồng thời insert vào `POINT_EARNED`/`POINT_SPENT` **và** update `balance` trong cùng 1 transaction.
- Ví được tạo tự động khi user đăng ký tài khoản (bao gồm cả user vãng lai).
