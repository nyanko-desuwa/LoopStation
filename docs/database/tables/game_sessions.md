# GAME_SESSIONS - Phiên chơi mini game

## Vai trò

1 dòng = 1 phòng/phiên chơi real-time. Trạng thái live được đồng bộ qua WebSocket, DB chỉ lưu thông tin phòng và kết quả cuối. `room_code` unique để học sinh nhập vào join phòng (kiểu PIN Kahoot).

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `game_id` | int | NOT NULL | - | FK → MINI_GAMES - chơi game nào |
| `host_user_id` | int | NULL | - | FK → USERS - giáo viên/staff chủ trì phòng. NULL nếu chơi tự do |
| `room_code` | varchar(20) | NOT NULL, UNIQUE | - | Mã phòng học sinh nhập để vào (VD PIN 6 số kiểu Kahoot) |
| `status` | varchar(20) | NOT NULL | `'waiting'` | `waiting` \| `playing` \| `finished` \| `cancelled` |
| `points_reward` | int | NOT NULL | 0 | Điểm ví thưởng cho phiên (lấy default từ APP_SETTINGS, host chỉnh được). Mặc định 0 |
| `started_at` | timestamp | NULL | - | Thời điểm bắt đầu chơi |
| `ended_at` | timestamp | NULL | - | Thời điểm kết thúc |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `game_id` | `MINI_GAMES.id` | Phiên thuộc game nào |
| `host_user_id` | `USERS.id` | Ai chủ trì phòng |

## Khóa ngoại

- `game_id` => `MINI_GAMES.id` ON DELETE CASCADE
- `host_user_id` => `USERS.id` ON DELETE SET NULL

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| UNIQUE | `room_code` | Mã phòng không trùng |
| IDX | `game_id` | Lấy các phiên của 1 game |
| IDX | `host_user_id` | Lịch sử phòng của host |
| IDX | `status` | Lọc phòng đang chờ/chơi/xong |

## Chuyển trạng thái

```
waiting => playing (host bắt đầu) => finished (kết thúc, chốt rank + thưởng điểm)
        => cancelled (host hủy phòng)
```

## Quy tắc nghiệp vụ

- `room_code` dùng kiểu PIN để học sinh vào phòng.
- Khi host bắt đầu: chuyển `waiting => playing`, set `started_at = NOW()`.
- `points_reward` lấy default từ APP_SETTINGS, chỉ thưởng nếu cấu hình bật thưởng điểm game.
- Trạng thái real-time đồng bộ qua WebSocket, DB chỉ chốt kết quả cuối.
- Khi kết thúc: chuyển `finished`, set `ended_at`, chốt rank theo score và thưởng điểm nếu bật.
