# PERMISSIONS - Danh mục quyền RBAC

## Vai trò

Định nghĩa toàn bộ quyền trong hệ thống theo dạng `resource.action` (VD: `handover.create`, `event.approve`). Backend check permission code trước mỗi thao tác.  

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `code` | varchar(100) | NOT NULL, UNIQUE | - | `resource.action`, VD: `handover.create` |
| `resource` | varchar(50) | NOT NULL | - | Đối tượng: `handover`, `event`, `content`, ... |
| `action` | varchar(50) | NOT NULL | - | Hành động: `create`, `approve`, `publish`, ... |
| `name` | varchar(150) | NOT NULL | - | Tên hiển thị cho manager |
| `description` | varchar(255) | NULL | - | Mô tả quyền |
| `is_system` | boolean | NOT NULL | true | true = seed bởi hệ thống \| false = manager thêm |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `ROLE_PERMISSIONS` | `permission_id` | Quyền này gán cho role nào |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UNIQUE | `code` | Không trùng code quyền |
| UNIQUE | `(resource, action)` | 1 cặp resource+action là duy nhất |
| IDX | `resource` | Nhóm quyền theo đối tượng |

## Ghi chú nghiệp vụ

- `code` là chuỗi tra cứu chính ở backend middleware - cần ổn định, không đổi sau khi seed.
- `is_system = true` cho quyền gốc - không nên cho manager xóa, tránh vỡ logic backend.
- Kết hợp với `ROLE_PERMISSIONS` để map role → tập quyền.
