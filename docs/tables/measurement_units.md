# MEASUREMENT_UNITS - Đơn vị đo

## Vai trò

Bảng danh mục (lookup) các đơn vị đo được dùng để ghi khối lượng/thể tích rác trong đơn chuyển giao. Manager quản lý toàn bộ danh sách.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(50) | NOT NULL | - | Tên đơn vị hiển thị (vd: Kilogram) |
| `symbol` | varchar(20) | NOT NULL | - | Ký hiệu (vd: `kg`, `g`, `ml`) |
| `category` | varchar(30) | NOT NULL | - | `weight` \| `volume` \| `count` |
| `is_system` | boolean | NOT NULL | true | `true` = đơn vị seed sẵn khi deploy \| `false` = manager thêm mới |
| `created_by` | int | NULL | - | FK → USERS. NULL nếu là đơn vị gốc hệ thống |
| `deleted_at` | timestamp | NULL | - | Soft delete |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `created_by` | `USERS.id` | Manager đã thêm đơn vị này |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `HANDOVER_REQUESTS` | `unit_id` | Đơn vị của `estimated_weight` |
| `HANDOVER_WASTE_ITEMS` | `unit_id` | Đơn vị của từng dòng rác |
| `HANDOVER_WEIGHT_LOGS` | `unit_id` | Đơn vị khi cân thực tế |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `category` | Lọc theo loại đo |
| IDX | `is_system` | Phân biệt đơn vị gốc / tùy chỉnh |
| IDX | `deleted_at` | Lọc chưa xóa |

## Ghi chú nghiệp vụ

- Các đơn vị hệ thống (`is_system = true`) được seed khi deploy, không xóa.
- Manager có thể thêm đơn vị mới (`is_system = false`) nhưng không được sửa/xóa đơn vị đang được dùng trong đơn đã tồn tại.
