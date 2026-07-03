# GAME_PARTICIPANTS - Người chơi trong phiên mini game

## Vai trò

1 dòng/người chơi/phiên. Bảng xếp hạng real-time sort theo score. Hỗ trợ cả user đã đăng nhập lẫn khách chơi bằng nickname. Thưởng điểm ví qua `points_awarded` nếu APP_SETTINGS bật.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `session_id` | int | NOT NULL | - | FK → GAME_SESSIONS - thuộc phiên chơi nào |
| `user_id` | int | NULL | - | FK → USERS - user đã đăng nhập. NULL nếu chơi bằng nickname (khách) |
| `nickname` | varchar(100) | NOT NULL | - | Tên hiển thị trong phòng (user hoặc khách) |
| `score` | int | NOT NULL | 0 | Điểm số trong game (khác điểm ví). Mặc định 0 |
| `rank` | int | NULL | - | Thứ hạng chung cuộc trong phiên |
| `points_awarded` | int | NOT NULL | 0 | Điểm ví thực nhận sau phiên (nếu bật thưởng). Ghi POINT_EARNED tương ứng. Mặc định 0 |
| `joined_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | Thời điểm vào phòng |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `session_id` | `GAME_SESSIONS.id` | Thuộc phòng nào |
| `user_id` | `USERS.id` | User nào tham gia |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(session_id, score)` | Sắp xếp leaderboard theo điểm |
| IDX | `session_id` | Lấy toàn bộ người chơi của phiên |
| IDX | `user_id` | Lịch sử tham gia của user |

## Khóa ngoại

- `session_id` => `GAME_SESSIONS.id` ON DELETE CASCADE
- `user_id` => `USERS.id` ON DELETE SET NULL

## Quy tắc nghiệp vụ

- Chỉ user đã đăng nhập (user_id NOT NULL) mới được thưởng điểm ví; khách nickname chỉ chơi vui.
- Khi phiên `finished`: chốt `rank` theo `score`, nếu bật thưởng thì set `points_awarded` và insert `POINT_EARNED` (source_type = game).
