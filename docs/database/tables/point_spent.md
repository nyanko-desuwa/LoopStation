# POINT_SPENT - Lịch sử trừ điểm

## Vai trò

Mỗi dòng là 1 lần điểm bị trừ khỏi ví. `points` luôn dương. Đối xứng với `POINT_EARNED` - tách riêng để dễ audit và tránh nhầm dấu. Cùng với `POINT_EARNED`, đây là nguồn sự thật cho `USER_WALLETS.balance`.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `wallet_id` | int | NOT NULL | - | FK → USER_WALLETS |
| `points` | int | NOT NULL | - | Số điểm trừ; luôn > 0 |
| `source_type` | varchar(30) | NOT NULL | - | `redemption` \| `manager_adjust` |
| `reference_id` | int | NULL | - | ID bản ghi nguồn. NULL với `manager_adjust` |
| `description` | varchar(300) | NULL | - | Mô tả hiển thị trong lịch sử giao dịch |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `wallet_id` | `USER_WALLETS.id` | Ví bị trừ điểm |

`reference_id` theo `source_type`:

| `source_type` | `reference_id` trỏ về |
| --- | --- |
| `redemption` | `REDEMPTIONS.id` |
| `manager_adjust` | NULL |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `REDEMPTIONS` | `transaction_id` | Mỗi lần đổi quà gắn 1 dòng trừ điểm |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(wallet_id, created_at)` | Feed lịch sử giao dịch của 1 ví |
| IDX | `(source_type, reference_id)` | Tra điểm trừ từ 1 bản ghi cụ thể |

## Ghi chú nghiệp vụ

- Append-only - không UPDATE/DELETE dòng đã ghi.
- Trước khi trừ điểm, backend kiểm tra `USER_WALLETS.balance >= points` để tránh số dư âm.
- `manager_adjust`: manager tay trừ điểm thủ công, `description` bắt buộc ghi lý do.
