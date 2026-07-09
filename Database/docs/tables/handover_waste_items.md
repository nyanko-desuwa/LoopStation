# HANDOVER_WASTE_ITEMS - Dòng loại rác trong đơn

## Vai trò

Bảng nối N-N giữa `HANDOVER_REQUESTS` và `WASTE_TYPES`. Mỗi dòng = 1 loại rác được chọn trong 1 đơn, kèm khối lượng user khai báo và đơn vị đo. User nhấn dấu "+" trong giao diện để thêm dòng rác khác vào cùng đơn - không phải tạo loại rác mới.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `request_id` | int | NOT NULL | - | FK → HANDOVER_REQUESTS |
| `waste_type_id` | int | NOT NULL | - | FK → WASTE_TYPES. Loại rác được chọn |
| `weight` | decimal(10,2) | NOT NULL | - | Khối lượng user nhập khi tạo đơn |
| `unit_id` | int | NOT NULL | - | FK → MEASUREMENT_UNITS. Đơn vị của `weight` |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `request_id` | `HANDOVER_REQUESTS.id` | Thuộc đơn nào |
| `waste_type_id` | `WASTE_TYPES.id` | Loại rác nào |
| `unit_id` | `MEASUREMENT_UNITS.id` | Đơn vị đo |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UQ | `(request_id, waste_type_id)` | 1 đơn không được chọn trùng loại rác |
| IDX | `waste_type_id` | Thống kê theo loại rác |

## Ghi chú nghiệp vụ

- Ràng buộc unique `(request_id, waste_type_id)` đảm bảo mỗi loại rác chỉ xuất hiện 1 lần trong 1 đơn.
- `weight` ở đây là giá trị user tự khai khi tạo đơn (ước tính). Cân thực tế lưu tách riêng ở `HANDOVER_WEIGHT_LOGS`.
- Khi user nhấn "+" trong form, frontend tạo thêm 1 dòng mới vào bảng này với `waste_type_id` và `weight` khác, không cần tạo đơn mới.
