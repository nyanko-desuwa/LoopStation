# USER_STICKERS - Sticker user đang nắm giữ

## Vai trò

Lưu số lượng sticker hiện tại của từng user theo từng loại sticker. 1 dòng = 1 cặp (user, sticker). `quantity` là số đang giữ (giảm khi đổi vật lý), `total_obtained` là tổng tích lũy từ đầu (không giảm - dùng cho thành tích và điều kiện bonus lần đầu).

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `user_id` | int | NOT NULL | - | FK → USERS |
| `sticker_id` | int | NOT NULL | - | FK → STICKERS |
| `quantity` | int | NOT NULL | 0 | Số sticker đang giữ (đã trừ phần đổi vật lý) |
| `total_obtained` | int | NOT NULL | 0 | Tổng từng nhận được từ đầu. Không giảm khi đổi |
| `first_obtained_at` | timestamp | NULL | - | Thời điểm lần đầu có sticker này |
| `last_obtained_at` | timestamp | NULL | - | Thời điểm nhận thêm gần nhất |

## Quan hệ khóa ngoại

| Cột | Trỏ đến | Ý nghĩa |
| --- | --- | --- |
| `user_id` | `USERS.id` | Chủ sở hữu |
| `sticker_id` | `STICKERS.id` | Loại sticker |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| UNIQUE | `(user_id, sticker_id)` | Mỗi user chỉ có 1 dòng/loại sticker |
| IDX | `sticker_id` | Đếm user sở hữu 1 loại sticker |

## Ghi chú nghiệp vụ

- Khi nhận sticker trùng: `quantity += 1`, `total_obtained += 1`, cập nhật `last_obtained_at`.
- Khi đổi vật lý: `quantity -= redeem_quantity_required`. `total_obtained` không đổi.
- Kiểm tra "lần đầu" sở hữu: `total_obtained` chuyển từ 0 → 1 (hoặc dòng chưa tồn tại). Lúc này mới cộng `bonus_points` và mở khóa `unlocks_content_id`.
- Để biết cần thêm bao nhiêu sticker để đổi: `STICKERS.redeem_quantity_required - quantity`.
