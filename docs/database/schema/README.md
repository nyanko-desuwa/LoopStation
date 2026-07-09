# Loop Station - Tài liệu Database Schema

## 1. Tổng quan

Loop Station là hệ thống quản lý thu hồi và tái chế rác cho công ty. Hệ thống quản lý các yêu cầu chuyển giao rác, sự kiện tái chế, hệ thống điểm thưởng, và nội dung giáo dục môi trường.

**Sơ đồ trực quan:** [Xem trên dbdiagram.io](https://dbdiagram.io/d/LOOP-STATION-DATABASE-SCHEMA-6a3a315f5c789b8acbdfd6fa)

## 2. Cấu trúc file

| File | Vai trò |
| --- | --- |
| [schema.dbml](schema.dbml) | File nguồn dbdiagram.io |
| [schema.sql](../../../database/schema/schema.sql) | DDL MariaDB dùng để deploy production |
| [tables/](../tables/) | Tài liệu chi tiết từng bảng |

## 3. Kiến trúc Database

### 3.1. Mô hình nghiệp vụ

Hệ thống quản lý 4 mảng nghiệp vụ chính:

**A. Quản lý người dùng**

- 3 vai trò: User (khách hàng), Staff (nhân viên công ty), manager (chủ cơ sở/quản lý)
- manager và Staff đều thuộc 1 cơ sở cụ thể (USERS.facility_id)
- User (khách hàng) không gắn cơ sở cố định, chọn cơ sở khi tạo đơn thu hồi
- Hỗ trợ user vãng lai tạo tài khoản khi quét QR tại event

**B. Nghiệp vụ thu hồi rác**

- Cơ sở: Trạm thu hồi và văn phòng công ty
- Đơn chuyển giao có 2 loại:
  - Đơn thường: User đặt trước, hẹn lịch, nhân viên đến lấy tại địa chỉ user
  - Đơn tại sự kiện: Tạo tức thì khi khách nộp đồ tại event (khách vãng lai hoặc khách hàng chưa kịp đặt đơn trước). Phân biệt qua event_id (NULL = đơn thường, có giá trị = đơn tại sự kiện)
- Phân công nhân viên theo cơ sở công ty
- User chọn 1 hoặc nhiều loại rác cho 1 đơn từ WASTE_TYPES; nhấn dấu "+" để thêm dòng rác khác vào cùng đơn (ví dụ: thùng giấy 2kg, thêm dòng rác hữu cơ 5kg) chứ không phải tạo loại rác mới trong hệ thống.
- Khối lượng thực tế ghi nhận riêng qua HANDOVER_WEIGHT_LOGS, hỗ trợ nhiều lần cân, đơn vị đo lấy từ MEASUREMENT_UNITS

**C. Sự kiện tái chế**

- Sự kiện "Ngày hội sống xanh" tổ chức tại địa điểm bên ngoài (trường học, công viên, khu dân cư)
- manager tạo sự kiện, việc tạo được ghi vào SYSTEM_LOGS (không lưu manager_id trực tiếp trên EVENTS)
- Phân công staff theo từng sự kiện
- Điểm danh qua QR code
- Minigame nhận quà và tích điểm
- Đăng ký user vãng lai

**D. Tương tác người dùng**

- Hệ thống điểm thưởng: tách riêng bảng cộng điểm (POINT_EARNED) và trừ điểm (POINT_SPENT), không dùng dấu +/- trong 1 cột
- Đổi quà bằng điểm ví (catalog quà và lịch sử đổi). Cho user chọn nhận tại cơ sở (pickup) hoặc ship tận nhà (delivery)
- Nội dung giáo dục kèm quota đọc
- Sticker sưu tập thưởng khi đọc bài giáo dục (vui, hướng tới trẻ em): random theo độ hiếm, đủ số lượng đổi được vật phẩm vật lý (sticker dán, kẹo, sữa...) tại cơ sở hoặc ship tận nhà, sticker hiếm tự cộng điểm/mở khóa nội dung
- Quà đổi sticker do manager cấu hình linh hoạt: tạo danh mục vật phẩm (ảnh + tên + tồn kho) và rule "1 sticker ảo đổi ra bao nhiêu vật phẩm" (VD x1 sticker dán, x2 kẹo, x3 sữa), không hardcode
- Danh hiệu theo kỳ (VD 30 ngày): ghi nhận thành tích luân phiên, tự hết hạn, manager cấu hình tiêu chí
- Mini game tương tác real-time (giáo viên chủ trì, học sinh chơi cùng trên web kiểu Kahoot): generic game_type + config_json để thêm loại game mới không cần đổi schema. Các kiểu ưu tiên gồm quiz, sorting_race, bingo, matching, wheel, guess_image. Thưởng điểm ví cấu hình bật/tắt qua APP_SETTINGS
- Lịch sử giao dịch và ví điểm
- Xác thực qua mật khẩu + link reset / link xác minh email (chuẩn Laravel)

### 3.2. Các quan hệ chính

Schema gồm 39 bảng với các nhóm khóa ngoại chính:

**Gắn User vào Cơ sở**

```
USERS.facility_id => FACILITIES.id
```

Staff và manager thuộc về 1 cơ sở duy nhất. Quyết định staff/manager nào được phân công xử lý đơn hoặc event của cơ sở đó.

**Tổ chức Sự kiện**

```
EVENT_STAFF_ASSIGNMENTS.event_id => EVENTS.id
EVENT_STAFF_ASSIGNMENTS.staff_id => USERS.id
```

manager tạo sự kiện, việc tạo được ghi vào SYSTEM_LOGS (entity_type = event, action = create) thay vì lưu manager_id trên EVENTS. Cơ sở tổ chức suy ra từ facility của manager tạo event. Chỉ staff cùng cơ sở đó mới được phân công làm việc tại sự kiện.

**Luồng đơn chuyển giao**

```
HANDOVER_REQUESTS.user_id => USERS.id
HANDOVER_REQUESTS.facility_id => FACILITIES.id
HANDOVER_REQUESTS.staff_id => USERS.id
HANDOVER_REQUESTS.event_id => EVENTS.id (nullable)
```

- [ ] User tạo đơn và chọn cơ sở để nộp (facility_id). manager chỉ được phân công nhân viên thuộc chính cơ sở đó xử lý đơn: USERS.facility_id = HANDOVER_REQUESTS.facility_id. Nếu đơn phát sinh tại sự kiện, event_id sẽ trỏ về sự kiện đó.

**Loại rác và Cân thực tế**

```
WASTE_TYPES.created_by => USERS.id (nullable)
HANDOVER_WASTE_ITEMS.request_id => HANDOVER_REQUESTS.id
HANDOVER_WASTE_ITEMS.waste_type_id => WASTE_TYPES.id
HANDOVER_WEIGHT_LOGS.request_id => HANDOVER_REQUESTS.id
HANDOVER_WEIGHT_LOGS.recorded_by => USERS.id
```

1 Đơn chuyển giao có thể chọn nhiều Loại rác qua bảng nối HANDOVER_WASTE_ITEMS. User chỉ chọn từ danh sách loại rác manager đã tạo. Khối lượng thực tế tách riêng ở HANDOVER_WEIGHT_LOGS để hỗ trợ nhiều lần cân, audit ai cân, lúc nào, và đơn vị đo lấy từ MEASUREMENT_UNITS.

**Tham gia Sự kiện**

```
EVENT_REGISTRATIONS.event_id => EVENTS.id
EVENT_REGISTRATIONS.user_id => USERS.id
```

Ghi nhận check-in và quyền tham gia minigame. Ràng buộc unique đảm bảo 1 user chỉ đăng ký 1 lần cho mỗi sự kiện.

**Điểm và Quà tặng**

```
USER_WALLETS.user_id => USERS.id (1:1)
POINT_EARNED.wallet_id => USER_WALLETS.id
POINT_SPENT.wallet_id => USER_WALLETS.id
EVENT_REWARDS.event_id => EVENTS.id
```

Mỗi user có 1 ví. Điểm cộng và điểm trừ tách thành 2 bảng riêng (POINT_EARNED, POINT_SPENT), cả hai đều lưu points dương. `balance = SUM(POINT_EARNED.points) - SUM(POINT_SPENT.points)`. Quà tại sự kiện (EVENT_REWARDS) là quà minigame vật lý, không trừ điểm ví.

**Đổi quà bằng điểm ví**

```
REDEMPTIONS.user_id => USERS.id
REDEMPTIONS.reward_id => REWARD_CATALOG.id
REDEMPTIONS.transaction_id => POINT_SPENT.id
REDEMPTIONS.fulfilled_by_id => USERS.id
```

User đổi quà trong REWARD_CATALOG bằng điểm ví. Mỗi lần đổi trừ điểm qua POINT_SPENT (source_type = redemption) và trừ tồn kho REWARD_CATALOG.stock. Đây là luồng khác với quà minigame EVENT_REWARDS.

**Reset mật khẩu**

```
PASSWORD_RESET_TOKENS.email  (PK)
```

Reset mật khẩu bằng link (Laravel Password Broker). User nhập email → nhận link chứa token → bấm link để đặt mật khẩu mới. Đây là kênh reset mật khẩu duy nhất; hệ thống không dùng OTP.

**Xác minh email**

```
USERS.email_verified_at  (set khi user bấm link xác minh)
```

Dùng cơ chế `MustVerifyEmail` chuẩn Laravel: gửi link xác minh, set `email_verified_at = NOW()` khi user bấm link.

**Nội dung Giáo dục**

```
EDUCATIONAL_CONTENTS.author_id => USERS.id
EDUCATIONAL_CONTENTS.approved_by_id => USERS.id
CONTENT_READS.user_id => USERS.id
CONTENT_READS.content_id => EDUCATIONAL_CONTENTS.id
```

Staff soạn bài, manager duyệt. Bảng CONTENT_READS enforce timer 120 giây và quota theo ngày.

**Sticker sưu tập**

```
EDUCATIONAL_CONTENTS.sticker_set_id => STICKER_SETS.id (nullable)
STICKERS.set_id => STICKER_SETS.id
STICKERS.unlocks_content_id => EDUCATIONAL_CONTENTS.id (nullable)
USER_STICKERS.user_id => USERS.id
USER_STICKERS.sticker_id => STICKERS.id
STICKER_OBTAIN_LOGS.user_id => USERS.id
STICKER_OBTAIN_LOGS.sticker_id => STICKERS.id
STICKER_REDEMPTIONS.user_id => USERS.id
STICKER_REDEMPTIONS.facility_id => FACILITIES.id (nullable)
STICKER_REDEMPTIONS.staff_id => USERS.id (nullable)
```

Bài đọc gắn 1 bộ sticker (tùy chọn). Đọc xong bài, hệ thống random 1 sticker trong bộ theo trọng số (drop_weight). USER_STICKERS cộng dồn số lượng khi trùng, STICKER_OBTAIN_LOGS ghi lại từng lần. Đủ số lượng yêu cầu (redeem_quantity_required) thì tạo đơn đổi vật phẩm vật lý (STICKER_REDEMPTIONS), user chọn nhận tại cơ sở (pickup, facility_id + staff_id có giá trị) hoặc ship tận nhà (delivery, facility_id + staff_id để NULL). Độ hiếm cao hơn có thể tự cộng điểm (bonus_points) hoặc mở khóa 1 bài đặc biệt (unlocks_content_id).

**Quà đổi sticker cấu hình động**

```
STICKER_REWARD_RULES.sticker_id => STICKERS.id
STICKER_REWARD_RULES.reward_item_id => STICKER_REWARD_ITEMS.id
STICKER_REDEMPTION_ITEMS.redemption_id => STICKER_REDEMPTIONS.id
STICKER_REDEMPTION_ITEMS.reward_item_id => STICKER_REWARD_ITEMS.id (nullable)
```

manager tạo danh mục vật phẩm quà (STICKER_REWARD_ITEMS: ảnh + tên + tồn kho), không hardcode "kẹo". STICKER_REWARD_RULES định nghĩa "1 sticker ảo đổi ra bao nhiêu vật phẩm" (VD sticker A => x1 sticker dán + x3 kẹo). Khi user đổi, hệ thống đọc rule, tạo STICKER_REDEMPTIONS rồi ghi snapshot từng vật phẩm giao vào STICKER_REDEMPTION_ITEMS (chốt tên + ảnh + số lượng tại thời điểm đổi), nên đổi rule hoặc xóa item về sau không làm sai lệch đơn cũ.

**Mini game real-time**

```
MINI_GAMES.content_id => EDUCATIONAL_CONTENTS.id (nullable)
MINI_GAMES.created_by => USERS.id (nullable)
GAME_SESSIONS.game_id => MINI_GAMES.id
GAME_SESSIONS.host_user_id => USERS.id (nullable)
GAME_PARTICIPANTS.session_id => GAME_SESSIONS.id
GAME_PARTICIPANTS.user_id => USERS.id (nullable)
```

MINI_GAMES là định nghĩa game với game_type + config_json generic (thêm loại game mới không cần đổi schema). Mỗi lượt chơi tạo GAME_SESSIONS có room_code để học sinh vào (kiểu Kahoot), staff/giáo viên chủ trì (host_user_id). GAME_PARTICIPANTS ghi từng người chơi, điểm số, xếp hạng và điểm ví được thưởng (points_awarded). Thưởng điểm ví bật/tắt qua APP_SETTINGS.

**Danh hiệu theo kỳ**

```
USER_TITLES.user_id => USERS.id
USER_TITLES.title_id => TITLE_DEFINITIONS.id
```

manager định nghĩa tiêu chí đạt danh hiệu (criteria_type, threshold, period_days) trong TITLE_DEFINITIONS. Cronjob định kỳ xét và cấp USER_TITLES, mỗi lượt cấp chốt expires_at tại thời điểm đó. Danh hiệu tự hết hiệu lực khi quá expires_at, không cần job xóa.

**Audit Log**

```
SYSTEM_LOGS.performed_by_user_id => USERS.id
```

Mọi thay đổi trạng thái trên đơn chuyển giao, sự kiện, user, nội dung, cơ sở đều được ghi lại kèm trạng thái trước/sau và người thực hiện. 

## 4. Chi tiết từng bảng

### 4.1. FACILITIES

**Mục đích:** Cơ sở/trạm thu hồi và văn phòng công ty.

Đây là các địa điểm cố định do công ty vận hành. User được gắn vào cơ sở để định tuyến đơn chuyển giao. Địa điểm diễn ra sự kiện được ghi trong EVENTS.location, không liên quan tới FACILITIES.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(200) | Tên cơ sở |
| type | ENUM(station, office) | station = trạm thu hồi, office = văn phòng công ty |
| address | VARCHAR(300) | Địa chỉ vật lý |
| latitude | DECIMAL(10,7) | Tọa độ GPS cho lọc theo vị trí |
| longitude | DECIMAL(10,7) | Tọa độ GPS cho lọc theo vị trí |
| image_url | VARCHAR(500) | Ảnh cơ sở |
| status | ENUM(active, locked) | locked sẽ ẩn cơ sở khỏi portal user |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_facilities_status (status)
- idx_facilities_type (type)
- idx_facilities_deleted (deleted_at)

---

### 4.2. USERS

**Mục đích:** Bảng chung cho cả 3 role: User, Staff, manager.

Tất cả actor trong hệ thống dùng chung bảng này. Phân quyền dựa theo role. User vãng lai được tạo tự động khi quét QR sự kiện, hệ thống sinh mật khẩu tạm và tự đăng nhập ngay tại chỗ.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(150) | Họ tên đầy đủ |
| phone | VARCHAR(20) UNIQUE | SĐT liên hệ (tùy chọn). Không dùng cho xác thực |
| email | VARCHAR(150) UNIQUE | Email đăng nhập và nhận thông báo. Kênh liên lạc chính, kể cả user vãng lai |
| email_verified_at | TIMESTAMP NULL | Chuẩn Laravel. Thời điểm email xác minh qua link (`MustVerifyEmail`). NULL = chưa xác minh |
| password | VARCHAR(255) NULL | Hash mật khẩu (cột chuẩn Laravel Auth). User vãng lai luôn có mật khẩu tạm, nhận qua email để dùng cho lần đăng nhập tiếp theo |
| remember_token | VARCHAR(100) NULL | Chuẩn Laravel "remember me" cho web guard |
| avatar_url | VARCHAR(500) NULL | Ảnh đại diện user |
| must_change_password | TINYINT(1) | 1 = đang dùng mật khẩu tạm, buộc đổi ở lần đăng nhập kế tiếp |
| role | ENUM(user, staff, manager) | Vai trò phân quyền |
| facility_id | INT FK => FACILITIES NULL | Cơ sở trực thuộc. Bắt buộc với staff và manager. NULL với role=user |
| is_walk_in | TINYINT(1) | 1 = tài khoản tự sinh qua QR sự kiện |
| status | ENUM(active, locked) | Trạng thái khóa tài khoản |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_users_role (role)
- idx_users_facility (facility_id)
- idx_users_phone (phone)
- idx_users_email (email)
- idx_users_deleted (deleted_at)

**Khóa ngoại:**

- facility_id => FACILITIES.id ON DELETE SET NULL

**Quy tắc nghiệp vụ:**

- facility_id bắt buộc với role = staff và role = manager. NULL với role = user
- manager là chủ 1 cơ sở, chỉ quản lý event/đơn/staff của cơ sở đó
- User vãng lai (is_walk_in = 1) tạo qua QR sự kiện: hệ thống sinh mật khẩu tạm, gửi qua email, đặt must_change_password = 1
- Đăng nhập bằng mật khẩu (`login_method = password`) hoặc walk-in auto-login. Không hỗ trợ OTP login
- Reset mật khẩu bằng link (PASSWORD_RESET_TOKENS). Xác minh email bằng link (`MustVerifyEmail`)
- **Tương thích Laravel Auth**: dùng đúng tên cột `password`, `email_verified_at`, `remember_token` để Authenticatable contract và middleware Auth mặc định hoạt động không cần override.

---

### 4.3. EVENTS

**Mục đích:** Quản lý vòng đời sự kiện tái chế.

Sự kiện là hoạt động "Ngày hội sống xanh" tổ chức tại địa điểm bên ngoài như trường học, công viên, khu dân cư. manager tạo sự kiện; hành động tạo được ghi vào SYSTEM_LOGS thay vì lưu trực tiếp trên EVENTS. Cơ sở tổ chức không lưu trên bảng này, được suy ra từ facility của manager đã tạo (tra qua SYSTEM_LOGS => USERS.facility_id).

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| title | VARCHAR(200) | Tên sự kiện |
| description | TEXT | Mô tả chi tiết |
| location | VARCHAR(300) NOT NULL | Địa chỉ địa điểm bên ngoài (trường, công viên...) |
| qr_code | VARCHAR(100) UNIQUE | Mã QR định danh. Chỉ active trong khung giờ sự kiện (start_time đến end_time) |
| image_url | VARCHAR(500) NULL | Ảnh poster/banner sự kiện |
| start_time | TIMESTAMP | Thời gian bắt đầu |
| end_time | TIMESTAMP | Thời gian kết thúc |
| expired_at | TIMESTAMP | Mốc cronjob auto-clean |
| status | ENUM(upcoming, active, ended, cancelled) | Trạng thái vòng đời |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_events_status (status, start_time) - phục vụ query "Sắp tới" và "Đang diễn ra"
- idx_events_deleted (deleted_at)

**Quy tắc nghiệp vụ:**

- QR code chỉ cho check-in khi NOW() BETWEEN start_time AND end_time (enforce ở tầng ứng dụng)
- Khi tạo event: insert SYSTEM_LOGS (entity_type = 'event', entity_id = EVENTS.id, action = 'create', performed_by_user_id = manager_id)
- Cơ sở tổ chức = facility của manager đã tạo event (query SYSTEM_LOGS để lấy manager, rồi lấy USERS.facility_id)
- Chỉ staff có facility trùng với facility tổ chức mới được phân công (enforce ở tầng ứng dụng)

---

### 4.4. HANDOVER_REQUESTS

**Mục đích:** Vòng đời đơn chuyển giao rác từ lúc tạo đến hoàn thành.

Đây là bảng trung tâm của luồng nghiệp vụ. Dùng chung cho đơn thường (đăng ký hẹn giờ) và đơn hỏa tốc (tạo tại sự kiện). Loại rác, giá trị đo và đơn vị đo được tách ra bảng phụ (xem 4.5 WASTE_TYPES, 4.6 HANDOVER_WASTE_ITEMS, 4.7 HANDOVER_WEIGHT_LOGS, 4.8 MEASUREMENT_UNITS).

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | Người tạo đơn |
| facility_id | INT FK => FACILITIES | Cơ sở tiếp nhận |
| staff_id | INT FK => USERS NULL | Nhân viên xử lý. NULL cho tới khi manager phân công. Chỉ staff cùng cơ sở với đơn (USERS.facility_id = facility_id) mới được phân công |
| event_id | INT FK => EVENTS NULL | NULL với đơn thường. Có giá trị với đơn hỏa tốc tại sự kiện |
| classification_type | VARCHAR(50) | Hình thức phân loại: cleaned_flattened, cleaned, as_is, mixed |
| estimated_weight | DECIMAL(10,2) | Giá trị đo ước tính của user |
| unit_id | INT FK => MEASUREMENT_UNITS | Đơn vị đo của estimated_weight. manager quản lý danh sách đơn vị đo, user chỉ chọn từ danh sách có sẵn |
| appointment_time | TIMESTAMP | Thời gian hẹn |
| expired_at | TIMESTAMP | Mốc auto-cancel nếu user không đến |
| reschedule_count | INT | Số lần dời lịch. Tối đa 2, vượt sẽ auto-cancel |
| status | ENUM(pending, approved, completed, rejected, cancelled, expired) | Trạng thái đơn |
| reject_reason | VARCHAR(500) | Bắt buộc khi status = rejected |
| cancel_reason | ENUM(user_cancel, staff_cancel, auto_expire, reschedule_exceeded) | Lý do hủy |
| notes | TEXT | Ghi chú của user |
| created_at | TIMESTAMP | Thời điểm tạo đơn |
| updated_at | TIMESTAMP | Thời điểm đổi trạng thái lần cuối |

**Chỉ mục:**

- idx_handover_user_status (user_id, status) - lịch sử đơn của user
- idx_handover_facility_time (facility_id, appointment_time) - lịch làm việc của cơ sở
- idx_handover_staff (staff_id)
- idx_handover_event (event_id)
- idx_handover_status (status)

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE
- facility_id => FACILITIES.id ON DELETE RESTRICT
- staff_id => USERS.id ON DELETE SET NULL
- event_id => EVENTS.id ON DELETE SET NULL

**Chuyển trạng thái:**

```
pending => approved => completed
        => rejected
        => cancelled (user_cancel, staff_cancel, reschedule_exceeded)
        => expired (auto_expire)
```

---

### 4.5. WASTE_TYPES

**Mục đích:** Danh mục loại rác cho User chọn khi tạo đơn.

Thay cho việc lưu waste_type dạng chữ tự do trên HANDOVER_REQUESTS. manager tạo và quản lý danh sách phân loại gốc (`is_system = 1`). User chỉ được chọn từ danh sách manager đã thêm. Đơn vị đo cũng được quản lý qua bảng riêng `MEASUREMENT_UNITS`.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(100) | Tên loại rác |
| icon | VARCHAR(50) NULL | Icon/emoji hiển thị (tùy chọn) |
| is_system | TINYINT(1) | 1 = phân loại gốc do manager tạo, 0 = loại do User thêm (nếu tính năng được mở) |
| created_by | INT FK => USERS NULL | manager đã thêm loại rác này. NULL nếu là phân loại gốc |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |

**Chỉ mục:**

- idx_waste_types_system (is_system)
- idx_waste_types_deleted (deleted_at)

**Khóa ngoại:**

- created_by => USERS.id ON DELETE SET NULL

**Ghi chú:** User chọn phân loại trong lúc tạo đơn chuyển giao; chỉ nhập giá trị đo và chọn đơn vị từ danh sách `MEASUREMENT_UNITS` cho từng loại ở bảng `HANDOVER_WASTE_ITEMS`.

**Dữ liệu mẫu (is_system = 1):** milk_carton (vỏ hộp sữa), recyclable (tái chế chung), old_item (đồ cũ), plastic (nhựa), electronic (điện tử), textile (vải), glass (thủy tinh), metal (kim loại), battery (pin), organic (hữu cơ), other (khác)

**Lưu ý:** manager quản lý danh sách đơn vị đo ở bảng `MEASUREMENT_UNITS`; user chỉ chọn từ danh sách có sẵn khi nhập `estimated_weight`, `weight` hoặc ghi cân thực tế.

---

### 4.6. HANDOVER_WASTE_ITEMS

**Mục đích:** Quan hệ nhiều-nhiều giữa Đơn chuyển giao và Loại rác.

Cho phép 1 đơn chọn nhiều loại rác cùng lúc (ví dụ: vừa nộp vỏ hộp sữa vừa nộp đồ điện tử trong 1 lần). Mỗi loại rác User nhập giá trị đo thực tế cùng đơn vị đo lấy từ `MEASUREMENT_UNITS`.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| request_id | INT FK => HANDOVER_REQUESTS | Đơn chuyển giao |
| waste_type_id | INT FK => WASTE_TYPES | Loại rác được chọn |
| weight | DECIMAL(10,2) | Giá trị đo do User nhập |
| unit_id | INT FK => MEASUREMENT_UNITS | Đơn vị đo của weight. User chỉ chọn từ danh sách manager đã thêm |
| created_at | TIMESTAMP | Thời điểm chọn |

**Ràng buộc unique:** (request_id, waste_type_id) - không chọn trùng loại rác trong 1 đơn

**Chỉ mục:**

- idx_waste_items_type (waste_type_id)

**Khóa ngoại:**

- request_id => HANDOVER_REQUESTS.id ON DELETE CASCADE
- waste_type_id => WASTE_TYPES.id ON DELETE RESTRICT

---

### 4.7. HANDOVER_WEIGHT_LOGS

**Mục đích:** Lịch sử cân thực tế của Đơn chuyển giao.

Tách khỏi HANDOVER_REQUESTS.actual_weight cũ để hỗ trợ cân nhiều lần và audit rõ ai cân, lúc nào, ghi chú gì.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| request_id | INT FK => HANDOVER_REQUESTS | Đơn được cân |
| weight | DECIMAL(10,2) | Giá trị đo thực tế khi cân |
| unit_id | INT FK => MEASUREMENT_UNITS | Đơn vị đo của weight. manager quản lý danh sách đơn vị, user chỉ chọn có sẵn |
| recorded_by | INT FK => USERS | Staff thực hiện cân |
| notes | TEXT NULL | Ghi chú khi cân |
| created_at | TIMESTAMP | Thời điểm cân |

**Chỉ mục:**

- idx_weight_logs_request (request_id)
- idx_weight_logs_staff (recorded_by)

**Khóa ngoại:**

- request_id => HANDOVER_REQUESTS.id ON DELETE CASCADE
- recorded_by => USERS.id ON DELETE RESTRICT

---

### 4.8. MEASUREMENT_UNITS

**Mục đích:** Danh mục đơn vị đo do manager quản lý.

User chỉ chọn từ danh sách có sẵn khi nhập giá trị đo trên đơn chuyển giao hoặc lúc cân thực tế. manager tạo/thêm/sửa/xóa đơn vị đo mà không cần sửa schema.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(50) | Tên đơn vị hiển thị (VD: kilogram, lít,igram) |
| symbol | VARCHAR(20) | Ký hiệu đơn vị (VD: kg, g, mg, l, ml, ...) |
| category | VARCHAR(30) | Nhóm: weight (khối lượng), volume (thể tích), count (đếm) |
| is_system | TINYINT(1) | 1 = đơn vị gốc do manager tạo |
| created_by | INT FK => USERS NULL | manager đã tạo đơn vị. NULL nếu là đơn vị gốc |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |

**Chỉ mục:**

- idx_measurement_units_category (category)
- idx_measurement_units_system (is_system)
- idx_measurement_units_deleted (deleted_at)

**Khóa ngoại:**

- created_by => USERS.id ON DELETE SET NULL

**Dữ liệu mẫu (is_system = 1):**
- Nhóm weight: g (gam), kg (kilogram), ton (tấn)
- Nhóm volume: ml (mililit), l (lít)
- Nhóm count: piece (cái), box (hộp), bag (túi)

**Lưu ý:** Danh sách đơn vị đo được manager quản lý tập trung. HANDOVER_REQUESTS, HANDOVER_WASTE_ITEMS, HANDOVER_WEIGHT_LOGS đều dùng `unit_id` FK tới bảng này.

---

### 4.9. EVENT_STAFF_ASSIGNMENTS

**Mục đích:** Quan hệ nhiều-nhiều giữa sự kiện và staff.

Một sự kiện có thể phân công nhiều staff. Một staff có thể được phân cho nhiều sự kiện, nhưng **không được overlap thời gian**. Nghĩa là 1 staff chỉ được quản lý 1 event tại 1 thời điểm.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| event_id | INT FK => EVENTS | Sự kiện được phân |
| staff_id | INT FK => USERS | Staff được phân công |
| assigned_at | TIMESTAMP | Thời điểm phân công |

**Ràng buộc unique:** (event_id, staff_id)

**Chỉ mục:**

- idx_staff_events (staff_id) - query lịch của staff

**Khóa ngoại:**

- event_id => EVENTS.id ON DELETE CASCADE
- staff_id => USERS.id ON DELETE CASCADE

**Quy tắc nghiệp vụ:**

- Chỉ user có role = staff mới được phân công
- Staff.facility_id phải bằng facility của manager đã tạo Event (tra qua SYSTEM_LOGS, enforce ở tầng ứng dụng)
- Không được phân công staff vào 2 event có khoảng thời gian giao nhau. Database chặn bằng trigger trên EVENT_STAFF_ASSIGNMENTS, so sánh `EVENTS.start_time` và `EVENTS.end_time`

---

### 4.10. EVENT_REWARDS

**Mục đích:** Quà vật lý tại sự kiện cho người thắng minigame.

Quà là vật phẩm thật (áo thun, cây xanh, voucher). Khi thắng minigame, số remaining giảm đi 1. Quà không đổi bằng điểm.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| event_id | INT FK => EVENTS | Sự kiện cha |
| name | VARCHAR(150) | Tên quà |
| description | TEXT | Mô tả quà |
| quantity | INT | Số lượng ban đầu |
| remaining | INT | Số lượng còn lại. Giảm dần khi user trúng minigame |
| created_at | TIMESTAMP | Thời điểm tạo |

**Chỉ mục:**

- idx_rewards_event (event_id)

**Khóa ngoại:**

- event_id => EVENTS.id ON DELETE CASCADE

**Lưu ý quan trọng:** Quà không trừ điểm trong USER_WALLETS. Đây là phần thưởng miễn phí cho người tham gia minigame.

---

### 4.11. EVENT_REGISTRATIONS

**Mục đích:** Theo dõi tham gia sự kiện và trạng thái check-in.

Ghi nhận việc user đăng ký sự kiện. Theo dõi thời gian check-in, trạng thái điểm danh và quyền chơi minigame. Một user chỉ đăng ký 1 lần cho mỗi sự kiện.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| event_id | INT FK => EVENTS | Sự kiện tham gia |
| user_id | INT FK => USERS | User tham gia |
| registration_type | ENUM(visit, handover, walkin) | visit = user đã có tk, đăng ký tham quan tìm hiểu (không nộp đồ); handover = đăng ký nộp đồ tại event; walkin = khách vãng lai tạo tk qua QR tại chỗ |
| status | ENUM(registered, attended, absent) | Trạng thái điểm danh: registered = đã đăng ký chưa đến, attended = đã check-in, absent = vắng mặt |
| minigame_status | ENUM(not_eligible, unlocked, played) | Trạng thái minigame: not_eligible = chưa đủ điều kiện, unlocked = đã mở khóa, played = đã chơi |
| checked_in_at | TIMESTAMP NULL | Thời điểm user quét QR check-in. NULL nếu chưa check-in |
| created_at | TIMESTAMP | Thời điểm đăng ký |

**Ràng buộc unique:** (event_id, user_id) - 1 đăng ký/user/sự kiện

**Chỉ mục:**

- idx_registration_user (user_id)

**Khóa ngoại:**

- event_id => EVENTS.id ON DELETE CASCADE
- user_id => USERS.id ON DELETE CASCADE

**Luồng trạng thái:**

```
Quét QR => registered (checked_in_at = NULL)
Check-in => attended (checked_in_at = NOW())
Không đến => absent
```

**Luồng minigame:**

```
not_eligible => unlocked (sau khi hoàn tất nộp đồ) => played
```

**Lưu ý quan trọng:** Chỉ user có tài khoản (đăng ký visit/handover) và khách vãng lai (walkin) mới có bản ghi trong bảng này. Khách chỉ đi ngang không quét QR, không đăng ký thì không được ghi nhận.

---

### 4.12. USER_WALLETS

**Mục đích:** Số dư điểm của từng user.

Quan hệ 1-1 với USERS. Lưu số dư điểm hiện tại. Mọi thay đổi điểm được ghi trong POINT_EARNED (cộng) hoặc POINT_SPENT (trừ).

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS UNIQUE | Chủ ví (quan hệ 1:1) |
| balance | INT | Số điểm hiện tại |
| updated_at | TIMESTAMP | Thời điểm thay đổi số dư gần nhất |

**Ràng buộc unique:** user_id

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE

**Công thức:** `balance = SUM(POINT_EARNED.points) - SUM(POINT_SPENT.points)`

---

### 4.13. POINT_EARNED

**Mục đích:** Log bất biến cho mọi lần cộng điểm.

Mỗi lần cộng điểm tạo 1 dòng, points luôn dương. Tách riêng khỏi việc trừ điểm (xem POINT_SPENT) để tránh nhầm lẫn dấu +/- khi code.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| wallet_id | INT FK => USER_WALLETS | Ví được cộng điểm |
| points | INT | Số điểm kiếm được. Luôn dương |
| source_type | ENUM(handover, event_minigame, content_read, manager_adjust, redemption_refund, sticker_bonus) | Nguồn phát sinh |
| reference_id | INT NULL | ID của bản ghi nguồn: HANDOVER_REQUESTS.id, EVENT_REGISTRATIONS.id, CONTENT_READS.id, REDEMPTIONS.id, STICKERS.id. NULL với manager_adjust |
| description | VARCHAR(300) NULL | Mô tả hiển thị cho user |
| created_at | TIMESTAMP | Thời điểm giao dịch |

**Chỉ mục:**

- idx_earned_wallet (wallet_id, created_at) - lịch sử cộng điểm của ví
- idx_earned_source (source_type, reference_id) - query đối soát

**Khóa ngoại:**

- wallet_id => USER_WALLETS.id ON DELETE CASCADE

**Các nguồn điểm:**

- **handover:** Điểm từ hoàn tất đơn chuyển giao
- **event_minigame:** Điểm thưởng thêm khi chơi minigame tại sự kiện (khác với quà vật lý)
- **content_read:** Điểm từ đọc bài giáo dục
- **manager_adjust:** Điều chỉnh cộng điểm thủ công của manager
- **redemption_refund:** Hoàn điểm khi 1 REDEMPTIONS bị hủy sau khi đã trừ điểm
- **sticker_bonus:** Điểm tự cộng khi lần đầu sở hữu 1 loại sticker có bonus_points > 0 (reference_id = STICKERS.id)

**Lưu ý quan trọng:** Quà vật lý tại sự kiện (EVENT_REWARDS) không tạo giao dịch cộng điểm. User nhận quà mà không tốn điểm.

---

### 4.14. POINT_SPENT

**Mục đích:** Log bất biến cho mọi lần trừ điểm.

Mỗi lần trừ điểm tạo 1 dòng, points luôn dương (biểu thị số điểm bị trừ, không dùng số âm).

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| wallet_id | INT FK => USER_WALLETS | Ví bị trừ điểm |
| points | INT | Số điểm đã tiêu/bị trừ. Luôn dương |
| source_type | ENUM(redemption, manager_adjust) | Nguồn phát sinh |
| reference_id | INT NULL | ID của REDEMPTIONS tương ứng. NULL với manager_adjust |
| description | VARCHAR(300) NULL | Mô tả hiển thị cho user |
| created_at | TIMESTAMP | Thời điểm giao dịch |

**Chỉ mục:**

- idx_spent_wallet (wallet_id, created_at) - lịch sử trừ điểm của ví
- idx_spent_source (source_type, reference_id) - query đối soát

**Khóa ngoại:**

- wallet_id => USER_WALLETS.id ON DELETE CASCADE

**Các nguồn điểm:**

- **redemption:** Trừ điểm khi user đổi quà từ REWARD_CATALOG (reference_id = REDEMPTIONS.id)
- **manager_adjust:** Điều chỉnh trừ điểm thủ công của manager

---

### 4.15. REWARD_CATALOG

**Mục đích:** Danh mục quà có thể đổi bằng điểm ví.

Khác với EVENT_REWARDS (quà minigame vật lý tại sự kiện, không tốn điểm). REWARD_CATALOG là kho quà chung, user dùng điểm trong ví để đổi. Trừ tồn kho khi đổi thành công.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(150) | Tên quà |
| description | TEXT | Mô tả quà |
| image_url | VARCHAR(500) | Ảnh quà |
| points_cost | INT | Số điểm cần để đổi 1 phần |
| stock | INT | Số lượng còn lại. Trừ dần khi user đổi |
| status | ENUM(active, locked) | locked = tạm ngừng cho đổi |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_catalog_status (status)
- idx_catalog_deleted (deleted_at)

**Quy tắc nghiệp vụ:**

- Chỉ hiển thị quà có status = active AND deleted_at IS NULL AND stock > 0
- Khi user đổi: kiểm tra balance >= points_cost, giảm stock, tạo REDEMPTIONS + POINT_SPENT

---

### 4.16. REDEMPTIONS

**Mục đích:** Lịch sử đổi quà bằng điểm ví.

Mỗi lần user đổi quà tạo 1 dòng. Chốt lại số điểm đã trừ tại thời điểm đổi (phòng khi points_cost trên catalog thay đổi sau này). Liên kết ngược tới POINT_SPENT để đối soát.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | User đổi quà |
| reward_id | INT FK => REWARD_CATALOG | Quà được đổi |
| points_spent | INT | Số điểm đã trừ tại thời điểm đổi (snapshot của points_cost) |
| quantity | INT | Số phần quà đổi trong 1 lần (mặc định 1) |
| status | ENUM(pending, shipping, fulfilled, cancelled) | pending = chờ nhận, shipping = đang giao (ship tận nhà), fulfilled = đã giao, cancelled = hủy và hoàn điểm |
| fulfillment_method | ENUM(pickup, delivery) | pickup = nhận tại cơ sở, delivery = ship tận nhà. Mặc định pickup |
| recipient_name | VARCHAR(150) NULL | Tên người nhận khi ship. NULL nếu pickup |
| recipient_phone | VARCHAR(20) NULL | SĐT người nhận khi ship. NULL nếu pickup |
| shipping_address | VARCHAR(500) NULL | Địa chỉ giao hàng khi ship. NULL nếu pickup |
| shipping_note | VARCHAR(300) NULL | Ghi chú giao hàng (tùy chọn) |
| transaction_id | INT FK => POINT_SPENT NULL | Giao dịch trừ điểm tương ứng |
| fulfilled_by_id | INT FK => USERS NULL | Staff/manager xác nhận giao quà |
| created_at | TIMESTAMP | Thời điểm đổi |
| updated_at | TIMESTAMP | Thời điểm cập nhật trạng thái gần nhất |

**Chỉ mục:**

- idx_redemptions_user (user_id, status)
- idx_redemptions_reward (reward_id)
- idx_redemptions_transaction (transaction_id)
- idx_redemptions_method (fulfillment_method)

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE
- reward_id => REWARD_CATALOG.id ON DELETE RESTRICT
- transaction_id => POINT_SPENT.id ON DELETE SET NULL
- fulfilled_by_id => USERS.id ON DELETE SET NULL

**Chuyển trạng thái:**

```
pickup:   pending => fulfilled (staff/manager xác nhận giao quà tại cơ sở)
delivery: pending => shipping (bắt đầu giao) => fulfilled (đã giao tới tay user)
          => cancelled (hủy: hoàn điểm qua POINT_EARNED đối ứng, cộng lại stock)
```

**Quy tắc nghiệp vụ:**

- Điểm trừ ngay khi tạo REDEMPTIONS (status = pending), không đợi fulfilled: insert POINT_SPENT (source_type = redemption)
- Nếu cancelled sau khi đã trừ điểm: insert POINT_EARNED (source_type = redemption_refund) để hoàn điểm
- fulfillment_method = delivery bắt buộc có recipient_name, recipient_phone, shipping_address. pickup thì các cột này để NULL
- Đơn ship đi qua trạng thái shipping trước khi fulfilled; đơn pickup có thể nhảy thẳng pending => fulfilled

---

### 4.17. PASSWORD_RESET_TOKENS

**Mục đích:** Bảng chuẩn Laravel cho Password Broker — kênh đặt lại mật khẩu duy nhất (reset bằng link).

User quên mật khẩu → nhập email → nhận link chứa token qua email → bấm link để đặt mật khẩu mới. Hệ thống không dùng OTP. Mỗi email chỉ giữ 1 token mới nhất (email là PK), token lưu dạng hash.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| email | VARCHAR(150) PK | Email yêu cầu reset. Mỗi email giữ token mới nhất |
| token | VARCHAR(255) | Hash token reset gửi qua link (không lưu plaintext) |
| created_at | TIMESTAMP NULL | Thời điểm phát hành, dùng tính hết hạn theo config auth.passwords (mặc định 60 phút) |

**Chỉ mục:**

- PRIMARY KEY (email)

**Khóa ngoại:** Không có — dùng email làm khóa tự nhiên chuẩn Laravel.

**Quy tắc nghiệp vụ:**

- Upsert theo email: mỗi lần yêu cầu reset ghi đè token cũ, chỉ token gần nhất còn hiệu lực
- Dùng 1 lần: reset thành công thì xóa dòng token ngay
- Hết hạn tự động: Laravel bỏ qua token quá `config('auth.passwords.users.expire')`; có thể chạy `php artisan auth:clear-resets` dọn rác
- Reset thành công: cập nhật USERS.password, hạ must_change_password = 0, revoke toàn bộ USER_SESSIONS với revoke_reason = password_change
- Chi tiết luồng: xem mục 5.9

---

### 4.18. EDUCATIONAL_CONTENTS

**Mục đích:** Bài viết giáo dục môi trường.

Staff soạn nội dung, manager duyệt để xuất bản. Chỉ bài có status = published mới hiển thị cho user. Nội dung dạng HTML (rich text), ảnh minh họa embed trực tiếp trong content qua thẻ `<img>`. Ảnh bìa thumbnail tách riêng ở thumbnail_url.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| title | VARCHAR(200) | Tiêu đề bài |
| content | TEXT | Nội dung dạng HTML (rich text). Ảnh minh họa embed qua `<img src="...">` |
| author_id | INT FK => USERS | Staff soạn bài |
| approved_by_id | INT FK => USERS NULL | manager duyệt bài. NULL nếu chưa duyệt |
| thumbnail_url | VARCHAR(500) NULL | Ảnh bìa/thumbnail của bài học. NULL nếu không có |
| status | ENUM(pending, published, rejected) | Trạng thái duyệt |
| timer_seconds | INT | Thời gian đọc tối thiểu để nhận điểm (mặc định 120 giây) |
| points_reward | INT | Điểm cộng khi hoàn thành |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_content_status (status)
- idx_content_author (author_id)
- idx_content_deleted (deleted_at)

**Khóa ngoại:**

- author_id => USERS.id ON DELETE RESTRICT
- approved_by_id => USERS.id ON DELETE SET NULL

**Luồng xuất bản:**

```
pending => published (manager duyệt)
        => rejected (manager từ chối)
```

---

### 4.19. CONTENT_READS

**Mục đích:** Theo dõi phiên đọc để enforce timer và quota.

Ghi lại mỗi lần user bắt đầu đọc bài. Enforce thời gian đọc tối thiểu 120 giây và quota theo ngày. Mỗi content được cộng điểm tối đa 2 lần/ngày, tổng toàn hệ thống tối đa 10 lần/ngày cho mỗi user.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | Người đọc |
| content_id | INT FK => EDUCATIONAL_CONTENTS | Bài được đọc |
| started_at | TIMESTAMP | Thời điểm bắt đầu phiên đọc |
| completed_at | TIMESTAMP NULL | Thời điểm kết thúc. NULL nếu chưa đủ timer |
| rewarded | TINYINT(1) | 1 nếu đã cộng điểm, 0 nếu chưa |
| read_date | DATE | Ngày đọc theo local time để reset quota lúc 00:00 |

**Chỉ mục:**

- idx_reads_user_date (user_id, read_date) - query quota ngày của user
- idx_reads_content (content_id)
- idx_reads_content_date (content_id, read_date) - query quota theo content trong ngày

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE
- content_id => EDUCATIONAL_CONTENTS.id ON DELETE CASCADE

**Luồng đọc:**

1. User mở bài => Tạo bản ghi với started_at = NOW(), completed_at = NULL, read_date = CURRENT_DATE
2. User đọc đủ 120+ giây => Cập nhật completed_at = NOW()
3. Cộng điểm => Đặt rewarded = 1, insert vào POINT_EARNED (source_type = content_read)
4. Nếu user thoát trước khi đủ timer => không set completed_at, không rewarded, không cộng điểm

**Enforce quota:**

```sql
SELECT COUNT(*) FROM CONTENT_READS
WHERE user_id = ? AND read_date = CURRENT_DATE AND rewarded = 1
```

Nếu count >= 10, không cộng điểm thêm trong ngày đó.

```sql
SELECT COUNT(*) FROM CONTENT_READS
WHERE user_id = ? AND content_id = ? AND read_date = CURRENT_DATE AND rewarded = 1
```

Nếu count >= 2, không cộng điểm thêm cho content đó trong ngày đó.

---

### 4.20. SYSTEM_LOGS

**Mục đích:** Audit trail cho mọi thay đổi trạng thái trong hệ thống.

Ghi lại mọi chỉnh sửa trên đơn chuyển giao, sự kiện, user, nội dung, và cơ sở. Lưu người thực hiện, trạng thái trước/sau, và payload chi tiết. Đây cũng là nơi duy nhất ghi nhận manager đã tạo 1 sự kiện (thay cho cột manager_id đã bỏ khỏi EVENTS).

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| entity_type | VARCHAR(30) | Loại đối tượng: handover, event, user, content, facility |
| entity_id | INT | ID của đối tượng bị tác động |
| action | VARCHAR(50) | Hành động: create, approve, reject, reschedule, complete, cancel... |
| old_status | VARCHAR(30) | Trạng thái trước khi đổi |
| new_status | VARCHAR(30) | Trạng thái sau khi đổi |
| details | TEXT | Payload JSON chi tiết thay đổi |
| performed_by_user_id | INT FK => USERS NULL | User thực hiện hành động. NULL với action tự động của hệ thống |
| created_at | TIMESTAMP | Thời điểm log |

**Chỉ mục:**

- idx_logs_entity (entity_type, entity_id) - query lịch sử của đối tượng
- idx_logs_user (performed_by_user_id) - audit hoạt động của user
- idx_logs_created (created_at) - query theo thời gian

**Khóa ngoại:**

- performed_by_user_id => USERS.id ON DELETE SET NULL

**Ứng dụng:**

- Audit trail cho tuân thủ
- Debug các vấn đề chuyển trạng thái
- Giám sát hoạt động user
- Hỗ trợ rollback
- Truy vết manager đã tạo 1 sự kiện: query entity_type = 'event', entity_id = EVENTS.id, action = 'create'

---

### 4.21. STICKER_SETS

**Mục đích:** Bộ sưu tập sticker, manager CRUD toàn bộ.

Nhóm các sticker theo chủ đề (VD: Đại dương, Rừng xanh). Gắn vào EDUCATIONAL_CONTENTS.sticker_set_id để chỉ định bài nào thưởng sticker từ bộ nào.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(150) | Tên bộ sưu tập |
| theme | VARCHAR(100) NULL | Chủ đề mô tả thêm |
| cover_image_url | VARCHAR(500) NULL | Ảnh đại diện bộ |
| status | ENUM(active, locked) | locked = tạm ẩn, không rơi sticker từ bộ này |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_sticker_sets_status (status)
- idx_sticker_sets_deleted (deleted_at)

---

### 4.22. STICKERS

**Mục đích:** Từng sticker trong 1 bộ sưu tập, manager CRUD toàn bộ.

Mỗi sticker có độ hiếm và trọng số rơi riêng - manager tự cấu hình qua drop_weight. Độ hiếm cao hơn thường đi kèm bonus_points cao hơn hoặc mở khóa nội dung đặc biệt.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| set_id | INT FK => STICKER_SETS | Thuộc bộ sưu tập nào |
| name | VARCHAR(150) | Tên sticker |
| image_url | VARCHAR(500) | Ảnh sticker |
| rarity | ENUM(common, rare, special) | Độ hiếm |
| drop_weight | INT | Trọng số rơi. manager tự chỉnh, tỉ lệ = drop_weight / tổng drop_weight của các sticker active trong bộ |
| redeem_quantity_required | INT | Cần đủ bao nhiêu cái mới đổi được 1 lần vật lý (mặc định 1) |
| bonus_points | INT | Điểm tự cộng vào POINT_EARNED khi LẦN ĐẦU sở hữu loại này (mặc định 0) |
| unlocks_content_id | INT FK => EDUCATIONAL_CONTENTS NULL | Mở khóa bài đặc biệt khi lần đầu sở hữu. NULL = không có |
| status | ENUM(active, locked) | locked = tạm ẩn khỏi vòng rơi |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_stickers_set (set_id)
- idx_stickers_rarity (rarity)
- idx_stickers_unlocks (unlocks_content_id)
- idx_stickers_deleted (deleted_at)

**Khóa ngoại:**

- set_id => STICKER_SETS.id ON DELETE CASCADE
- unlocks_content_id => EDUCATIONAL_CONTENTS.id ON DELETE SET NULL

---

### 4.23. USER_STICKERS

**Mục đích:** Số lượng sticker mỗi user đang giữ, theo từng loại.

1 dòng / user / loại sticker. Cộng dồn khi nhận trùng (không tạo dòng mới), tách quantity (đang giữ, giảm khi đổi vật lý) và total_obtained (tổng từng nhận, không giảm - dùng hiển thị thành tích).

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | Chủ sở hữu |
| sticker_id | INT FK => STICKERS | Loại sticker |
| quantity | INT | Số đang giữ (đã trừ phần đã đổi vật lý) |
| total_obtained | INT | Tổng từng nhận được, không giảm khi đổi |
| first_obtained_at | TIMESTAMP | Lần đầu có |
| last_obtained_at | TIMESTAMP | Lần gần nhất có thêm |

**Ràng buộc unique:** (user_id, sticker_id)

**Chỉ mục:**

- idx_user_stickers_sticker (sticker_id)

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE
- sticker_id => STICKERS.id ON DELETE CASCADE

---

### 4.24. STICKER_OBTAIN_LOGS

**Mục đích:** Lịch sử mỗi lần nhận sticker, kể cả trùng.

Khác USER_STICKERS (số lượng hiện tại), bảng này giữ từng lần nhận riêng lẻ - phục vụ tính danh hiệu theo kỳ (VD đếm số sticker nhận trong 30 ngày) và đối soát nguồn gốc.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | Người nhận |
| sticker_id | INT FK => STICKERS | Sticker nhận được |
| source_content_id | INT FK => EDUCATIONAL_CONTENTS NULL | Bài đọc đã ra sticker này. NULL nếu từ nguồn khác |
| created_at | TIMESTAMP | Thời điểm nhận |

**Chỉ mục:**

- idx_obtain_logs_user (user_id, created_at)
- idx_obtain_logs_sticker (sticker_id)
- idx_obtain_logs_content (source_content_id)

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE
- sticker_id => STICKERS.id ON DELETE CASCADE
- source_content_id => EDUCATIONAL_CONTENTS.id ON DELETE SET NULL

---

### 4.25. STICKER_REDEMPTIONS

**Mục đích:** Lịch sử đổi sticker ảo lấy vật phẩm vật lý (sticker dán, kẹo, sữa...).

Cho user chọn nhận tại cơ sở (pickup) hoặc ship tận nhà (delivery). Vật phẩm giao trong mỗi lần đổi được chốt snapshot chi tiết trong STICKER_REDEMPTION_ITEMS (bảng 4.25b), không phụ thuộc rule về sau.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | Người đổi |
| sticker_id | INT FK => STICKERS | Loại sticker đổi |
| quantity_used | INT | Số lượng sticker ảo đã trừ để đổi 1 lần |
| fulfillment_method | ENUM(pickup, delivery) | pickup = nhận tại cơ sở, delivery = ship tận nhà |
| status | ENUM(pending, shipping, fulfilled, cancelled) | pending = chờ xử lý, shipping = đang giao (chỉ delivery), fulfilled = đã giao/đã nhận, cancelled = hủy và hoàn sticker |
| facility_id | INT FK => FACILITIES NULL | Cơ sở nhận (khi pickup). NULL khi delivery |
| staff_id | INT FK => USERS NULL | Staff xác nhận giao (pickup) hoặc đóng gói (delivery). NULL khi chưa xử lý |
| recipient_name | VARCHAR(150) NULL | Tên người nhận (khi delivery) |
| recipient_phone | VARCHAR(20) NULL | SĐT người nhận (khi delivery) |
| shipping_address | VARCHAR(500) NULL | Địa chỉ giao hàng (khi delivery) |
| shipping_note | VARCHAR(300) NULL | Ghi chú giao hàng |
| created_at | TIMESTAMP | Thời điểm đổi |
| updated_at | TIMESTAMP | Thời điểm cập nhật trạng thái gần nhất |

**Chỉ mục:**

- idx_sticker_redeem_user (user_id, created_at)
- idx_sticker_redeem_user_status (user_id, status)
- idx_sticker_redeem_sticker (sticker_id)
- idx_sticker_redeem_facility (facility_id)
- idx_sticker_redeem_method (fulfillment_method)

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE
- sticker_id => STICKERS.id ON DELETE RESTRICT
- facility_id => FACILITIES.id ON DELETE SET NULL
- staff_id => USERS.id ON DELETE SET NULL

**Chuyển trạng thái:**

```
pickup:   pending => fulfilled (staff giao tại cơ sở)
                  => cancelled (hủy, hoàn sticker)
delivery: pending => shipping (staff đóng gói, bắt đầu giao)
                  => fulfilled (đã giao thành công)
                  => cancelled (hủy, hoàn sticker)
```

**Quy tắc nghiệp vụ:**

- Khi tạo: trừ USER_STICKERS.quantity -= quantity_used, đọc STICKER_REWARD_RULES của sticker để chốt danh sách vật phẩm, INSERT snapshot vào STICKER_REDEMPTION_ITEMS và trừ STICKER_REWARD_ITEMS.stock tương ứng
- pickup: facility_id bắt buộc, recipient/shipping để NULL. Trẻ em thường không tự đến cơ sở - ưu tiên luồng staff xác nhận trực tiếp
- delivery: recipient_name/recipient_phone/shipping_address bắt buộc, facility_id để NULL
- cancelled: hoàn lại USER_STICKERS.quantity và cộng lại STICKER_REWARD_ITEMS.stock

---

### 4.25b. STICKER_REDEMPTION_ITEMS

**Mục đích:** Snapshot chi tiết vật phẩm đã giao trong 1 lần đổi sticker.

Chốt chính xác các vật phẩm + số lượng tại thời điểm đổi, không đổi dù sau này rule (STICKER_REWARD_RULES) hoặc vật phẩm (STICKER_REWARD_ITEMS) thay đổi. Giống cách REDEMPTIONS.points_spent chốt điểm.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| redemption_id | INT FK => STICKER_REDEMPTIONS | Thuộc lần đổi nào |
| reward_item_id | INT FK => STICKER_REWARD_ITEMS NULL | Vật phẩm gốc. NULL nếu vật phẩm bị xóa sau này |
| item_name | VARCHAR(150) | Snapshot tên vật phẩm tại thời điểm đổi |
| item_image_url | VARCHAR(500) NULL | Snapshot ảnh vật phẩm tại thời điểm đổi |
| quantity | INT | Số lượng vật phẩm này đã giao (chốt theo rule lúc đổi) |
| created_at | TIMESTAMP | Thời điểm tạo |

**Chỉ mục:**

- idx_sticker_redeem_item_redemption (redemption_id)
- idx_sticker_redeem_item_reward (reward_item_id)

**Khóa ngoại:**

- redemption_id => STICKER_REDEMPTIONS.id ON DELETE CASCADE
- reward_item_id => STICKER_REWARD_ITEMS.id ON DELETE SET NULL

**Quy tắc nghiệp vụ:**

- Tạo cùng lúc với STICKER_REDEMPTIONS, mỗi rule khớp sticker sinh 1 dòng
- Không sửa sau khi tạo: đây là bản chốt lịch sử

---

### 4.26. TITLE_DEFINITIONS

**Mục đích:** Định nghĩa danh hiệu theo kỳ, manager CRUD toàn bộ tiêu chí.

Criteria_type là chuỗi tự do (mở rộng được khi cần thêm loại mới), threshold và period_days manager tự cấu hình.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(150) | Tên danh hiệu (VD: Nhà sưu tập nhí) |
| description | TEXT NULL | Mô tả |
| icon_url | VARCHAR(500) NULL | Icon hiển thị |
| criteria_type | VARCHAR(50) | Loại tiêu chí: sticker_count, rare_sticker_count, content_read_count... (mở rộng được) |
| threshold | INT | Ngưỡng cần đạt trong kỳ |
| period_days | INT | Số ngày tính ngược để xét. manager cấu hình, mặc định 30 |
| status | ENUM(active, locked) | locked = tạm ngừng cấp danh hiệu này |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_title_defs_status (status)
- idx_title_defs_criteria (criteria_type)

---

### 4.27. USER_TITLES

**Mục đích:** Danh hiệu user đã/đang giữ.

Mỗi lượt cấp chốt expires_at tại thời điểm đó (= earned_at + period_days của TITLE_DEFINITIONS lúc cấp) - nếu sau này manager đổi period_days, các danh hiệu đã cấp trước đó không bị ảnh hưởng.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | Người giữ danh hiệu |
| title_id | INT FK => TITLE_DEFINITIONS | Danh hiệu nào |
| earned_at | TIMESTAMP | Thời điểm đạt |
| expires_at | TIMESTAMP | Thời điểm hết hiệu lực |

**Chỉ mục:**

- idx_user_titles_user (user_id, expires_at)
- idx_user_titles_title (title_id)

**Khóa ngoại:**

- user_id => USERS.id ON DELETE CASCADE
- title_id => TITLE_DEFINITIONS.id ON DELETE CASCADE

**Quy tắc nghiệp vụ:**

- Danh hiệu tự hết hiệu lực khi expires_at trôi qua: lọc hiển thị theo `expires_at > NOW()`, không cần job xóa
- Cronjob định kỳ (VD hàng ngày) tính lại chỉ số theo criteria_type trong period_days gần nhất của từng user, cấp mới nếu đạt threshold và chưa có danh hiệu đó còn hiệu lực

---

### 4.28. APP_SETTINGS

**Mục đích:** Bảng key-value tập trung cho toàn bộ cấu hình manager.

Mọi setting do manager chỉnh qua UI đều lưu ở đây - bao gồm cấu hình upload ảnh, đường dẫn storage, và mọi config khác của hệ thống.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| setting_key | VARCHAR(100) UNIQUE | Khóa setting (VD: storage_base_path, upload_max_size) |
| setting_value | TEXT NULL | Giá trị setting. NULL = chưa cấu hình |
| description | VARCHAR(300) NULL | Mô tả cho manager hiểu ý nghĩa |
| updated_by | INT FK => USERS NULL | manager cập nhật lần cuối |
| updated_at | TIMESTAMP | Thời điểm cập nhật |

**Chỉ mục:**

- uk_app_settings_key (setting_key) - unique

**Khóa ngoại:**

- updated_by => USERS.id ON DELETE SET NULL

**Các setting mẫu:**

| setting_key | Ví dụ value | Mô tả |
| --- | --- | --- |
| storage_base_path | /storage/public/ | Thư mục gốc lưu file upload trên server |
| storage_base_url | https://example.com/storage/public/ | Base URL để render ảnh cho user |
| upload_max_size | 5242880 | Kích thước file tối đa (bytes) |
| allowed_image_types | jpg,png,webp,gif | Loại file ảnh cho phép upload |
| default_avatar_path | avatars/default.png | Avatar mặc định khi user chưa upload |

**Quy tắc nghiệp vụ:**

- manager đọc/ghi setting qua UI, hệ thống cache ở tầng ứng dụng để tránh query liên tục
- Nếu manager đổi `storage_base_path` hoặc `storage_base_url`, ảnh cũ vẫn giữ nguyên path tương đối trong DB - hệ thống tự ghép theo setting mới
- `setting_key` là unique, mỗi setting chỉ có 1 dòng

---

### 4.29. PERMISSIONS

**Mục đích:** Danh mục quyền RBAC.

Mỗi quyền được biểu diễn bằng chuỗi `resource.action` và là nguồn sự thật để backend kiểm tra authorization. UI chỉ dùng để hiển thị/ẩn nút, không thay thế kiểm tra backend.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| code | VARCHAR(100) UNIQUE | Mã quyền, ví dụ `handover.create` |
| resource | VARCHAR(50) | Nhóm tài nguyên: auth, handover, event, content... |
| action | VARCHAR(50) | Hành động: create, approve, publish... |
| name | VARCHAR(150) | Tên hiển thị |
| description | VARCHAR(255) NULL | Mô tả cho manager |
| is_system | TINYINT(1) | 1 = seed bởi hệ thống |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- uk_permissions_code (code)
- uk_permissions_resource_action (resource, action)
- idx_permissions_resource (resource)

---

### 4.30. ROLE_PERMISSIONS

**Mục đích:** Gán quyền cho từng role.

Bảng nối N-N giữa `USERS.role` và `PERMISSIONS`. manager có thể chỉnh mapping qua UI để bật/tắt quyền của từng role.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| role | ENUM(user, staff, manager) | Role được gán quyền |
| permission_id | INT FK => PERMISSIONS | Quyền được gán |
| created_by | INT FK => USERS NULL | manager cấu hình |
| created_at | TIMESTAMP | Thời điểm tạo |

**Ràng buộc unique:** (role, permission_id)

**Chỉ mục:**

- idx_role_permissions_permission (permission_id)
- idx_role_permissions_role (role)
- idx_role_permissions_created_by (created_by)

---

### 4.31. USER_SESSIONS

**Mục đích:** Quản lý phiên đăng nhập đa thiết bị.

Mỗi thiết bị/phiên login tạo 1 dòng riêng, refresh token lưu ở dạng hash. Access token là JWT ngắn hạn, refresh token sống khoảng 60 ngày và có thể revoke độc lập.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| user_id | INT FK => USERS | Chủ phiên |
| refresh_token_hash | VARCHAR(255) | Hash của refresh token, không lưu plaintext |
| refresh_token_jti | CHAR(36) UNIQUE | Token ID để detect rotation/reuse |
| device_type | ENUM(web, mobile, tablet, desktop, unknown) | Loại thiết bị |
| device_name | VARCHAR(150) NULL | Tên thiết bị thân thiện |
| device_os | VARCHAR(80) NULL | Hệ điều hành |
| ip_address | VARCHAR(45) NULL | IPv4/IPv6 |
| user_agent | VARCHAR(500) NULL | User-Agent |
| issued_at | TIMESTAMP | Thời điểm phát hành token |
| refresh_token_expires_at | TIMESTAMP | Hết hạn refresh token (~60 ngày) |
| last_activity_at | TIMESTAMP | Lần hoạt động cuối |
| revoked_at | TIMESTAMP NULL | NULL = còn hoạt động |
| revoked_by_user_id | INT FK => USERS NULL | Ai revoke session |
| revoke_reason | VARCHAR(50) NULL | logout, logout_all, password_change, manager_force_logout... |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- uk_user_sessions_jti (refresh_token_jti)
- idx_user_sessions_user_active (user_id, revoked_at, refresh_token_expires_at)
- idx_user_sessions_last_activity (last_activity_at)
- idx_user_sessions_ip (ip_address)
- idx_user_sessions_revoked_by (revoked_by_user_id)

---

### 4.32. LOGIN_LOGS

**Mục đích:** Audit trail cho mọi lần đăng nhập.

Ghi cả thành công lẫn thất bại, phục vụ audit và phát hiện brute-force. Bảng này là append-only.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | BIGINT PK | Khóa chính |
| user_id | INT FK => USERS NULL | User đã xác định được (nếu có) |
| login_identifier | VARCHAR(150) | Email/username/phone nhập vào |
| login_method | ENUM(password, walk_in_auto_login) | Phương thức login |
| success | TINYINT(1) | 1 = thành công, 0 = thất bại |
| failure_reason | VARCHAR(100) NULL | Lý do thất bại |
| ip_address | VARCHAR(45) NULL | IPv4/IPv6 |
| user_agent | VARCHAR(500) NULL | User-Agent |
| session_id | INT FK => USER_SESSIONS NULL | Session tạo ra nếu login thành công |
| attempted_at | TIMESTAMP | Thời điểm thử đăng nhập |
| metadata_json | JSON NULL | Metadata thêm: event_id, device_info... |

**Chỉ mục:**

- idx_login_logs_user_time (user_id, attempted_at)
- idx_login_logs_identifier_time (login_identifier, attempted_at)
- idx_login_logs_ip_time (ip_address, attempted_at)
- idx_login_logs_success_time (success, attempted_at)
- idx_login_logs_session (session_id)

---

### 4.32b. SESSIONS

**Mục đích:** Bảng chuẩn Laravel khi dùng session driver = database (web guard).

Lưu trạng thái session HTTP: CSRF token, flash data, user đăng nhập qua cookie session. Khác USER_SESSIONS (kho refresh token JWT cho API đa thiết bị) — hai bảng phục vụ 2 tầng: SESSIONS cho web session state, USER_SESSIONS cho API token.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | VARCHAR(255) PK | Session ID do Laravel sinh |
| user_id | INT FK => USERS NULL | User đăng nhập (web guard). NULL với khách |
| ip_address | VARCHAR(45) NULL | IPv4/IPv6 |
| user_agent | TEXT NULL | User-Agent |
| payload | LONGTEXT | Dữ liệu session đã serialize (Laravel quản lý) |
| last_activity | INT | Unix timestamp lần hoạt động cuối |

**Chỉ mục:**

- PRIMARY KEY (id)
- idx_sessions_user (user_id)
- idx_sessions_last_activity (last_activity)

**Quy tắc nghiệp vụ:**

- Laravel tự chạy garbage collection dọn session có last_activity quá ngưỡng config('session.lifetime')
- Cột payload do Laravel serialize/unserialize, không đọc/ghi thẳng bằng raw SQL
- Kết hợp với USER_SESSIONS khi dự án dùng cả web guard (blade) lẫn API (mobile/SPA), không xung đột

---

### 4.33. STICKER_REWARD_ITEMS

**Mục đích:** Danh mục vật phẩm vật lý dùng làm quà đổi sticker, thay cho việc hardcode "kẹo".

manager CRUD toàn bộ: tạo vật phẩm với ảnh + tên + tồn kho. Không còn cố định chỉ có kẹo — có thể là sticker dán, kẹo, sữa, hay bất cứ thứ gì manager cấu hình.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| name | VARCHAR(150) | Tên vật phẩm (VD: Sticker dán, Kẹo, Sữa). manager tự đặt, không hardcode |
| image_url | VARCHAR(500) NULL | Ảnh vật phẩm. Upload lên server, lưu path tương đối |
| description | TEXT NULL | Mô tả vật phẩm |
| stock | INT | Số lượng còn lại trong kho. manager tự chỉnh, trừ dần khi đổi. Mặc định 0 |
| status | ENUM(active, locked) | locked = tạm ngừng cho đổi |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_sticker_reward_items_status (status)
- idx_sticker_reward_items_deleted (deleted_at)

**Quy tắc nghiệp vụ:**

- manager tạo/sửa/xóa vật phẩm và tự điều chỉnh tồn kho (quyền sticker_reward_item.adjust_stock)
- Đổi sticker ra vật phẩm: trừ stock của từng vật phẩm theo rule (STICKER_REWARD_RULES)

---

### 4.34. STICKER_REWARD_RULES

**Mục đích:** Cấu hình bó quà — 1 lần đổi sticker X ra những vật phẩm nào, mỗi thứ bao nhiêu.

Đây là nơi manager định nghĩa "1 sticker ảo đổi ra bao nhiêu vật phẩm" (VD x1 sticker dán, x2 kẹo, x3 sữa) mà không cần đổi schema hay hardcode.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| sticker_id | INT FK => STICKERS | Loại sticker ảo dùng để đổi |
| reward_item_id | INT FK => STICKER_REWARD_ITEMS | Vật phẩm nhận được |
| quantity | INT | Đổi 1 lần sticker này ra bao nhiêu vật phẩm (mặc định 1) |
| status | ENUM(active, locked) | locked = tạm ngừng áp dụng rule này |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- uk_sticker_reward_rule (sticker_id, reward_item_id) UNIQUE
- idx_sticker_reward_rule_sticker (sticker_id)
- idx_sticker_reward_rule_item (reward_item_id)

**Khóa ngoại:**

- sticker_id => STICKERS.id ON DELETE CASCADE
- reward_item_id => STICKER_REWARD_ITEMS.id ON DELETE RESTRICT

**Quy tắc nghiệp vụ:**

- Unique (sticker_id, reward_item_id): mỗi cặp sticker–vật phẩm chỉ có 1 rule
- Khi user đổi sticker: đọc các rule active của sticker đó, chốt snapshot sang STICKER_REDEMPTION_ITEMS

---

### 4.35. STICKER_REDEMPTION_ITEMS

**Mục đích:** Snapshot đơn — chốt chính xác các vật phẩm + số lượng đã giao trong 1 lần đổi sticker.

Giống cách REDEMPTIONS.points_spent chốt điểm, bảng này chốt vật phẩm tại thời điểm đổi. Sau này rule hay vật phẩm thay đổi cũng không ảnh hưởng lịch sử đã giao.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| redemption_id | INT FK => STICKER_REDEMPTIONS | Thuộc lần đổi nào |
| reward_item_id | INT FK => STICKER_REWARD_ITEMS NULL | Vật phẩm gốc. NULL nếu vật phẩm bị xóa sau này |
| item_name | VARCHAR(150) | Snapshot tên vật phẩm tại thời điểm đổi |
| item_image_url | VARCHAR(500) NULL | Snapshot ảnh vật phẩm tại thời điểm đổi |
| quantity | INT | Số lượng vật phẩm này đã giao (chốt theo rule lúc đổi) |
| created_at | TIMESTAMP | Thời điểm tạo |

**Chỉ mục:**

- idx_sticker_redemption_items_redemption (redemption_id)
- idx_sticker_redemption_items_item (reward_item_id)

**Khóa ngoại:**

- redemption_id => STICKER_REDEMPTIONS.id ON DELETE CASCADE
- reward_item_id => STICKER_REWARD_ITEMS.id ON DELETE SET NULL

**Quy tắc nghiệp vụ:**

- Mỗi vật phẩm trong 1 lần đổi = 1 dòng
- item_name/item_image_url là snapshot: không đổi dù vật phẩm gốc sửa/xóa
- reward_item_id set NULL khi vật phẩm gốc bị xóa, nhưng snapshot vẫn giữ nguyên

---

### 4.36. MINI_GAMES

**Mục đích:** Định nghĩa mini game tương tác real-time (kiểu Kahoot), giáo viên lồng vào bài giảng, học sinh chơi cùng trên web.

Dùng game_type + config_json để thêm loại game mới mà không cần đổi schema. Backend parse config_json theo game_type.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| title | VARCHAR(200) | Tên game (VD: Đua phân loại rác) |
| game_type | VARCHAR(50) | quiz, sorting_race, bingo, matching, wheel, guess_image... (mở rộng được, không ràng ENUM cứng) |
| description | TEXT NULL | Mô tả |
| config_json | JSON NULL | Cấu hình + dữ liệu game tùy game_type (câu hỏi, đáp án, thời gian, hình ảnh...). Backend parse theo game_type |
| content_id | INT FK => EDUCATIONAL_CONTENTS NULL | Gắn với bài học nào. NULL = game độc lập |
| created_by | INT FK => USERS NULL | Staff/manager tạo game |
| status | ENUM(active, locked) | locked = tạm ẩn khỏi danh sách |
| deleted_at | TIMESTAMP NULL | Soft delete. NULL = còn hoạt động |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- idx_mini_games_type (game_type)
- idx_mini_games_content (content_id)
- idx_mini_games_created_by (created_by)
- idx_mini_games_status (status)
- idx_mini_games_deleted (deleted_at)

**Khóa ngoại:**

- content_id => EDUCATIONAL_CONTENTS.id ON DELETE SET NULL
- created_by => USERS.id ON DELETE SET NULL

**Quy tắc nghiệp vụ:**

- Thêm loại game mới chỉ cần thêm game_type + format config_json tương ứng, không đổi schema
- Staff tạo/host game, manager CRUD toàn bộ (quyền mini_game.*)

---

### 4.37. GAME_SESSIONS

**Mục đích:** 1 phiên chơi = 1 phòng. Trạng thái real-time đồng bộ qua WebSocket, DB chỉ lưu phòng + kết quả.

room_code unique để học sinh nhập vào join phòng (kiểu PIN Kahoot).

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| game_id | INT FK => MINI_GAMES | Chơi game nào |
| host_user_id | INT FK => USERS NULL | Giáo viên/staff chủ trì. NULL nếu chơi tự do |
| room_code | VARCHAR(20) UNIQUE | Mã phòng học sinh nhập để vào (VD PIN 6 số) |
| status | ENUM(waiting, playing, finished, cancelled) | waiting = chờ người vào, playing = đang chơi, finished = kết thúc, cancelled = hủy |
| points_reward | INT | Điểm ví thưởng cho phiên (lấy default từ APP_SETTINGS, host chỉnh được). Mặc định 0 |
| started_at | TIMESTAMP NULL | Thời điểm bắt đầu chơi |
| ended_at | TIMESTAMP NULL | Thời điểm kết thúc |
| created_at | TIMESTAMP | Thời điểm tạo |
| updated_at | TIMESTAMP | Thời điểm sửa lần cuối |

**Chỉ mục:**

- uk_game_sessions_room (room_code) UNIQUE
- idx_game_sessions_game (game_id)
- idx_game_sessions_host (host_user_id)
- idx_game_sessions_status (status)

**Khóa ngoại:**

- game_id => MINI_GAMES.id ON DELETE CASCADE
- host_user_id => USERS.id ON DELETE SET NULL

**Chuyển trạng thái:**

```
waiting => playing (host bắt đầu) => finished (kết thúc, chốt rank + thưởng điểm)
        => cancelled (host hủy phòng)
```

**Quy tắc nghiệp vụ:**

- points_reward lấy default từ APP_SETTINGS, chỉ thưởng nếu cấu hình bật thưởng điểm game
- Trạng thái từng câu/từng lượt chơi live qua WebSocket, DB chỉ chốt kết quả cuối

---

### 4.38. GAME_PARTICIPANTS

**Mục đích:** 1 dòng/người chơi/phiên. Bảng xếp hạng real-time sort theo score.

Hỗ trợ cả user đã đăng nhập lẫn khách chơi bằng nickname. Thưởng điểm ví qua points_awarded nếu APP_SETTINGS bật.

| Cột | Kiểu | Mô tả |
| --- | --- | --- |
| id | INT PK | Khóa chính |
| session_id | INT FK => GAME_SESSIONS | Thuộc phiên chơi nào |
| user_id | INT FK => USERS NULL | User đã đăng nhập. NULL nếu chơi bằng nickname (khách) |
| nickname | VARCHAR(100) | Tên hiển thị trong phòng (user hoặc khách) |
| score | INT | Điểm số trong game (khác điểm ví). Mặc định 0 |
| rank | INT NULL | Thứ hạng chung cuộc trong phiên |
| points_awarded | INT | Điểm ví thực nhận sau phiên (nếu bật thưởng). Ghi POINT_EARNED tương ứng. Mặc định 0 |
| joined_at | TIMESTAMP | Thời điểm vào phòng |

**Chỉ mục:**

- idx_game_participants_session_score (session_id, score)
- idx_game_participants_session (session_id)
- idx_game_participants_user (user_id)

**Khóa ngoại:**

- session_id => GAME_SESSIONS.id ON DELETE CASCADE
- user_id => USERS.id ON DELETE SET NULL

**Quy tắc nghiệp vụ:**

- Chỉ user đã đăng nhập (user_id NOT NULL) mới được thưởng điểm ví; khách nickname chỉ chơi vui
- Khi phiên finished: chốt rank theo score, nếu bật thưởng thì set points_awarded và insert POINT_EARNED (source_type = game)

---

## 5. Các luồng nghiệp vụ chính

### 5.1. Đơn chuyển giao thường

1. User tạo HANDOVER_REQUESTS (status = pending), chọn 1 hoặc nhiều loại rác => Insert HANDOVER_WASTE_ITEMS cho mỗi loại
2. Nếu cần thêm 1 dòng rác khác trong cùng đơn, User nhấn nút "+" để thêm dòng mới => chọn lại loại rác từ WASTE_TYPES, nhập weight và unit_id tương ứng (không phải tạo WASTE_TYPES mới)
3. manager phân công staff => Cập nhật staff_id, status = approved
4. Staff đến và cân rác => Insert HANDOVER_WEIGHT_LOGS (weight, recorded_by = staff_id), cập nhật status = completed
5. Hệ thống cộng điểm => Insert POINT_EARNED (source_type = handover), cập nhật USER_WALLETS.balance
6. Hệ thống log mọi thay đổi trạng thái vào SYSTEM_LOGS

### 5.2. Luồng tham gia Sự kiện

**Trước sự kiện:**

1. manager tạo EVENTS => Insert SYSTEM_LOGS (entity_type = event, action = create, performed_by_user_id = manager_id)
2. manager phân công staff => Insert EVENT_STAFF_ASSIGNMENTS (chỉ staff cùng cơ sở với manager tạo event, trigger chặn nếu staff đang được gán cho event khác cùng thời gian)
3. manager cấu hình quà => Insert EVENT_REWARDS

**Trong sự kiện:**

1. User quét QR code => Tạo EVENT_REGISTRATIONS (status = registered)
2. Staff check-in user => Cập nhật status = attended, checked_in_at = NOW()
3. User nộp đồ => Tạo HANDOVER_REQUESTS với event_id, chọn loại rác qua HANDOVER_WASTE_ITEMS
4. Staff cân rác => Insert HANDOVER_WEIGHT_LOGS, cập nhật HANDOVER_REQUESTS.status = completed
5. Hệ thống mở khóa minigame => Cập nhật EVENT_REGISTRATIONS minigame_status = unlocked
6. User chơi minigame => Cập nhật minigame_status = played
7. Nếu user trúng => Giảm EVENT_REWARDS.remaining, trao quà vật lý
8. Hệ thống cộng điểm => Insert POINT_EARNED (source_type = event_minigame)

**Luồng walk-in:**

1. User lạ quét QR => Hệ thống thu email, kiểm tra USERS theo email
2. Nếu chưa có => Tạo USERS (email lưu vào USERS.email, is_walk_in = 1, sinh mật khẩu tạm, password = hash(temp), must_change_password = 1)
3. Hệ thống tự đăng nhập ngay tại chỗ (auto-login): tạo USER_SESSIONS, cấp token, insert LOGIN_LOGS (login_method = walk_in_auto_login). User vào app dùng được liền, không cần mở email nhập mật khẩu
4. Gửi mật khẩu tạm qua email vừa thu => dùng cho các lần đăng nhập lại sau (thiết bị khác, hết session)
5. Tạo EVENT_REGISTRATIONS (registration_type = walkin)
6. Vì must_change_password = 1 => hệ thống nhắc/buộc user đặt mật khẩu mới khi vào app, sau khi đổi hạ must_change_password = 0
7. Lần đăng nhập lại sau bằng mật khẩu (login_method = password)
8. Nếu quên mật khẩu => dùng luồng reset bằng link qua PASSWORD_RESET_TOKENS (xem mục 5.9)

### 5.3. Luồng nội dung Giáo dục

1. Staff tạo EDUCATIONAL_CONTENTS (status = pending, kèm thumbnail_url nếu có)
2. manager review => Cập nhật status = published hoặc rejected, approved_by_id = manager
3. User mở bài đã published => Insert CONTENT_READS (started_at = NOW(), read_date = CURRENT_DATE, completed_at = NULL)
4. User đọc đủ timer_seconds => Cập nhật completed_at = NOW()
5. Hệ thống kiểm tra quota ngày: tổng lượt rewarded trong read_date = CURRENT_DATE < 10? Lượt rewarded cho content này trong ngày < 2?
6. Nếu đủ điều kiện => Đặt rewarded = 1, insert POINT_EARNED (source_type = content_read)
7. Nếu user thoát trước khi đủ timer => completed_at = NULL, không rewarded, không cộng điểm

### 5.4. Luồng Điểm

**Điểm được cộng (POINT_EARNED, points luôn dương):**

- Hoàn tất đơn chuyển giao (source_type = handover)
- Chơi minigame tại sự kiện (source_type = event_minigame)
- Đọc bài giáo dục (source_type = content_read)
- Điều chỉnh cộng điểm thủ công của manager (source_type = manager_adjust)
- Hoàn điểm khi hủy đổi quà (source_type = redemption_refund)
- Lần đầu sở hữu 1 loại sticker có bonus_points > 0 (source_type = sticker_bonus)

**Điểm bị trừ (POINT_SPENT, points luôn dương):**

- Đổi quà từ REWARD_CATALOG (source_type = redemption)
- Điều chỉnh trừ điểm thủ công của manager (source_type = manager_adjust)

**Công thức:** `USER_WALLETS.balance = SUM(POINT_EARNED.points) - SUM(POINT_SPENT.points)`

**Lưu ý quan trọng:** Quà tại sự kiện (EVENT_REWARDS) là quà vật lý trao tay tại sự kiện. Không trừ điểm trong ví. Chỉ quà trong REWARD_CATALOG mới trừ điểm.

### 5.5. Luồng Đổi quà

1. User xem danh sách REWARD_CATALOG (status = active, deleted_at IS NULL, stock > 0)
2. User chọn quà => Kiểm tra USER_WALLETS.balance >= points_cost \* quantity
3. Trừ điểm => Insert POINT_SPENT (source_type = redemption, points = points_cost \* quantity)
4. Tạo REDEMPTIONS (status = pending, points_spent = points_cost \* quantity, transaction_id = vừa tạo)
5. Giảm REWARD_CATALOG.stock
6. Staff/manager giao quà => Cập nhật REDEMPTIONS.status = fulfilled, fulfilled_by_id
7. Nếu hủy => Insert POINT_EARNED (source_type = redemption_refund, points = points_spent) để hoàn điểm, cộng lại stock, REDEMPTIONS.status = cancelled

### 5.6. Luồng Sticker sưu tập

1. User đọc xong 1 bài có EDUCATIONAL_CONTENTS.sticker_set_id khác NULL (đủ timer_seconds)
2. Hệ thống lấy toàn bộ STICKERS (status = active, deleted_at IS NULL) thuộc set_id đó, random 1 theo trọng số drop_weight
3. Insert STICKER_OBTAIN_LOGS (user_id, sticker_id, source_content_id)
4. Upsert USER_STICKERS: nếu chưa có dòng thì tạo mới (quantity = 1, total_obtained = 1); nếu đã có thì quantity += 1, total_obtained += 1, last_obtained_at = NOW()
5. Nếu đây là lần đầu sở hữu (total_obtained trước khi cộng = 0):
  - Nếu STICKERS.bonus_points > 0 => Insert POINT_EARNED (source_type = sticker_bonus, points = bonus_points, reference_id = sticker_id)
  - Nếu STICKERS.unlocks_content_id khác NULL => mở khóa bài đó cho user (enforce hiển thị ở tầng ứng dụng)
6. User xem USER_STICKERS.quantity >= STICKERS.redeem_quantity_required => đủ điều kiện đổi vật lý
7. User đến cơ sở, Staff xác nhận => Insert STICKER_REDEMPTIONS (quantity_used, facility_id, staff_id), trừ USER_STICKERS.quantity -= quantity_used

### 5.7. Luồng Danh hiệu theo kỳ

Chạy bằng cronjob định kỳ (VD hàng ngày):

1. Với mỗi TITLE_DEFINITIONS đang active, lặp qua từng user có hoạt động trong period_days gần nhất
2. Tính chỉ số theo criteria_type trong khoảng \[NOW() - period_days, NOW()\]:
  - sticker_count: COUNT STICKER_OBTAIN_LOGS của user trong kỳ
  - rare_sticker_count: COUNT STICKER_OBTAIN_LOGS join STICKERS where rarity IN (rare, special) trong kỳ
  - content_read_count: COUNT CONTENT_READS (rewarded = 1) của user trong kỳ
3. Nếu chỉ số >= threshold VÀ user chưa có USER_TITLES cho title đó với expires_at > NOW() => Insert USER_TITLES (earned_at = NOW(), expires_at = NOW() + period_days ngày)
4. Không cần job dọn dẹp: danh hiệu tự hết hiệu lực khi expires_at trôi qua, tầng hiển thị chỉ lọc theo `expires_at > NOW()`

### 5.8. Luồng Upload Ảnh

**Lưu ý về setting:** Tất cả setting hạ tầng upload và render ảnh đều đi qua `APP_SETTINGS` (storage_base_path, storage_base_url, upload_max_size, allowed_image_types...).

**Luồng upload ảnh chung (áp dụng cho tất cả loại ảnh trong hệ thống):**

1. User/manager chọn file ảnh trên UI
2. Backend kiểm tra: loại file có trong `APP_SETTINGS.allowed_image_types`? kích thước ≤ `APP_SETTINGS.upload_max_size`?
3. Server lưu file vào `{APP_SETTINGS.storage_base_path}/{folder}/{filename}.ext`
4. Server trả về path tương đối: `{folder}/{filename}.ext`
5. Backend INSERT/UPDATE path vào cột ảnh tương ứng trong DB (image_url, avatar_url, thumbnail_url, cover_image_url, icon_url...)
6. Khi render: đọc DB lấy path tương đối, ghép với `APP_SETTINGS.storage_base_url` → URL hoàn chỉnh hiển thị cho user

**Các loại ảnh trong hệ thống:**

| Đối tượng | Cột ảnh | Thư mục gợi ý | Ai upload |
| --- | --- | --- | --- |
| Poster sự kiện | EVENTS.image_url | events/ | manager |
| Avatar user | USERS.avatar_url | avatars/ | User |
| Ảnh bìa bài học | EDUCATIONAL_CONTENTS.thumbnail_url | contents/ | Staff |
| Ảnh cơ sở | FACILITIES.image_url | facilities/ | manager |
| Ảnh sticker | STICKERS.image_url | stickers/ | manager |
| Ảnh bộ sticker | STICKER_SETS.cover_image_url | sticker-sets/ | manager |
| Ảnh quà đổi điểm | REWARD_CATALOG.image_url | rewards/ | manager |
| Ảnh quà sticker | STICKER_REWARD_ITEMS.image_url | sticker-rewards/ | manager |
| Icon danh hiệu | TITLE_DEFINITIONS.icon_url | titles/ | manager |
| Ảnh minh họa trong bài | embed trong EDUCATIONAL_CONTENTS.content qua `<img src="...">` | contents/ | Staff |

**Ví dụ cụ thể:**

- `APP_SETTINGS.storage_base_url = https://example.com/storage/public/`
- `EVENTS.image_url = events/evt_001.jpg`
- Render: `https://example.com/storage/public/events/evt_001.jpg`

- `EDUCATIONAL_CONTENTS.content = '<p>Bài học về nhựa tái chế</p><img src="contents/plastic_01.jpg">'`
- Render: `<img src="https://example.com/storage/public/contents/plastic_01.jpg">`

**Lưu ý:**

- Path lưu trong DB luôn là **path tương đối** (không lưu full URL) - để thay đổi domain/base URL chỉ cần sửa 1 dòng trong `APP_SETTINGS` mà không cần sửa bản ghi ảnh
- Nếu manager đổi `storage_base_path`, ảnh cũ vẫn hoạt động đúng vì chỉ thay đổi base, không thay path tương đối
- Ảnh embed trong `EDUCATIONAL_CONTENTS.content` cần sanitize HTML khi upload để chặn XSS (đặc biệt quan trọng vì đối tượng sử dụng là trẻ em)

---

### 5.9. Luồng Auth + Refresh Token

1. User đăng nhập bằng password hoặc walk-in auto-login (không có OTP)
2. Backend verify thông tin đăng nhập, check `USERS.status`, `deleted_at`
3. Nếu thành công: tạo `USER_SESSIONS` với refresh token hash + metadata thiết bị/IP
4. Backend trả về:
   - access token JWT (15–30 phút)
   - refresh token (60 ngày)
5. Client dùng access token cho mọi request
6. Khi access token hết hạn:
   - client gửi refresh token
   - backend verify hash trong `USER_SESSIONS`
   - check session chưa revoked, chưa hết hạn
   - rotate refresh token nếu cần
   - cấp access token mới
7. Logout current device:
   - revoke 1 row trong `USER_SESSIONS`
8. Logout all devices / đổi mật khẩu / manager force logout:
   - revoke toàn bộ session của user
9. Mỗi lần login/refresh đều ghi audit vào `LOGIN_LOGS`

**Quy tắc token:**
- Access token là stateless JWT, không lưu plaintext trong DB
- Refresh token bắt buộc lưu hash
- Multi-device được phép: mỗi device = 1 session riêng
- Session active khi `revoked_at IS NULL` và `refresh_token_expires_at > NOW()`

**Luồng reset mật khẩu bằng link (PASSWORD_RESET_TOKENS):**

1. User bấm "Quên mật khẩu" → nhập email
2. Backend gọi `Password::sendResetLink(['email' => ...])` → sinh token, upsert vào `password_reset_tokens` (hash), gửi link qua email
3. User bấm link (chứa token gốc + email) → mở form đặt mật khẩu mới
4. User submit mật khẩu mới → Backend gọi `Password::reset(...)`:
   - Tra `password_reset_tokens` theo email
   - So hash(token gửi lên) với hash trong DB → khớp + chưa hết hạn (mặc định 60 phút)
5. Nếu hợp lệ:
   - Cập nhật `USERS.password = Hash::make(new_password)`
   - Hạ `USERS.must_change_password = 0`
   - Xóa dòng token khỏi `password_reset_tokens` (dùng 1 lần)
   - Revoke toàn bộ `USER_SESSIONS` với `revoke_reason = password_change`
   - Ghi `SYSTEM_LOGS` (entity_type = user, action = reset_password)
6. Nếu token sai/hết hạn → trả lỗi, yêu cầu gửi lại

**Luồng xác minh email (Laravel MustVerifyEmail):**

1. Khi tạo tài khoản (hoặc đổi email) → gửi email chứa link xác minh có signature
2. User bấm link → Backend verify signature + user_id → set `USERS.email_verified_at = NOW()`
3. Middleware `verified` chặn user chưa xác minh truy cập route cần email verified

---

### 5.10. Luồng RBAC

1. Backend nhận request vào một action cụ thể
2. Middleware/guard xác định permission cần dùng, ví dụ `handover.approve`
3. Lấy role hiện tại từ `USERS.role`
4. Tra `ROLE_PERMISSIONS` để lấy tập quyền được phép của role đó
5. Nếu permission tồn tại => cho phép, ngược lại trả 403
6. manager UI chỉ là nơi chỉnh mapping role-permission; backend vẫn là nguồn sự thật

**Nguyên tắc:**
- `USERS.role` chỉ là role gốc: `user | staff | manager`
- Quyền thật sự đến từ `PERMISSIONS` + `ROLE_PERMISSIONS`
- Các quyền nhạy cảm như `settings.update`, `role_permission.update`, `system_log.view` phải luôn kiểm tra server-side

**Seed permission mặc định:**
- Bảng `PERMISSIONS` được seed sẵn toàn bộ quyền chuẩn của hệ thống
- Bảng `ROLE_PERMISSIONS` seed 3 bộ quyền mặc định cho `user`, `staff`, `manager` (đã bỏ quyền trùng `points.adjust_manager` và thêm `event_registration.view_own`)
- manager có thể thay đổi mapping này qua UI nếu cần

---

### 5.11. Luồng Cấu hình quà đổi sticker (manager)

Thay cho việc hardcode "kẹo", manager tự dựng danh mục vật phẩm và rule đổi:

1. manager tạo vật phẩm => Insert STICKER_REWARD_ITEMS (name, image_url, stock). VD: "Sticker dán", "Kẹo", "Sữa"
2. manager chỉnh tồn kho bất kỳ vật phẩm nào => Cập nhật STICKER_REWARD_ITEMS.stock
3. manager cấu hình rule "1 sticker ảo đổi ra bao nhiêu vật phẩm" => Insert STICKER_REWARD_RULES (sticker_id, reward_item_id, quantity). VD: sticker "Rùa biển" => x1 Sticker dán + x2 Kẹo + x3 Sữa
4. Có thể locked 1 vật phẩm (status = locked) hoặc 1 rule để tạm ngừng cho đổi mà không xóa dữ liệu

### 5.12. Luồng Đổi sticker vật lý (pickup hoặc ship)

1. User đủ số lượng sticker ảo yêu cầu => tạo yêu cầu đổi. Chọn `fulfillment_method`:
   - **pickup**: chọn `facility_id` để nhận tại cơ sở
   - **delivery**: nhập `recipient_name`, `recipient_phone`, `shipping_address`, `shipping_note`
2. Hệ thống đọc STICKER_REWARD_RULES active của sticker đó => Insert STICKER_REDEMPTIONS (status = pending) + **chốt snapshot** từng vật phẩm sang STICKER_REDEMPTION_ITEMS (item_name, item_image_url, quantity)
3. Trừ sticker ảo: USER_STICKERS.quantity -= quantity_used; trừ tồn kho: STICKER_REWARD_ITEMS.stock -= quantity mỗi vật phẩm
4. Chuyển trạng thái:
   - **pickup**: `pending => fulfilled` khi staff giao tại cơ sở (staff_id ghi người xác nhận)
   - **delivery**: `pending => shipping` khi đóng gói giao đi => `fulfilled` khi giao xong
   - `=> cancelled`: hoàn lại sticker ảo và tồn kho vật phẩm
5. Snapshot trong STICKER_REDEMPTION_ITEMS không đổi dù sau này rule/vật phẩm sửa hay xóa

### 5.13. Luồng Ship quà đổi điểm (REDEMPTIONS)

Tương tự đổi sticker, quà đổi bằng điểm ví cũng cho chọn nhận tại cơ sở hoặc ship:

1. User đổi quà, chọn `fulfillment_method` (pickup / delivery). Nếu delivery thì nhập thông tin người nhận + địa chỉ
2. Insert REDEMPTIONS (status = pending) + trừ điểm qua POINT_SPENT (source_type = redemption), trừ REWARD_CATALOG.stock
3. Chuyển trạng thái: `pending => shipping (chỉ delivery) => fulfilled`; `=> cancelled` thì hoàn điểm qua POINT_EARNED (source_type = redemption_refund) + cộng lại stock

### 5.14. Luồng Mini game real-time (kiểu Kahoot)

1. Staff/manager tạo game => Insert MINI_GAMES (game_type + config_json). Có thể gắn content_id để lồng vào bài học
2. Giáo viên/host mở phòng => Insert GAME_SESSIONS (status = waiting, room_code sinh ngẫu nhiên, points_reward lấy default từ APP_SETTINGS)
3. Học sinh nhập room_code để vào => Insert GAME_PARTICIPANTS (user_id nếu đã đăng nhập, hoặc nickname nếu là khách)
4. Host bắt đầu => GAME_SESSIONS.status = playing, started_at = NOW(). Trạng thái real-time đồng bộ qua **WebSocket**, DB chỉ lưu phòng + kết quả
5. Trong lúc chơi: cập nhật GAME_PARTICIPANTS.score, bảng xếp hạng sort theo score real-time
6. Kết thúc => status = finished, ended_at = NOW(), chốt rank theo score
7. Nếu APP_SETTINGS bật thưởng điểm (`game_points_enabled`): set GAME_PARTICIPANTS.points_awarded + Insert POINT_EARNED (source_type = game). Chỉ user đã đăng nhập mới được thưởng, khách nickname chỉ chơi vui

---

## 6. Ghi chú Kỹ thuật

### 6.1. Database Engine

- MariaDB / MySQL
- Engine: InnoDB
- Charset: utf8mb4 với collation utf8mb4_unicode_ci
- Tất cả bảng dùng AUTO_INCREMENT làm khóa chính

### 6.2. Kiểu dữ liệu

- Timestamps: TIMESTAMP với DEFAULT CURRENT_TIMESTAMP và ON UPDATE CURRENT_TIMESTAMP khi cần
- Enums: VARCHAR trong DBML (dễ đọc), ENUM trong SQL (enforce constraint)
- Khối lượng/giá trị đo: DECIMAL(10,2), đơn vị đo lấy từ MEASUREMENT_UNITS (manager quản lý)
- GPS: DECIMAL(10,7) cho độ chính xác latitude/longitude
- Điểm: luôn lưu dạng số dương (POINT_EARNED, POINT_SPENT), không dùng số âm để biểu thị trừ điểm

### 6.3. Chính sách Khóa Ngoại

- ON DELETE CASCADE: Xóa bản ghi con khi xóa cha (EVENT_REWARDS, EVENT_REGISTRATIONS, POINT_EARNED, POINT_SPENT, HANDOVER_WASTE_ITEMS, HANDOVER_WEIGHT_LOGS, USER_STICKERS, STICKER_OBTAIN_LOGS, USER_TITLES, STICKER_REDEMPTION_ITEMS, GAME_PARTICIPANTS...)
- ON DELETE RESTRICT: Chặn xóa cha nếu còn con (FACILITIES, WASTE_TYPES, STICKER_REWARD_ITEMS...)
- ON DELETE SET NULL: Bỏ liên kết nhưng giữ bản ghi (staff_id, event_id trên HANDOVER_REQUESTS; sticker_set_id trên EDUCATIONAL_CONTENTS; unlocks_content_id trên STICKERS; facility_id, staff_id trên STICKER_REDEMPTIONS; content_id, created_by trên MINI_GAMES; host_user_id trên GAME_SESSIONS; user_id trên GAME_PARTICIPANTS; reward_item_id trên STICKER_REDEMPTION_ITEMS)

### 6.4. Chiến lược Chỉ mục

- Chỉ mục 1 cột trên khóa ngoại để tối ưu JOIN
- Chỉ mục composite cho pattern query thường dùng (user_id + status, facility_id + appointment_time, user_id + created_at)
- Ràng buộc unique trên khóa tự nhiên (phone, email, QR code, cặp event + user, cặp request + waste_type, cặp user + sticker)
- Trigger trên EVENT_STAFF_ASSIGNMENTS chặn staff overlap thời gian (so sánh start_time/end_time của events)
- Đơn vị đo được tách riêng thành bảng MEASUREMENT_UNITS để manager mở rộng sau này mà không cần sửa schema

### 6.5. Tiến hóa Schema

- Mọi thay đổi schema phải đồng bộ trên cả schema.dbml, schema.sql và README này
- Cần viết migration script khi thay đổi schema trên production
- Mọi thay đổi trạng thái phải được log vào SYSTEM_LOGS

---

## 7. Quyết định Thiết kế đã Chốt

Các mục sau đã được chốt và triển khai vào schema:

1. **Xác thực user vãng lai:** Dùng mật khẩu tạm gửi qua email. Khi tạo tài khoản vãng lai, hệ thống sinh mật khẩu tạm và đặt USERS.must_change_password = 1, buộc đổi ở lần đăng nhập kế tiếp. Đăng nhập bằng mật khẩu hoặc walk-in auto-login, không dùng OTP

2. **Quota đọc theo ngày:** Tính theo ngày, reset lúc 00:00 mỗi ngày (dùng read_date trong CONTENT_READS). Mỗi content tối đa 2 lượt rewarded/ngày, tổng tối đa 10 lượt rewarded/ngày/user

3. **Soft delete:** Đã thêm cột deleted_at cho các bảng chính: USERS, FACILITIES, EVENTS, EDUCATIONAL_CONTENTS, REWARD_CATALOG, WASTE_TYPES. NULL = còn hoạt động, có giá trị = đã xóa mềm

4. **Đơn vị đo:** Thêm bảng MEASUREMENT_UNITS để manager quản lý danh sách đơn vị đo. HANDOVER_REQUESTS, HANDOVER_WASTE_ITEMS và HANDOVER_WEIGHT_LOGS dùng `unit_id` FK thay cho enum, giúp manager mở rộng đơn vị sau này mà không phải sửa schema

5. **Đổi quà:** Đã thêm bảng REWARD_CATALOG (danh mục quà đổi bằng điểm) và REDEMPTIONS (lịch sử đổi). Trừ điểm qua POINT_SPENT với source_type = redemption

6. **Bỏ OTP, dùng luồng chuẩn Laravel:** Hệ thống không dùng OTP (đã bỏ bảng EMAIL_OTP_CODES). Reset mật khẩu bằng link qua PASSWORD_RESET_TOKENS (Laravel Password Broker), xác minh email bằng link (`MustVerifyEmail`), đăng nhập chỉ bằng mật khẩu hoặc walk-in auto-login

7. **Tách điểm cộng/trừ:** Bỏ cột points dấu +/- trên 1 bảng chung. Tách thành POINT_EARNED (luôn dương, mọi nguồn cộng điểm) và POINT_SPENT (luôn dương, mọi nguồn trừ điểm) để code rõ ràng, tránh nhầm lẫn dấu

8. **Tối giản EVENTS:** Bỏ cột facility_id (suy ra từ facility của manager tạo event) và manager_id (chuyển sang ghi nhận qua SYSTEM_LOGS, entity_type = event, action = create)

9. **Chuẩn hóa loại rác:** Bỏ cột waste_type dạng chữ tự do trên HANDOVER_REQUESTS. Thêm bảng WASTE_TYPES (danh mục do manager quản lý, user chỉ chọn từ danh sách có sẵn) và HANDOVER_WASTE_ITEMS (bảng nối N-N, 1 đơn chọn nhiều loại rác, có weight + unit_id cho từng loại)

10. **Tách khối lượng thực tế:** Bỏ cột actual_weight trên HANDOVER_REQUESTS. Thêm bảng HANDOVER_WEIGHT_LOGS để hỗ trợ nhiều lần cân, audit ai cân, lúc nào, và lưu `unit_id` FK tới bảng đơn vị đo

11. **Ảnh bìa bài học:** Thêm cột thumbnail_url trên EDUCATIONAL_CONTENTS. Nội dung gốc có thể là PDF, content lưu phần text, thumbnail_url lưu ảnh bìa hiển thị ngoài danh sách

12. **Sticker sưu tập:** Thêm 5 bảng (STICKER_SETS, STICKERS, USER_STICKERS, STICKER_OBTAIN_LOGS, STICKER_REDEMPTIONS) làm lớp thưởng vui độc lập với hệ điểm ví, dành cho F1 là trẻ em đọc bài giáo dục. Sticker có thể trùng (sưu tập theo số lượng), đủ số lượng đổi được vật phẩm vật lý (cấu hình qua STICKER_REWARD_ITEMS + STICKER_REWARD_RULES). Độ hiếm càng cao (rarity) thì bonus_points và khả năng unlocks_content_id càng lớn, manager cấu hình toàn bộ qua drop_weight/redeem_quantity_required/bonus_points. Thêm source_type = sticker_bonus vào POINT_EARNED và sticker_set_id vào EDUCATIONAL_CONTENTS

13. **Danh hiệu theo kỳ:** Thêm 2 bảng (TITLE_DEFINITIONS, USER_TITLES) ghi nhận thành tích luân phiên (VD 30 ngày), tự hết hạn để không ai giữ mãi. manager CRUD toàn bộ tiêu chí (criteria_type, threshold, period_days). Cấp qua cronjob định kỳ, mỗi lượt cấp chốt expires_at tại thời điểm đó

14. **Tài khoản dùng chung (phụ huynh/con):** Không thêm khái niệm hồ sơ phụ (sub-profile). Giữ nguyên 1 USERS.id = 1 danh tính duy nhất; nếu phụ huynh cho con dùng ké tài khoản, sticker/điểm/danh hiệu tính chung theo tài khoản đó - đây là giới hạn được chấp nhận, không phải bug. Trường học/giáo viên chỉ là bối cảnh marketing bên ngoài (GV gửi QR cho phụ huynh), không vào DB, không thêm role giáo viên hay bảng trường/lớp

15. **Upload ảnh & Cấu hình manager (APP_SETTINGS):** Tất cả setting do manager cấu hình đều nằm trong bảng `APP_SETTINGS` dạng key-value (storage_base_path, upload_max_size, allowed_image_types...). Ảnh upload lên server, lưu path tương đối vào cột image_url/avatar_url/thumbnail_url trong DB, không lưu full URL. Khi render, hệ thống ghép path tương đối với giá trị storage_base_url từ APP_SETTINGS. EDUCATIONAL_CONTENTS.content là HTML (rich text), ảnh minh họa embed trực tiếp qua `<img src="...">`. Nếu manager đổi base URL, chỉ cần sửa 1 dòng trong APP_SETTINGS, không cần sửa bản ghi ảnh

16. **Authentication + RBAC:** Thêm bảng session/token và bảng permission riêng. Refresh token sống 60 ngày, lưu hash trong USER_SESSIONS, access token là JWT ngắn hạn. Quyền truy cập backend phải check qua PERMISSIONS + ROLE_PERMISSIONS, không chỉ dựa vào USERS.role. Mỗi session đăng nhập đa thiết bị được lưu riêng và có thể revoke độc lập; login thành công/thất bại phải có LOGIN_LOGS. Bảng USERS dùng đúng tên cột chuẩn Laravel (`password`, `email_verified_at`, `remember_token`) thay vì `password_hash`, giúp Authenticatable contract và middleware Auth hoạt động mặc định. Bổ sung 2 bảng framework: PASSWORD_RESET_TOKENS (reset mật khẩu bằng link, Laravel Password Broker) và SESSIONS (web session driver = database) — song song với USER_SESSIONS (JWT). Hệ thống không dùng OTP; reset mật khẩu và xác minh email đều dùng link chuẩn Laravel.

17. **Ship quà tận nhà (pickup/delivery):** Cả REDEMPTIONS (đổi quà bằng điểm) và STICKER_REDEMPTIONS (đổi sticker vật lý) đều cho user chọn `fulfillment_method` = pickup (nhận tại cơ sở) hoặc delivery (ship tận nhà). Giải quyết việc không ai tới cơ sở nhận kẹo/quà. Khi delivery: lưu recipient_name/recipient_phone/shipping_address/shipping_note, facility_id/staff_id để NULL, thêm trạng thái `shipping` vào vòng đời. Khi pickup: giữ luồng cũ (nhận tại facility, staff xác nhận)

18. **Quà đổi sticker cấu hình linh hoạt (bỏ hardcode "kẹo"):** Thêm 3 bảng. STICKER_REWARD_ITEMS = danh mục vật phẩm vật lý (ảnh + tên + tồn kho) manager CRUD tự do, thay cho việc hardcode "kẹo". STICKER_REWARD_RULES = cấu hình bó quà "1 sticker ảo X đổi ra bao nhiêu vật phẩm" (VD x1 sticker dán, x2 kẹo, x3 sữa), manager set qua quantity. STICKER_REDEMPTION_ITEMS = snapshot đơn, chốt chính xác vật phẩm + số lượng đã giao trong mỗi lần đổi (giống REDEMPTIONS.points_spent chốt điểm), không đổi dù rule/vật phẩm thay đổi sau này. manager tự chỉnh tồn kho cho mọi vật phẩm

19. **Mini game tương tác real-time (generic):** Thêm 3 bảng (MINI_GAMES, GAME_SESSIONS, GAME_PARTICIPANTS). Vì chưa chốt game cụ thể, dùng mô hình generic `game_type` + `config_json` để thêm loại game mới (quiz, sorting_race, bingo, matching, wheel, guess_image...) mà không cần đổi schema; backend parse config theo game_type. Giáo viên chủ trì phòng (room_code kiểu Kahoot), học sinh join chơi real-time đồng bộ qua WebSocket, DB chỉ lưu phòng + kết quả. Thưởng điểm ví bật/tắt và mức điểm cấu hình qua APP_SETTINGS (points_reward mỗi phiên); chỉ user đã đăng nhập mới được cộng điểm ví (source_type = game), khách nickname chỉ chơi vui

---

## 8. Tham chiếu

- **Sơ đồ trực quan:** https://dbdiagram.io/d/LOOP-STATION-DATABASE-SCHEMA-6a3a315f5c789b8acbdfd6fa
- **File nguồn:** [schema.dbml](schema.dbml) | [schema.sql](../../../database/schema/schema.sql)
- **Tài liệu DBML:** https://dbml.dbdiagram.io/docs/