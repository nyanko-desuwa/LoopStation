# EVENT_REGISTRATIONS - Đăng ký tham dự sự kiện

## Vai trò

Ghi nhận user đăng ký hoặc được tạo tài khoản tại sự kiện. Phân biệt 3 loại đăng ký: tham quan, đăng ký nộp đồ, và khách vãng lai quét QR. Cũng lưu trạng thái chơi minigame của user tại sự kiện đó.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `event_id` | int | NOT NULL | - | FK → EVENTS |
| `user_id` | int | NOT NULL | - | FK → USERS |
| `registration_type` | varchar(20) | NOT NULL | - | `visit` = tham quan \| `handover` = đăng ký nộp đồ \| `walkin` = khách vãng lai |
| `status` | varchar(20) | NOT NULL | `'registered'` | `registered` \| `attended` \| `absent` |
| `minigame_status` | varchar(20) | NOT NULL | `'not_eligible'` | `not_eligible` \| `unlocked` \| `played` |
| `checked_in_at` | timestamp | NULL | - | Thời điểm check-in tại sự kiện. NULL = chưa check-in |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `event_id` | `EVENTS.id` | Sự kiện đăng ký |
| `user_id` | `USERS.id` | User đăng ký |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UQ | `(event_id, user_id)` | 1 user chỉ đăng ký 1 lần/sự kiện |
| IDX | `user_id` | Tra lịch sử tham dự của user |

## Ghi chú nghiệp vụ

- `registration_type = 'walkin'`: tạo tự động khi user quét QR sự kiện lần đầu (user chưa đăng ký trước). Hệ thống đồng thời tạo tài khoản `USERS` và insert dòng này.
- `minigame_status = 'unlocked'` được set sau khi user hoàn thành điều kiện (ví dụ: nộp rác thành công tại event).
- `checked_in_at` được ghi khi user quét QR check-in tại sự kiện.
