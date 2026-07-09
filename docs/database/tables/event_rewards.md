# EVENT_REWARDS - Quà tặng minigame tại sự kiện

## Vai trò

Danh sách quà vật lý được thiết lập cho mỗi sự kiện, dùng làm phần thưởng khi user chơi minigame và trúng thưởng. Số lượng trừ dần sau mỗi lần trúng. Bảng này **khác** với `REWARD_CATALOG` (quà đổi bằng điểm ví).

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `event_id` | int | NOT NULL | - | FK → EVENTS |
| `name` | varchar(150) | NOT NULL | - | Tên quà |
| `description` | text | NULL | - | Mô tả chi tiết |
| `quantity` | int | NOT NULL | 0 | Số lượng ban đầu |
| `remaining` | int | NOT NULL | 0 | Số còn lại; trừ dần khi minigame trúng thưởng |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `event_id` | `EVENTS.id` | Thuộc sự kiện nào |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `event_id` | Tra tất cả quà của 1 sự kiện |

## Ghi chú nghiệp vụ

- `remaining = 0` → hết quà, minigame không thể trúng loại quà này nữa.
- Backend kiểm tra `remaining > 0` trước khi phát thưởng, trừ bằng transaction để tránh race condition.
- Phân biệt với `REWARD_CATALOG`: quà trong bảng này là quà vật lý phát tại chỗ trong event, không liên quan đến điểm ví.
