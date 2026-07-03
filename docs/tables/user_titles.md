# USER_TITLES - Danh hiệu user đã/đang giữ

## Vai trò

Ghi nhận mỗi lần user đạt được 1 danh hiệu. Danh hiệu có thời hạn: `expires_at` được chốt tại thời điểm cấp. Khi hết hạn, user mất danh hiệu đó và cần đạt lại. Không có job xóa - filter `expires_at > NOW()` để lấy danh hiệu đang active.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS |
| `title_id` | int | NOT NULL | - | FK → TITLE_DEFINITIONS |
| `earned_at` | timestamp | NULL | - | Thời điểm đạt danh hiệu |
| `expires_at` | timestamp | NULL | - | = `earned_at` + `period_days`. Chốt tại thời điểm cấp - không đổi nếu manager sửa `period_days` sau |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | User giữ danh hiệu |
| `title_id` | `TITLE_DEFINITIONS.id` | Loại danh hiệu |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `(user_id, expires_at)` | Danh hiệu đang active của user: `WHERE user_id = X AND expires_at > NOW()` |
| IDX | `title_id` | Đếm user đang giữ 1 loại danh hiệu |

## Ghi chú nghiệp vụ

- Danh hiệu được cấp lại: user đạt lại tiêu chí sau khi hết hạn → tạo dòng mới, không update dòng cũ.
- Cho phép 1 user có nhiều dòng cùng `title_id` (các kỳ khác nhau).
- Danh hiệu hết hạn không bị xóa khỏi DB - lịch sử vẫn giữ, chỉ không hiển thị ở UI khi `expires_at <= NOW()`.
