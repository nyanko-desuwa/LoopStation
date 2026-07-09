# SYSTEM_LOGS - Audit log thao tác nghiệp vụ

## Vai trò

Audit log mọi thao tác thay đổi trạng thái đối tượng trong hệ thống. Ghi được nhiều loại đối tượng qua cặp `entity_type` + `entity_id`. Đây cũng là nơi lưu việc "manager tạo event" (thay vì lưu `manager_id` trực tiếp trên EVENTS).

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `entity_type` | varchar(30) | NOT NULL | - | `handover` \| `event` \| `user` \| `content` \| `facility` |
| `entity_id` | int | NOT NULL | - | ID của đối tượng bị tác động |
| `action` | varchar(50) | NOT NULL | - | `create` \| `approve` \| `reject` \| `reschedule` \| `complete` \| `cancel` ... |
| `old_status` | varchar(30) | NULL | - | Trạng thái trước |
| `new_status` | varchar(30) | NULL | - | Trạng thái sau |
| `details` | text | NULL | - | JSON chi tiết payload thay đổi |
| `performed_by_user_id` | int | NULL | - | FK → USERS - người thực hiện |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `performed_by_user_id` | `USERS.id` | Người thực hiện thao tác |

> `entity_id` là FK "mềm" (soft FK): không ràng buộc DB vì trỏ đến nhiều bảng khác nhau tùy `entity_type`.

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(entity_type, entity_id)` | Lấy lịch sử của 1 đối tượng |
| IDX | `performed_by_user_id` | Thao tác của 1 người |
| IDX | `created_at` | Duyệt log theo thời gian |

## Ghi chú nghiệp vụ

- Polymorphic log: `entity_type` + `entity_id` cho phép log mọi loại đối tượng trong 1 bảng.
- **Truy nguồn người tạo event:** query `entity_type = 'event' AND action = 'create'` để biết manager nào tạo - EVENTS không có cột `manager_id`. Cơ sở tổ chức event suy ra từ facility của manager này.
- `details` lưu JSON linh hoạt để không phải thêm cột mỗi khi cần log thêm dữ liệu.
- Append-only theo quy ước - phục vụ điều tra và truy vết thay đổi.
