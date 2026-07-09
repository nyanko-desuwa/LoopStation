# STICKER_REWARD_ITEMS - Danh mục vật phẩm quà đổi sticker

## Vai trò

Danh mục vật phẩm vật lý dùng làm quà khi user đổi sticker ảo. Manager CRUD toàn bộ: tạo vật phẩm với ảnh + tên + tồn kho. Thay cho việc hardcode "kẹo" — manager có thể thêm sticker dán, kẹo, sữa hay bất cứ vật phẩm nào.

## Mô tả cột

| Cột | Kiểu | Null? | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | int | NOT NULL | auto_increment | PK |
| `name` | varchar(150) | NOT NULL | - | Tên vật phẩm (VD: Sticker dán, Kẹo, Sữa). Manager tự đặt, không hardcode |
| `image_url` | varchar(500) | NULL | - | Ảnh vật phẩm. Upload lên server, lưu path tương đối |
| `description` | text | NULL | - | Mô tả vật phẩm |
| `stock` | int | NOT NULL | 0 | Số lượng còn lại trong kho. Manager tự chỉnh, trừ dần khi đổi |
| `status` | varchar(20) | NOT NULL | `'active'` | `active` \| `locked` - locked: tạm ngừng cho đổi |
| `deleted_at` | timestamp | NULL | - | Soft delete. NULL = còn hoạt động |
| `created_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP | - |
| `updated_at` | timestamp | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | - |

## Quan hệ khóa ngoại

**Được tham chiếu bởi:**

| Bảng | Cột | Ý nghĩa |
| --- | --- | --- |
| `STICKER_REWARD_RULES` | `reward_item_id` | Vật phẩm này dùng trong rule nào |
| `STICKER_REDEMPTION_ITEMS` | `reward_item_id` | Vật phẩm gốc trong snapshot đơn (NULL nếu bị xóa) |

## Index

| Index | Cột | Mục đích |
| --- | --- | --- |
| PK | `id` | - |
| IDX | `status` | Lọc active/locked |
| IDX | `deleted_at` | Lọc soft delete |

## Ghi chú nghiệp vụ

- Manager tạo/sửa/xóa vật phẩm và tự điều chỉnh tồn kho (quyền `sticker_reward_item.*`).
- Khi user đổi sticker: hệ thống đọc `STICKER_REWARD_RULES` active của sticker để biết trừ kho những vật phẩm nào.
- `stock` manager chỉnh được bất kỳ lúc nào qua quyền `sticker_reward_item.adjust_stock`.
- Khi bị xóa mềm (`deleted_at` có giá trị): không cho chọn ở luồng đổi mới, nhưng snapshot cũ vẫn giữ được nhờ `STICKER_REDEMPTION_ITEMS`.
