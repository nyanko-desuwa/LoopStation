# CONTENT_READS - Lượt đọc bài của user

## Vai trò

Track mỗi lần user mở đọc 1 bài học. Ghi nhận thời gian bắt đầu, thời gian hoàn thành (đủ timer), trạng thái đã nhận điểm chưa, và ngày đọc để tính quota nhận điểm hàng ngày.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS |
| `content_id` | int | NOT NULL | - | FK → EDUCATIONAL_CONTENTS |
| `started_at` | timestamp | NOT NULL | - | Thời điểm bắt đầu đọc |
| `completed_at` | timestamp | NULL | - | Thời điểm hoàn thành (đủ `timer_seconds`). NULL = chưa đủ |
| `rewarded` | boolean | NOT NULL | false | `true` = đã cộng điểm vào ví |
| `read_date` | date | NOT NULL | - | Ngày đọc (local time). Dùng để đếm quota ngày, reset 00:00 |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User đọc |
| `content_id` | `EDUCATIONAL_CONTENTS.id` | Bài được đọc |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, read_date)` | Đếm quota ngày của user |
| IDX | `content_id` | Thống kê lượt đọc của 1 bài |

## Ghi chú nghiệp vụ

- Quota nhận điểm: `rewarded = true` tối đa **10 lần/ngày/user** và tối đa **2 lần/ngày/user/content**. Đếm bằng cột `read_date`.
- Flow: insert dòng khi user mở bài (`started_at`) → frontend đếm ngược timer → khi đủ thời gian, backend update `completed_at` và kiểm tra quota → nếu còn quota: set `rewarded = true`, insert vào `POINT_EARNED`, cập nhật `USER_WALLETS.balance`.
- Sticker thưởng: nếu bài có `sticker_set_id` và `rewarded` vừa được set `true`, backend random 1 sticker từ bộ đó theo `drop_weight`, insert vào `STICKER_OBTAIN_LOGS` và cập nhật `USER_STICKERS`.
