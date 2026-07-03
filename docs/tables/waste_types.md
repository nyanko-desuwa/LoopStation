# WASTE_TYPES - Loại rác

## Vai trò

Bảng danh mục các loại rác có thể chọn khi tạo đơn chuyển giao. Manager quản lý danh sách gốc. User chỉ **chọn** từ danh sách, không tự tạo loại rác mới trong hệ thống - nút "+" trong giao diện tạo đơn dùng để thêm **dòng rác** vào đơn (mỗi dòng là 1 loại rác + khối lượng), không phải tạo loại mới.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(100) | NOT NULL | - | Tên loại rác (vd: Giấy bìa, Rác hữu cơ) |
| `icon` | varchar(50) | NULL | - | Icon/emoji hiển thị (tùy chọn) |
| `is_system` | boolean | NOT NULL | true | `true` = phân loại gốc do manager tạo \| `false` = user tự thêm |
| `created_by` | int | NULL | - | FK → USERS. NULL nếu là phân loại gốc hệ thống |
| `deleted_at` | timestamp | NULL | - | Soft delete |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `created_by` | `USERS.id` | Manager/user đã thêm loại rác này |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `HANDOVER_WASTE_ITEMS` | `waste_type_id` | Loại rác được chọn trong đơn |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `is_system` | Phân biệt loại gốc / tùy chỉnh |
| IDX | `deleted_at` | Lọc chưa xóa |

## Ghi chú nghiệp vụ

- `is_system = true`: do manager seed, là loại rác chuẩn của hệ thống (giấy, nhựa, kim loại,...).
- `is_system = false`: user thêm loại rác tùy chỉnh - chỉ hiển thị cho user đó, không ảnh hưởng danh sách chung.
- Khi xóa mềm (`deleted_at != NULL`), loại rác không còn hiển thị trong form tạo đơn mới nhưng các đơn cũ vẫn tham chiếu được.
