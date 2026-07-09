# HANDOVER_WEIGHT_LOGS - Lịch sử cân thực tế

## Vai trò

Lưu từng lần cân thực tế của đơn chuyển giao. Tách riêng khỏi `HANDOVER_REQUESTS` để hỗ trợ **nhiều lần cân** (ví dụ cân lần đầu chưa đủ, thêm đợt sau) và audit đầy đủ: ai cân, lúc nào, đơn vị gì.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `request_id` | int | NOT NULL | - | FK → HANDOVER_REQUESTS. Đơn được cân |
| `weight` | decimal(10,2) | NOT NULL | - | Giá trị cân thực tế |
| `unit_id` | int | NOT NULL | - | FK → MEASUREMENT_UNITS. Đơn vị đo |
| `recorded_by` | int | NOT NULL | - | FK → USERS. Staff thực hiện cân |
| `notes` | text | NULL | - | Ghi chú kèm theo lần cân |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | Thời điểm cân |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `request_id` | `HANDOVER_REQUESTS.id` | Thuộc đơn nào |
| `unit_id` | `MEASUREMENT_UNITS.id` | Đơn vị đo |
| `recorded_by` | `USERS.id` | Staff cân |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `request_id` | Tra tất cả lần cân của 1 đơn |
| IDX | `recorded_by` | Tra lịch sử cân của 1 staff |

## Ghi chú nghiệp vụ

- Bảng append-only - không update/delete sau khi đã ghi.
- Tổng điểm thưởng cho đơn tính dựa trên tổng `weight` từ bảng này, không phải `HANDOVER_REQUESTS.estimated_weight`.
- Nếu cần hiển thị khối lượng cuối cùng, lấy tổng (hoặc bản ghi cuối) từ bảng này.
