# EVENT_STAFF_ASSIGNMENTS - Phân công staff tại sự kiện

## Vai trò

Bảng nối N-N giữa `EVENTS` và `USERS` (staff). Ghi nhận staff nào được phân công làm việc tại sự kiện nào. Có ràng buộc: 1 staff không được phân công 2 sự kiện overlap thời gian cùng lúc.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `event_id` | int | NOT NULL | - | FK → EVENTS |
| `staff_id` | int | NOT NULL | - | FK → USERS (role = staff) |
| `assigned_at` | timestamp | NULL | - | Thời điểm phân công |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `event_id` | `EVENTS.id` | Sự kiện được phân công |
| `staff_id` | `USERS.id` | Staff được phân công |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UQ | `(event_id, staff_id)` | 1 staff chỉ được phân công 1 lần cho 1 sự kiện |
| IDX | `staff_id` | Tra lịch công tác của staff |

## Ghi chú nghiệp vụ

- Trigger `BEFORE INSERT/UPDATE` chặn phân công nếu staff đã có sự kiện khác overlap `start_time`–`end_time`.
- Chỉ staff thuộc cùng cơ sở (`USERS.facility_id`) với manager tạo event mới được thêm vào đây.
- Manager xem lịch của từng staff trước khi phân công để tránh xung đột.
