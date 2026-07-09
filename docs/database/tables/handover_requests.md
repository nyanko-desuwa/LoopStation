# HANDOVER_REQUESTS - Đơn chuyển giao rác

## Vai trò

Bảng trung tâm của nghiệp vụ thu hồi rác. Lưu toàn bộ đơn chuyển giao, dùng chung cho **đơn thường** (user đặt trước, hẹn lịch) và **đơn hỏa tốc tại sự kiện** (tạo tức thì khi khách nộp đồ tại event). Phân biệt qua cột `event_id`: NULL = đơn thường, có giá trị = đơn tại sự kiện.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS. User tạo đơn |
| `facility_id` | int | NOT NULL | - | FK → FACILITIES. Cơ sở nhận rác |
| `staff_id` | int | NULL | - | FK → USERS. NULL cho tới khi manager phân công. Chỉ staff cùng `facility_id` với đơn mới được phân công |
| `event_id` | int | NULL | - | FK → EVENTS. NULL = đơn thường; có giá trị = đơn hỏa tốc tại sự kiện |
| `classification_type` | varchar(50) | NULL | - | Hình thức phân loại: `cleaned_flattened` \| `cleaned` \| `as_is` \| `mixed` |
| `estimated_weight` | decimal(10,2) | NULL | - | Khối lượng user ước tính khi tạo đơn |
| `unit_id` | int | NULL | - | FK → MEASUREMENT_UNITS. Đơn vị của `estimated_weight` |
| `appointment_time` | timestamp | NULL | - | Thời gian hẹn đến nộp rác (đơn thường) |
| `expired_at` | timestamp | NULL | - | Mốc auto-cancel nếu user không đến theo lịch |
| `reschedule_count` | int | NOT NULL | 0 | Số lần dời lịch. Tối đa 2; vượt → tự hủy |
| `status` | varchar(30) | NOT NULL | `'pending'` | `pending` \| `approved` \| `completed` \| `rejected` \| `cancelled` \| `expired` |
| `reject_reason` | varchar(500) | NULL | - | Bắt buộc khi `status = rejected` |
| `cancel_reason` | varchar(30) | NULL | - | `user_cancel` \| `staff_cancel` \| `auto_expire` \| `reschedule_exceeded` |
| `notes` | text | NULL | - | Ghi chú tự do |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User tạo đơn |
| `facility_id` | `FACILITIES.id` | Cơ sở nhận rác |
| `staff_id` | `USERS.id` | Staff được phân công xử lý |
| `event_id` | `EVENTS.id` | Sự kiện phát sinh đơn (nếu có) |
| `unit_id` | `MEASUREMENT_UNITS.id` | Đơn vị đo ước tính |

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `HANDOVER_WASTE_ITEMS` | `request_id` | Các dòng loại rác của đơn |
| `HANDOVER_WEIGHT_LOGS` | `request_id` | Các lần cân thực tế của đơn |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, status)` | Lịch sử đơn của user |
| IDX | `(facility_id, appointment_time)` | Lịch nhận tại cơ sở |
| IDX | `staff_id` | Đơn của staff |
| IDX | `event_id` | Đơn theo sự kiện |
| IDX | `status` | Lọc theo trạng thái |

## Vòng đời trạng thái

```
pending → approved → completed
       ↘ rejected
       ↘ cancelled  (user_cancel | staff_cancel | reschedule_exceeded)
       ↘ expired    (auto-cancel khi quá expired_at)
```

## Ghi chú nghiệp vụ

- **Đơn thường**: user tạo → manager/staff duyệt (`approved`) → staff đến lấy → ghi cân → `completed`.
- **Đơn hỏa tốc** (`event_id != NULL`): tạo tức thì ngay tại sự kiện, bỏ qua bước hẹn lịch, `appointment_time` có thể NULL.
- `estimated_weight` là trọng lượng user tự khai - không dùng để tính điểm. Trọng lượng thực tế lấy từ `HANDOVER_WEIGHT_LOGS`.
- Khi `reschedule_count` đạt 2, thao tác đặt lại lịch tiếp theo sẽ tự hủy đơn (`cancel_reason = reschedule_exceeded`).
