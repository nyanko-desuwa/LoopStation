# POINT_EARNED - Lịch sử cộng điểm

## Vai trò

Mỗi dòng là 1 lần điểm được cộng vào ví. `points` luôn dương. Không dùng dấu âm - lần trừ điểm ghi riêng ở `POINT_SPENT`. Cùng với `POINT_SPENT`, đây là nguồn sự thật cho `USER_WALLETS.balance`.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `wallet_id` | int | NOT NULL | - | FK → USER_WALLETS |
| `points` | int | NOT NULL | - | Số điểm cộng; luôn > 0 |
| `source_type` | varchar(30) | NOT NULL | - | Nguồn điểm: `handover` \| `event_minigame` \| `content_read` \| `manager_adjust` \| `redemption_refund` \| `sticker_bonus` |
| `reference_id` | int | NULL | - | ID bản ghi nguồn tương ứng. NULL với `manager_adjust` |
| `description` | varchar(300) | NULL | - | Mô tả hiển thị trong lịch sử giao dịch |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `wallet_id` | `USER_WALLETS.id` | Ví nhận điểm |

`reference_id` là polymorphic (không có FK cứng), truy nguồn bằng `source_type`:

| `source_type` | `reference_id` trỏ về |
| --- | --- |
| `handover` | `HANDOVER_REQUESTS.id` |
| `event_minigame` | `EVENT_REGISTRATIONS.id` |
| `content_read` | `CONTENT_READS.id` |
| `redemption_refund` | `REDEMPTIONS.id` |
| `sticker_bonus` | `USER_STICKERS.id` |
| `manager_adjust` | NULL |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(wallet_id, created_at)` | Feed lịch sử giao dịch của 1 ví |
| IDX | `(source_type, reference_id)` | Tra điểm phát sinh từ 1 bản ghi cụ thể |

## Ghi chú nghiệp vụ

- Append-only - không UPDATE/DELETE dòng đã ghi.
- `USER_WALLETS.balance = SUM(POINT_EARNED.points) - SUM(POINT_SPENT.points)` cho cùng ví.
- `manager_adjust`: manager tay cộng điểm bù trừ thủ công, `reference_id = NULL`, `description` bắt buộc ghi lý do.
