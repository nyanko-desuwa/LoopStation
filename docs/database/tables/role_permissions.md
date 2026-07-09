# ROLE_PERMISSIONS - Mapping role → quyền

## Vai trò

Bảng nối gán quyền (`PERMISSIONS`) cho từng role (`user` / `staff` / `manager`). Manager cấu hình mapping qua UI.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `role` | varchar(20) | NOT NULL | - | `user` \| `staff` \| `manager` |
| `permission_id` | int | NOT NULL | - | FK → PERMISSIONS |
| `created_by` | int | NULL | - | FK → USERS - manager cấu hình mapping |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `permission_id` | `PERMISSIONS.id` | Quyền được gán |
| `created_by` | `USERS.id` | Manager cấu hình |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UNIQUE | `(role, permission_id)` | 1 role không gán trùng 1 quyền |
| IDX | `permission_id` | Tra role nào có quyền này |
| IDX | `role` | Lấy toàn bộ quyền của 1 role |
| IDX | `created_by` | Audit ai cấu hình |

## Ghi chú nghiệp vụ

- Khi user đăng nhập, backend load tập quyền theo `role` để cache trong session/token.
- Đổi mapping ở đây có hiệu lực với các phiên/token cấp sau - phiên đang chạy có thể cần refresh.
- `role` lưu dạng varchar (không FK sang bảng role riêng) - 3 role cố định, đủ dùng.
