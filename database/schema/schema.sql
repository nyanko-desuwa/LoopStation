SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


CREATE TABLE `PERMISSIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL COMMENT 'Permission code in resource.action format',
  `resource` VARCHAR(50) NOT NULL COMMENT 'Resource name, e.g. handover, event, content',
  `action` VARCHAR(50) NOT NULL COMMENT 'Action name, e.g. create, approve, publish',
  `name` VARCHAR(150) NOT NULL COMMENT 'Human-readable permission label',
  `description` VARCHAR(255) DEFAULT NULL COMMENT 'Optional help text for UI/manager',
  `is_system` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = seeded by system, 0 = manager-added',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_code` (`code`),
  UNIQUE KEY `uk_permissions_resource_action` (`resource`, `action`),
  KEY `idx_permissions_resource` (`resource`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Master permission catalog for RBAC';


CREATE TABLE `ROLE_PERMISSIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role` ENUM('user', 'staff', 'manager') NOT NULL COMMENT 'Existing role domain from USERS.role',
  `permission_id` INT NOT NULL COMMENT 'FK to PERMISSIONS.id',
  `created_by` INT DEFAULT NULL COMMENT 'manager who configured this mapping',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permissions_role_permission` (`role`, `permission_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  KEY `idx_role_permissions_role` (`role`),
  KEY `idx_role_permissions_created_by` (`created_by`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `PERMISSIONS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_created_by` FOREIGN KEY (`created_by`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Maps roles to permissions';


CREATE TABLE `USER_SESSIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL COMMENT 'Owner of the session',
  `refresh_token_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed refresh token, never plaintext',
  `refresh_token_jti` CHAR(36) NOT NULL COMMENT 'Unique token id for rotation/reuse detection',
  `device_type` ENUM('web', 'mobile', 'tablet', 'desktop', 'unknown') NOT NULL DEFAULT 'unknown',
  `device_name` VARCHAR(150) DEFAULT NULL COMMENT 'Friendly device name',
  `device_os` VARCHAR(80) DEFAULT NULL COMMENT 'Operating system',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'Raw user-agent string',
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Time refresh token issued',
  `refresh_token_expires_at` TIMESTAMP NOT NULL COMMENT 'Refresh token expiration, ~60 days',
  `last_activity_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Last authenticated activity or refresh',
  `revoked_at` TIMESTAMP DEFAULT NULL COMMENT 'NULL = active',
  `revoked_by_user_id` INT DEFAULT NULL COMMENT 'User/manager who revoked this session',
  `revoke_reason` VARCHAR(50) DEFAULT NULL COMMENT 'logout, logout_all, password_change, manager_force_logout, suspicious, token_reuse',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_sessions_jti` (`refresh_token_jti`),
  KEY `idx_user_sessions_user_active` (`user_id`, `revoked_at`, `refresh_token_expires_at`),
  KEY `idx_user_sessions_last_activity` (`last_activity_at`),
  KEY `idx_user_sessions_ip` (`ip_address`),
  KEY `idx_user_sessions_revoked_by` (`revoked_by_user_id`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_sessions_revoked_by` FOREIGN KEY (`revoked_by_user_id`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Per-device refresh-token sessions';


CREATE TABLE `LOGIN_LOGS` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL COMMENT 'Resolved user if known',
  `login_identifier` VARCHAR(150) NOT NULL COMMENT 'Email/phone/username used in login',
  `login_method` ENUM('password', 'walk_in_auto_login') NOT NULL COMMENT 'Authentication method used',
  `success` TINYINT(1) NOT NULL COMMENT '1 = success, 0 = failure',
  `failure_reason` VARCHAR(100) DEFAULT NULL COMMENT 'invalid_credentials, account_locked, user_not_found, must_change_password, etc.',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'Raw user-agent string',
  `session_id` INT DEFAULT NULL COMMENT 'Created session if login succeeded',
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata_json` JSON DEFAULT NULL COMMENT 'Optional extra metadata like event_id or device_info',
  PRIMARY KEY (`id`),
  KEY `idx_login_logs_user_time` (`user_id`, `attempted_at`),
  KEY `idx_login_logs_identifier_time` (`login_identifier`, `attempted_at`),
  KEY `idx_login_logs_ip_time` (`ip_address`, `attempted_at`),
  KEY `idx_login_logs_success_time` (`success`, `attempted_at`),
  KEY `idx_login_logs_session` (`session_id`),
  CONSTRAINT `fk_login_logs_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_login_logs_session` FOREIGN KEY (`session_id`) REFERENCES `USER_SESSIONS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Audit log for every login attempt';


CREATE TABLE `APP_SETTINGS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL COMMENT 'Khóa setting (VD: storage_base_path, upload_max_size)',
  `setting_value` TEXT DEFAULT NULL COMMENT 'Giá trị setting',
  `description` VARCHAR(300) DEFAULT NULL COMMENT 'Mô tả cho manager hiểu ý nghĩa setting',
  `updated_by` INT DEFAULT NULL COMMENT 'manager cập nhật lần cuối',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_settings_key` (`setting_key`),
  KEY `idx_app_settings_updated_by` (`updated_by`),
  CONSTRAINT `fk_app_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Bảng key-value tập trung cho toàn bộ cấu hình manager';


CREATE TABLE `FACILITIES` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL COMMENT 'Tên cơ sở nhận',
  `type` ENUM('station', 'office') NOT NULL COMMENT 'station = trạm thu hồi, office = cơ sở/văn phòng công ty',
  `address` VARCHAR(300) DEFAULT NULL,
  `latitude` DECIMAL(10, 7) DEFAULT NULL,
  `longitude` DECIMAL(10, 7) DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked sẽ ẩn khỏi Portal',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_facilities_status` (`status`),
  INDEX `idx_facilities_type` (`type`),
  INDEX `idx_facilities_deleted` (`deleted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Cơ sở/trạm thu hồi của công ty';


CREATE TABLE `USERS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) UNIQUE COMMENT 'SĐT liên hệ (tùy chọn), không dùng cho xác thực',
  `email` VARCHAR(150) UNIQUE COMMENT 'Email đăng nhập và nhận thông báo - kênh liên lạc chính, kể cả user vãng lai',
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời điểm email xác minh qua link của Laravel. NULL = chưa xác minh',
  `password` VARCHAR(255) DEFAULT NULL COMMENT 'Hash mật khẩu (chuẩn Laravel Auth). NULL với tài khoản Walk-in tự sinh. Walk-in nhận mật khẩu tạm gửi qua email',
  `avatar_url` VARCHAR(500) DEFAULT NULL COMMENT 'Ảnh đại diện user',
  `remember_token` VARCHAR(100) DEFAULT NULL COMMENT 'Token "remember me" chuẩn Laravel Auth',
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = đang dùng mật khẩu tạm, buộc đổi ở lần đăng nhập kế tiếp',
  `role` ENUM('user', 'staff', 'manager') NOT NULL DEFAULT 'user',
  `facility_id` INT DEFAULT NULL COMMENT 'Cơ sở trực thuộc. Bắt buộc với staff và manager (manager là chủ cơ sở, chỉ quản lý event/đơn/staff cùng cơ sở). NULL với role=user',
  `is_walk_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = tài khoản tạo tự động từ QR sự kiện',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_facility` (`facility_id`),
  INDEX `idx_users_phone` (`phone`),
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_deleted` (`deleted_at`),
  CONSTRAINT `fk_users_facility` FOREIGN KEY (`facility_id`) REFERENCES `FACILITIES` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Người dùng: User, Staff, manager (tương thích Auth Laravel: password + email_verified_at + remember_token)';


--TABLE PASSWORD_RESET_TOKENS (Bảng chuẩn Laravel - reset mật khẩu bằng link)
CREATE TABLE `PASSWORD_RESET_TOKENS` (
  `email` VARCHAR(150) NOT NULL COMMENT 'Email yêu cầu reset - PK. Mỗi email chỉ giữ token mới nhất',
  `token` VARCHAR(255) NOT NULL COMMENT 'Hash token reset gửi qua link quên mật khẩu',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời điểm phát hành token, dùng tính hết hạn',
  PRIMARY KEY (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Bảng chuẩn Laravel cho Password Broker (reset mật khẩu bằng link). Kênh reset mật khẩu duy nhất của hệ thống';


--TABLE SESSIONS (Bảng chuẩn Laravel - session driver = database)
CREATE TABLE `SESSIONS` (
  `id` VARCHAR(255) NOT NULL COMMENT 'Session ID chuẩn Laravel - PK',
  `user_id` INT DEFAULT NULL COMMENT 'FK => USERS. NULL với khách chưa đăng nhập',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 / IPv6',
  `user_agent` TEXT DEFAULT NULL COMMENT 'User-Agent string',
  `payload` LONGTEXT NOT NULL COMMENT 'Dữ liệu session đã serialize (Laravel quản lý)',
  `last_activity` INT NOT NULL COMMENT 'Unix timestamp lần hoạt động cuối',
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Bảng chuẩn Laravel cho session driver database (web guard). Khác USER_SESSIONS (kho refresh token JWT cho API đa thiết bị)';


CREATE TABLE `EVENTS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `location` VARCHAR(300) NOT NULL COMMENT 'Địa điểm tổ chức bên ngoài (trường học, công viên...)',
  `qr_code` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Chỉ active trong khung giờ sự kiện',
  `image_url` VARCHAR(500) DEFAULT NULL COMMENT 'Ảnh poster/banner sự kiện',
  `start_time` TIMESTAMP NOT NULL,
  `end_time` TIMESTAMP NOT NULL,
  `expired_at` TIMESTAMP DEFAULT NULL COMMENT 'Mốc auto-clean khỏi danh sách',
  `status` ENUM('upcoming', 'active', 'ended', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_events_status` (`status`, `start_time`),
  INDEX `idx_events_deleted` (`deleted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Sự kiện thu gom rác tái chế. manager tạo sự kiện được ghi trong SYSTEM_LOGS, không lưu trực tiếp trên bảng này. Cơ sở tổ chức suy ra từ facility của manager tạo event';


--TABLE MEASUREMENT_UNITS (Danh mục đơn vị đo - manager quản lý)
CREATE TABLE `MEASUREMENT_UNITS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT 'Tên đơn vị hiển thị',
  `symbol` VARCHAR(20) NOT NULL COMMENT 'Ký hiệu đơn vị (kg, g, mg, l, ml, ...)',
  `category` VARCHAR(30) NOT NULL COMMENT 'weight | volume | count',
  `is_system` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = đơn vị gốc do manager tạo',
  `created_by` INT DEFAULT NULL COMMENT 'manager tạo đơn vị. NULL nếu là đơn vị gốc hệ thống',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_measurement_units_category` (`category`),
  INDEX `idx_measurement_units_system` (`is_system`),
  INDEX `idx_measurement_units_deleted` (`deleted_at`),
  CONSTRAINT `fk_measurement_units_creator` FOREIGN KEY (`created_by`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Danh mục đơn vị đo do manager quản lý, user chỉ chọn từ danh sách có sẵn';


--TABLE HANDOVER_REQUESTS (Đơn chuyển giao rác)
CREATE TABLE `HANDOVER_REQUESTS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `facility_id` INT NOT NULL,
  `staff_id` INT DEFAULT NULL COMMENT 'NULL cho tới khi manager phân công. Chỉ Staff cùng cơ sở với đơn (USERS.facility_id = facility_id) mới được phân công',
  `event_id` INT DEFAULT NULL COMMENT 'NULL với đơn thường; có giá trị với Đơn hỏa tốc tại sự kiện',
  `classification_type` VARCHAR(50) DEFAULT NULL COMMENT 'Hình thức phân loại: cleaned_flattened=rửa&ép dẹp | cleaned=chỉ rửa | as_is=giữ nguyên | mixed=hỗn hợp',
  `estimated_weight` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Giá trị đo ước tính của User',
  `unit_id` INT DEFAULT NULL COMMENT 'FK => MEASUREMENT_UNITS. Đơn vị đo của estimated_weight',
  `appointment_time` TIMESTAMP DEFAULT NULL,
  `expired_at` TIMESTAMP DEFAULT NULL COMMENT 'Mốc auto-cancel nếu User không đến',
  `reschedule_count` INT NOT NULL DEFAULT 0 COMMENT 'Tối đa 2, vượt sẽ tự hủy',
  `status` ENUM(
    'pending',
    'approved',
    'completed',
    'rejected',
    'cancelled',
    'expired'
  ) NOT NULL DEFAULT 'pending',
  `reject_reason` VARCHAR(500) DEFAULT NULL COMMENT 'Bắt buộc khi status = rejected',
  `cancel_reason` ENUM(
    'user_cancel',
    'staff_cancel',
    'auto_expire',
    'reschedule_exceeded'
  ) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_handover_user_status` (`user_id`, `status`),
  INDEX `idx_handover_facility_time` (`facility_id`, `appointment_time`),
  INDEX `idx_handover_staff` (`staff_id`),
  INDEX `idx_handover_event` (`event_id`),
  INDEX `idx_handover_status` (`status`),
  CONSTRAINT `fk_handover_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_handover_facility` FOREIGN KEY (`facility_id`) REFERENCES `FACILITIES` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_handover_staff` FOREIGN KEY (`staff_id`) REFERENCES `USERS` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_handover_event` FOREIGN KEY (`event_id`) REFERENCES `EVENTS` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_handover_unit` FOREIGN KEY (`unit_id`) REFERENCES `MEASUREMENT_UNITS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Yêu cầu chuyển giao rác tái chế';


--TABLE WASTE_TYPES (Danh mục loại rác - chỉ manager tạo phân loại gốc)
CREATE TABLE `WASTE_TYPES` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Tên loại rác',
  `icon` VARCHAR(50) DEFAULT NULL COMMENT 'Icon/emoji hiển thị (tùy chọn)',
  `is_system` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = phân loại gốc do manager tạo, 0 = loại do User tự thêm',
  `created_by` INT DEFAULT NULL COMMENT 'manager/User đã thêm loại rác này. NULL nếu là phân loại gốc',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_waste_types_system` (`is_system`),
  INDEX `idx_waste_types_deleted` (`deleted_at`),
  CONSTRAINT `fk_waste_types_creator` FOREIGN KEY (`created_by`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Danh mục loại rác do manager quản lý, User chỉ chọn từ danh sách có sẵn';


--TABLE HANDOVER_WASTE_ITEMS (Loại rác được chọn trong 1 Đơn - N-N)
CREATE TABLE `HANDOVER_WASTE_ITEMS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `request_id` INT NOT NULL,
  `waste_type_id` INT NOT NULL,
  `weight` DECIMAL(10, 2) NOT NULL COMMENT 'Giá trị đo User nhập cho từng loại rác',
  `unit_id` INT NOT NULL COMMENT 'FK => MEASUREMENT_UNITS. Đơn vị đo của weight',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_request_waste_type` (`request_id`, `waste_type_id`),
  INDEX `idx_waste_items_type` (`waste_type_id`),
  INDEX `idx_waste_items_unit` (`unit_id`),
  CONSTRAINT `fk_waste_items_request` FOREIGN KEY (`request_id`) REFERENCES `HANDOVER_REQUESTS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_waste_items_type` FOREIGN KEY (`waste_type_id`) REFERENCES `WASTE_TYPES` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_waste_items_unit` FOREIGN KEY (`unit_id`) REFERENCES `MEASUREMENT_UNITS` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Bảng nối N-N: 1 Đơn chuyển giao có thể chọn nhiều Loại rác, kèm weight/unit cho từng loại';


--TABLE HANDOVER_WEIGHT_LOGS (Lịch sử cân thực tế)
CREATE TABLE `HANDOVER_WEIGHT_LOGS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `request_id` INT NOT NULL,
  `weight` DECIMAL(10, 2) NOT NULL COMMENT 'Giá trị đo thực tế',
  `unit_id` INT NOT NULL COMMENT 'FK => MEASUREMENT_UNITS. Đơn vị đo của weight',
  `recorded_by` INT NOT NULL COMMENT 'Staff thực hiện cân',
  `notes` TEXT DEFAULT NULL COMMENT 'Ghi chú khi cân',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_weight_logs_request` (`request_id`),
  INDEX `idx_weight_logs_staff` (`recorded_by`),
  INDEX `idx_weight_logs_unit` (`unit_id`),
  CONSTRAINT `fk_weight_logs_request` FOREIGN KEY (`request_id`) REFERENCES `HANDOVER_REQUESTS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_weight_logs_staff` FOREIGN KEY (`recorded_by`) REFERENCES `USERS` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_weight_logs_unit` FOREIGN KEY (`unit_id`) REFERENCES `MEASUREMENT_UNITS` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Tách khỏi HANDOVER_REQUESTS để hỗ trợ nhiều lần cân và audit ai cân, lúc nào';


--TABLE EVENT_STAFF_ASSIGNMENTS (Phân công Staff cho Sự kiện)
CREATE TABLE `EVENT_STAFF_ASSIGNMENTS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `staff_id` INT NOT NULL,
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_staff` (`event_id`, `staff_id`),
  INDEX `idx_staff_events` (`staff_id`),
  CONSTRAINT `fk_event_assignment_event` FOREIGN KEY (`event_id`) REFERENCES `EVENTS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_assignment_staff` FOREIGN KEY (`staff_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Phân công nhân viên cho sự kiện. 1 staff chỉ được quản lý 1 event tại 1 thời điểm, chặn overlap bằng trigger';

DELIMITER //
CREATE TRIGGER `trg_event_staff_assignments_bi`
BEFORE INSERT ON `EVENT_STAFF_ASSIGNMENTS`
FOR EACH ROW
BEGIN
  DECLARE v_overlap INT DEFAULT 0;
  SELECT COUNT(*) INTO v_overlap
  FROM `EVENT_STAFF_ASSIGNMENTS` esa
  JOIN `EVENTS` e_existing ON e_existing.id = esa.event_id
  JOIN `EVENTS` e_new ON e_new.id = NEW.event_id
  WHERE esa.staff_id = NEW.staff_id
    AND e_existing.deleted_at IS NULL
    AND e_new.deleted_at IS NULL
    AND e_existing.start_time < e_new.end_time
    AND e_existing.end_time > e_new.start_time;

  IF v_overlap > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Staff đã được phân công cho một event khác trong cùng thời gian';
  END IF;
END//
CREATE TRIGGER `trg_event_staff_assignments_bu`
BEFORE UPDATE ON `EVENT_STAFF_ASSIGNMENTS`
FOR EACH ROW
BEGIN
  DECLARE v_overlap INT DEFAULT 0;
  SELECT COUNT(*) INTO v_overlap
  FROM `EVENT_STAFF_ASSIGNMENTS` esa
  JOIN `EVENTS` e_existing ON e_existing.id = esa.event_id
  JOIN `EVENTS` e_new ON e_new.id = NEW.event_id
  WHERE esa.staff_id = NEW.staff_id
    AND esa.id <> OLD.id
    AND e_existing.deleted_at IS NULL
    AND e_new.deleted_at IS NULL
    AND e_existing.start_time < e_new.end_time
    AND e_existing.end_time > e_new.start_time;

  IF v_overlap > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Staff đã được phân công cho một event khác trong cùng thời gian';
  END IF;
END//
DELIMITER ;


--TABLE EVENT_REWARDS (Quà tặng sự kiện)
CREATE TABLE `EVENT_REWARDS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL COMMENT 'Tên quà',
  `description` TEXT,
  `quantity` INT NOT NULL DEFAULT 0 COMMENT 'Số lượng ban đầu',
  `remaining` INT NOT NULL DEFAULT 0 COMMENT 'Trừ dần sau mỗi lần Minigame trúng thưởng - quà vật lý, không tốn điểm ví',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rewards_event` (`event_id`),
  CONSTRAINT `fk_rewards_event` FOREIGN KEY (`event_id`) REFERENCES `EVENTS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Quà tặng trong sự kiện';


--TABLE EVENT_REGISTRATIONS (Đăng ký sự kiện)
CREATE TABLE `EVENT_REGISTRATIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `registration_type` ENUM('visit', 'handover', 'walkin') NOT NULL COMMENT 'visit=tham quan tìm hiểu (không nộp đồ), handover=đăng ký nộp đồ tại event, walkin=khách vãng lai',
  `status` ENUM('registered', 'attended', 'absent') NOT NULL DEFAULT 'registered',
  `minigame_status` ENUM('not_eligible', 'unlocked', 'played') NOT NULL DEFAULT 'not_eligible',
  `checked_in_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_user` (`event_id`, `user_id`),
  INDEX `idx_registration_user` (`user_id`),
  CONSTRAINT `fk_registration_event` FOREIGN KEY (`event_id`) REFERENCES `EVENTS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_registration_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Đăng ký tham gia sự kiện';


--TABLE USER_WALLETS (Ví điểm xanh)
CREATE TABLE `USER_WALLETS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `balance` INT NOT NULL DEFAULT 0 COMMENT 'Số điểm hiện tại',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wallet_user` (`user_id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Ví điểm xanh của User';


--TABLE POINT_EARNED (Điểm kiếm được)
CREATE TABLE `POINT_EARNED` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `wallet_id` INT NOT NULL,
  `points` INT NOT NULL COMMENT 'Luôn dương - số điểm kiếm được',
  `source_type` ENUM(
    'handover',
    'event_minigame',
    'content_read',
    'manager_adjust',
    'redemption_refund',
    'sticker_bonus'
  ) NOT NULL,
  `reference_id` INT DEFAULT NULL COMMENT 'ID của handover / event_registration / content_read / redemption / sticker tương ứng. NULL với manager_adjust',
  `description` VARCHAR(300) DEFAULT NULL COMMENT 'Hiển thị trong lịch sử giao dịch',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_earned_wallet` (`wallet_id`, `created_at`),
  INDEX `idx_earned_source` (`source_type`, `reference_id`),
  CONSTRAINT `fk_earned_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `USER_WALLETS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Lịch sử cộng điểm. points luôn dương';


--TABLE POINT_SPENT (Điểm đã tiêu)
CREATE TABLE `POINT_SPENT` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `wallet_id` INT NOT NULL,
  `points` INT NOT NULL COMMENT 'Luôn dương - số điểm đã tiêu/bị trừ',
  `source_type` ENUM('redemption', 'manager_adjust') NOT NULL,
  `reference_id` INT DEFAULT NULL COMMENT 'ID của redemption tương ứng. NULL với manager_adjust',
  `description` VARCHAR(300) DEFAULT NULL COMMENT 'Hiển thị trong lịch sử giao dịch',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_spent_wallet` (`wallet_id`, `created_at`),
  INDEX `idx_spent_source` (`source_type`, `reference_id`),
  CONSTRAINT `fk_spent_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `USER_WALLETS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Lịch sử trừ điểm. points luôn dương. USER_WALLETS.balance = SUM(POINT_EARNED.points) - SUM(POINT_SPENT.points)';


--TABLE EDUCATIONAL_CONTENTS (Nội dung giáo dục môi trường)
CREATE TABLE `EDUCATIONAL_CONTENTS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL COMMENT 'Nội dung dạng HTML (rich text). Ảnh minh họa embed qua <img src="...">. Bài gốc có thể là PDF, ảnh bìa lưu ở thumbnail_url',
  `author_id` INT NOT NULL COMMENT 'Staff soạn bài',
  `approved_by_id` INT DEFAULT NULL COMMENT 'manager duyệt',
  `thumbnail_url` VARCHAR(500) DEFAULT NULL COMMENT 'Ảnh bìa/thumbnail của bài học',
  `status` ENUM('pending', 'published', 'rejected') NOT NULL DEFAULT 'pending',
  `timer_seconds` INT NOT NULL DEFAULT 120 COMMENT 'Thời gian tối thiểu đọc để nhận điểm',
  `points_reward` INT NOT NULL DEFAULT 0 COMMENT 'Điểm cộng khi đọc xong',
  `sticker_set_id` INT DEFAULT NULL COMMENT 'Bộ sticker thưởng khi đọc xong bài. NULL = bài không có thưởng sticker',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_content_status` (`status`),
  INDEX `idx_content_author` (`author_id`),
  INDEX `idx_content_sticker_set` (`sticker_set_id`),
  INDEX `idx_content_deleted` (`deleted_at`),
  CONSTRAINT `fk_content_author` FOREIGN KEY (`author_id`) REFERENCES `USERS` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_content_approver` FOREIGN KEY (`approved_by_id`) REFERENCES `USERS` (`id`) ON DELETE
  SET NULL,
  CONSTRAINT `fk_content_sticker_set` FOREIGN KEY (`sticker_set_id`) REFERENCES `STICKER_SETS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Bài học giáo dục môi trường';


--TABLE CONTENT_READS (Lịch sử đọc bài - Enforce Timer & Quota)
CREATE TABLE `CONTENT_READS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `content_id` INT NOT NULL,
  `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'NULL nếu chưa đủ Timer',
  `rewarded` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 khi đã cộng điểm và thỏa timer/quota ngày',
  `read_date` DATE NOT NULL COMMENT 'Ngày đọc theo local time để reset quota mỗi ngày',
  PRIMARY KEY (`id`),
  INDEX `idx_reads_user_date` (`user_id`, `read_date`),
  INDEX `idx_reads_content` (`content_id`),
  INDEX `idx_reads_content_date` (`content_id`, `read_date`),
  CONSTRAINT `fk_reads_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reads_content` FOREIGN KEY (`content_id`) REFERENCES `EDUCATIONAL_CONTENTS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Lịch sử đọc bài để enforce quota theo ngày: tối đa 10 lượt rewarded/ngày, mỗi content tối đa 2 lượt rewarded/ngày, timer tối thiểu 120 giây';


--TABLE STICKER_SETS (Bộ sưu tập sticker)
CREATE TABLE `STICKER_SETS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL COMMENT 'Tên bộ sưu tập (VD: Đại dương, Rừng xanh)',
  `theme` VARCHAR(100) DEFAULT NULL COMMENT 'Chủ đề mô tả thêm',
  `cover_image_url` VARCHAR(500) DEFAULT NULL COMMENT 'Ảnh đại diện bộ',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked = tạm ẩn, không rơi sticker từ bộ này',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sticker_sets_status` (`status`),
  INDEX `idx_sticker_sets_deleted` (`deleted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Bộ sưu tập sticker, manager CRUD';


--TABLE STICKERS (Từng sticker trong 1 bộ)
CREATE TABLE `STICKERS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `set_id` INT NOT NULL COMMENT 'Thuộc bộ sưu tập nào',
  `name` VARCHAR(150) NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `rarity` ENUM('common', 'rare', 'special') NOT NULL DEFAULT 'common',
  `drop_weight` INT NOT NULL COMMENT 'Trọng số rơi. manager tự chỉnh tỉ lệ',
  `redeem_quantity_required` INT NOT NULL DEFAULT 1 COMMENT 'Cần đủ bao nhiêu cái mới đổi được 1 lần vật lý',
  `bonus_points` INT NOT NULL DEFAULT 0 COMMENT 'Điểm tự cộng vào POINT_EARNED khi LẦN ĐẦU sở hữu loại này',
  `unlocks_content_id` INT DEFAULT NULL COMMENT 'Mở khóa bài đặc biệt khi lần đầu sở hữu. NULL = không có',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked = tạm ẩn khỏi vòng rơi',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_stickers_set` (`set_id`),
  INDEX `idx_stickers_rarity` (`rarity`),
  INDEX `idx_stickers_unlocks` (`unlocks_content_id`),
  INDEX `idx_stickers_deleted` (`deleted_at`),
  CONSTRAINT `fk_stickers_set` FOREIGN KEY (`set_id`) REFERENCES `STICKER_SETS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stickers_unlocks` FOREIGN KEY (`unlocks_content_id`) REFERENCES `EDUCATIONAL_CONTENTS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Từng sticker trong 1 bộ';


--TABLE USER_STICKERS (Số lượng sticker user đang giữ)
CREATE TABLE `USER_STICKERS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `sticker_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0 COMMENT 'Số đang giữ (đã trừ phần đã đổi vật lý)',
  `total_obtained` INT NOT NULL DEFAULT 0 COMMENT 'Tổng từng nhận được, không giảm khi đổi - hiển thị thành tích',
  `first_obtained_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Lần đầu có',
  `last_obtained_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Lần gần nhất có thêm',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_sticker` (`user_id`, `sticker_id`),
  INDEX `idx_user_stickers_sticker` (`sticker_id`),
  CONSTRAINT `fk_user_stickers_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_stickers_sticker` FOREIGN KEY (`sticker_id`) REFERENCES `STICKERS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '1 dòng/user/loại sticker, cộng dồn khi nhận trùng';


--TABLE STICKER_OBTAIN_LOGS (Lịch sử mỗi lần nhận sticker)
CREATE TABLE `STICKER_OBTAIN_LOGS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `sticker_id` INT NOT NULL,
  `source_content_id` INT DEFAULT NULL COMMENT 'Bài đọc đã ra sticker này. NULL nếu từ nguồn khác',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_obtain_logs_user` (`user_id`, `created_at`),
  INDEX `idx_obtain_logs_sticker` (`sticker_id`),
  INDEX `idx_obtain_logs_content` (`source_content_id`),
  CONSTRAINT `fk_obtain_logs_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_obtain_logs_sticker` FOREIGN KEY (`sticker_id`) REFERENCES `STICKERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_obtain_logs_content` FOREIGN KEY (`source_content_id`) REFERENCES `EDUCATIONAL_CONTENTS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Lịch sử mỗi lần nhận sticker kể cả trùng, phục vụ tính danh hiệu theo kỳ';


--TABLE STICKER_REWARD_ITEMS (Danh mục vật phẩm quà đổi sticker - manager cấu hình)
CREATE TABLE `STICKER_REWARD_ITEMS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL COMMENT 'Tên vật phẩm (VD: Sticker dán, Kẹo, Sữa). manager tự đặt, không hardcode',
  `image_url` VARCHAR(500) DEFAULT NULL COMMENT 'Ảnh vật phẩm. Upload lên server, lưu path tương đối',
  `description` TEXT DEFAULT NULL COMMENT 'Mô tả vật phẩm',
  `stock` INT NOT NULL DEFAULT 0 COMMENT 'Số lượng còn lại trong kho. manager tự chỉnh, trừ dần khi đổi',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked = tạm ngừng cho đổi',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_reward_items_status` (`status`),
  INDEX `idx_reward_items_deleted` (`deleted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Danh mục vật phẩm vật lý làm quà đổi sticker, manager CRUD ảnh + tên + tồn kho';


--TABLE STICKER_REWARD_RULES (Cấu hình bó quà: 1 sticker ra những vật phẩm nào)
CREATE TABLE `STICKER_REWARD_RULES` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sticker_id` INT NOT NULL COMMENT 'Loại sticker ảo dùng để đổi',
  `reward_item_id` INT NOT NULL COMMENT 'Vật phẩm nhận được',
  `quantity` INT NOT NULL DEFAULT 1 COMMENT 'Đổi 1 lần sticker này ra bao nhiêu vật phẩm (VD: x1 sticker dán, x2 kẹo, x3 sữa)',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked = tạm ngừng áp dụng rule này',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reward_rule` (`sticker_id`, `reward_item_id`),
  INDEX `idx_reward_rules_sticker` (`sticker_id`),
  INDEX `idx_reward_rules_item` (`reward_item_id`),
  CONSTRAINT `fk_reward_rules_sticker` FOREIGN KEY (`sticker_id`) REFERENCES `STICKERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reward_rules_item` FOREIGN KEY (`reward_item_id`) REFERENCES `STICKER_REWARD_ITEMS` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Cấu hình bó quà: 1 lần đổi sticker X ra những vật phẩm nào, mỗi thứ bao nhiêu';


--TABLE STICKER_REDEMPTIONS (Đổi sticker vật lý - cho chọn pickup/delivery)
CREATE TABLE `STICKER_REDEMPTIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `sticker_id` INT NOT NULL,
  `quantity_used` INT NOT NULL COMMENT 'Số lượng sticker ảo đã trừ để đổi 1 lần',
  `fulfillment_method` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup' COMMENT 'pickup = nhận tại cơ sở, delivery = ship tận nhà',
  `status` ENUM('pending', 'shipping', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT 'pending=chờ xử lý, shipping=đang giao (chỉ delivery), fulfilled=đã giao/đã nhận, cancelled=hủy&hoàn sticker',
  `facility_id` INT DEFAULT NULL COMMENT 'Cơ sở nhận (khi pickup). NULL khi delivery',
  `staff_id` INT DEFAULT NULL COMMENT 'Staff xác nhận giao (pickup) hoặc đóng gói (delivery). NULL khi chưa xử lý',
  `recipient_name` VARCHAR(150) DEFAULT NULL COMMENT 'Tên người nhận (khi delivery)',
  `recipient_phone` VARCHAR(20) DEFAULT NULL COMMENT 'SĐT người nhận (khi delivery)',
  `shipping_address` VARCHAR(500) DEFAULT NULL COMMENT 'Địa chỉ giao hàng (khi delivery)',
  `shipping_note` VARCHAR(300) DEFAULT NULL COMMENT 'Ghi chú giao hàng',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sticker_redeem_user` (`user_id`, `created_at`),
  INDEX `idx_sticker_redeem_user_status` (`user_id`, `status`),
  INDEX `idx_sticker_redeem_sticker` (`sticker_id`),
  INDEX `idx_sticker_redeem_facility` (`facility_id`),
  INDEX `idx_sticker_redeem_method` (`fulfillment_method`),
  CONSTRAINT `fk_sticker_redeem_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sticker_redeem_sticker` FOREIGN KEY (`sticker_id`) REFERENCES `STICKERS` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_sticker_redeem_facility` FOREIGN KEY (`facility_id`) REFERENCES `FACILITIES` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sticker_redeem_staff` FOREIGN KEY (`staff_id`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Lịch sử đổi sticker vật lý, cho chọn pickup tại cơ sở hoặc delivery ship tận nhà';


--TABLE STICKER_REDEMPTION_ITEMS (Snapshot vật phẩm đã giao mỗi lần đổi)
CREATE TABLE `STICKER_REDEMPTION_ITEMS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `redemption_id` INT NOT NULL COMMENT 'Thuộc lần đổi nào (STICKER_REDEMPTIONS)',
  `reward_item_id` INT DEFAULT NULL COMMENT 'Vật phẩm gốc (STICKER_REWARD_ITEMS). NULL nếu vật phẩm đã bị xóa sau này',
  `item_name` VARCHAR(150) NOT NULL COMMENT 'Snapshot tên vật phẩm tại thời điểm đổi',
  `item_image_url` VARCHAR(500) DEFAULT NULL COMMENT 'Snapshot ảnh vật phẩm tại thời điểm đổi',
  `quantity` INT NOT NULL COMMENT 'Số lượng vật phẩm này đã giao (chốt theo rule lúc đổi)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_redeem_items_redemption` (`redemption_id`),
  INDEX `idx_redeem_items_item` (`reward_item_id`),
  CONSTRAINT `fk_redeem_items_redemption` FOREIGN KEY (`redemption_id`) REFERENCES `STICKER_REDEMPTIONS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redeem_items_item` FOREIGN KEY (`reward_item_id`) REFERENCES `STICKER_REWARD_ITEMS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Snapshot đơn: chốt chính xác vật phẩm + số lượng đã giao trong 1 lần đổi sticker';


--TABLE TITLE_DEFINITIONS (Định nghĩa danh hiệu - manager CRUD)
CREATE TABLE `TITLE_DEFINITIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL COMMENT 'Tên danh hiệu (VD: Nhà sưu tập nhí)',
  `description` TEXT DEFAULT NULL,
  `icon_url` VARCHAR(500) DEFAULT NULL,
  `criteria_type` VARCHAR(50) NOT NULL COMMENT 'sticker_count | rare_sticker_count | content_read_count - mở rộng được',
  `threshold` INT NOT NULL COMMENT 'Ngưỡng cần đạt trong kỳ',
  `period_days` INT NOT NULL COMMENT 'Số ngày tính ngược để xét.',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked = tạm ngừng cấp danh hiệu này',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_title_defs_status` (`status`),
  INDEX `idx_title_defs_criteria` (`criteria_type`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Định nghĩa danh hiệu, manager CRUD toàn bộ tiêu chí';


--TABLE USER_TITLES (Danh hiệu user đã/đang giữ)
CREATE TABLE `USER_TITLES` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title_id` INT NOT NULL,
  `earned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm đạt danh hiệu',
  `expires_at` TIMESTAMP NOT NULL COMMENT '= earned_at + period_days, chốt tại thời điểm cấp',
  PRIMARY KEY (`id`),
  INDEX `idx_user_titles_user` (`user_id`, `expires_at`),
  INDEX `idx_user_titles_title` (`title_id`),
  CONSTRAINT `fk_user_titles_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_titles_title` FOREIGN KEY (`title_id`) REFERENCES `TITLE_DEFINITIONS` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Danh hiệu user giữ, hết hiệu lực khi expires_at trôi qua';



--TABLE REWARD_CATALOG (Danh mục quà đổi bằng điểm ví)
CREATE TABLE `REWARD_CATALOG` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL COMMENT 'Tên quà',
  `description` TEXT,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `points_cost` INT NOT NULL COMMENT 'Số điểm cần để đổi 1 phần quà',
  `stock` INT NOT NULL DEFAULT 0 COMMENT 'Số lượng còn lại trong kho, trừ dần khi User đổi',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked = tạm ngừng cho đổi',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_catalog_status` (`status`),
  INDEX `idx_catalog_deleted` (`deleted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Danh mục quà đổi bằng điểm ví';


--TABLE REDEMPTIONS (Lịch sử đổi quà bằng điểm)
CREATE TABLE `REDEMPTIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `reward_id` INT NOT NULL COMMENT 'Quà trong REWARD_CATALOG',
  `points_spent` INT NOT NULL COMMENT 'Điểm đã trừ tại thời điểm đổi (chốt theo points_cost lúc đó)',
  `quantity` INT NOT NULL DEFAULT 1 COMMENT 'Số phần quà đổi trong 1 lần',
  `status` ENUM('pending', 'shipping', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT 'pending=chờ nhận, shipping=đang giao (chỉ delivery), fulfilled=đã giao, cancelled=hủy&hoàn điểm',
  `fulfillment_method` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup' COMMENT 'pickup = nhận tại cơ sở, delivery = ship tận nhà',
  `recipient_name` VARCHAR(150) DEFAULT NULL COMMENT 'Tên người nhận (khi delivery)',
  `recipient_phone` VARCHAR(20) DEFAULT NULL COMMENT 'SĐT người nhận (khi delivery)',
  `shipping_address` VARCHAR(500) DEFAULT NULL COMMENT 'Địa chỉ giao hàng (khi delivery)',
  `shipping_note` VARCHAR(300) DEFAULT NULL COMMENT 'Ghi chú giao hàng',
  `transaction_id` INT DEFAULT NULL COMMENT 'Giao dịch trừ điểm tương ứng trong POINT_SPENT',
  `fulfilled_by_id` INT DEFAULT NULL COMMENT 'Staff/manager xác nhận giao quà',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_redemptions_user` (`user_id`, `status`),
  INDEX `idx_redemptions_reward` (`reward_id`),
  INDEX `idx_redemptions_transaction` (`transaction_id`),
  INDEX `idx_redemptions_method` (`fulfillment_method`),
  CONSTRAINT `fk_redemptions_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redemptions_reward` FOREIGN KEY (`reward_id`) REFERENCES `REWARD_CATALOG` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_redemptions_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `POINT_SPENT` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_redemptions_fulfiller` FOREIGN KEY (`fulfilled_by_id`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Lịch sử đổi quà bằng điểm ví';


--TABLE SYSTEM_LOGS (Audit log)
CREATE TABLE `SYSTEM_LOGS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(30) NOT NULL COMMENT 'handover | event | user | content | facility',
  `entity_id` INT NOT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'create | approve | reject | reschedule | complete | cancel ...',
  `old_status` VARCHAR(30) DEFAULT NULL,
  `new_status` VARCHAR(30) DEFAULT NULL,
  `details` TEXT DEFAULT NULL COMMENT 'JSON chi tiết payload thay đổi',
  `performed_by_user_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_logs_entity` (`entity_type`, `entity_id`),
  INDEX `idx_logs_user` (`performed_by_user_id`),
  INDEX `idx_logs_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`performed_by_user_id`) REFERENCES `USERS` (`id`) ON DELETE
  SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Audit log cho mọi thao tác';


--TABLE MINI_GAMES (Định nghĩa mini game tương tác - real-time)
CREATE TABLE `MINI_GAMES` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL COMMENT 'Tên game (VD: Đua phân loại rác)',
  `game_type` VARCHAR(50) NOT NULL COMMENT 'quiz | sorting_race | bingo | matching | wheel | guess_image ... - mở rộng được, không ràng ENUM cứng',
  `description` TEXT DEFAULT NULL,
  `config_json` JSON DEFAULT NULL COMMENT 'Cấu hình + dữ liệu game tùy theo game_type (câu hỏi, đáp án, thời gian, hình ảnh...). Backend parse theo game_type',
  `content_id` INT DEFAULT NULL COMMENT 'Gắn với bài học nào (EDUCATIONAL_CONTENTS). NULL = game độc lập',
  `created_by` INT DEFAULT NULL COMMENT 'Staff/manager tạo game',
  `status` ENUM('active', 'locked') NOT NULL DEFAULT 'active' COMMENT 'locked = tạm ẩn khỏi danh sách',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - NULL = còn hoạt động',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_mini_games_type` (`game_type`),
  INDEX `idx_mini_games_content` (`content_id`),
  INDEX `idx_mini_games_creator` (`created_by`),
  INDEX `idx_mini_games_status` (`status`),
  INDEX `idx_mini_games_deleted` (`deleted_at`),
  CONSTRAINT `fk_mini_games_content` FOREIGN KEY (`content_id`) REFERENCES `EDUCATIONAL_CONTENTS` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mini_games_creator` FOREIGN KEY (`created_by`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Định nghĩa mini game tương tác, game_type + config_json để thêm loại game không cần đổi schema';


--TABLE GAME_SESSIONS (Phiên chơi = 1 phòng real-time)
CREATE TABLE `GAME_SESSIONS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `game_id` INT NOT NULL COMMENT 'Chơi game nào (MINI_GAMES)',
  `host_user_id` INT DEFAULT NULL COMMENT 'Giáo viên/staff chủ trì phòng. NULL nếu chơi tự do',
  `room_code` VARCHAR(20) NOT NULL COMMENT 'Mã phòng học sinh nhập để vào (VD: PIN 6 số kiểu Kahoot)',
  `status` ENUM('waiting', 'playing', 'finished', 'cancelled') NOT NULL DEFAULT 'waiting' COMMENT 'waiting=chờ người vào, playing=đang chơi, finished=kết thúc, cancelled=hủy',
  `points_reward` INT NOT NULL DEFAULT 0 COMMENT 'Điểm thưởng cấu hình cho phiên này (lấy default từ APP_SETTINGS, manager/host chỉnh được)',
  `started_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời điểm bắt đầu chơi',
  `ended_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời điểm kết thúc',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_game_sessions_room` (`room_code`),
  INDEX `idx_game_sessions_game` (`game_id`),
  INDEX `idx_game_sessions_host` (`host_user_id`),
  INDEX `idx_game_sessions_status` (`status`),
  CONSTRAINT `fk_game_sessions_game` FOREIGN KEY (`game_id`) REFERENCES `MINI_GAMES` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_game_sessions_host` FOREIGN KEY (`host_user_id`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '1 phiên chơi = 1 phòng, trạng thái real-time qua WebSocket, DB lưu phòng + kết quả';


--TABLE GAME_PARTICIPANTS (Người chơi trong 1 phiên)
CREATE TABLE `GAME_PARTICIPANTS` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `session_id` INT NOT NULL COMMENT 'Thuộc phiên chơi nào (GAME_SESSIONS)',
  `user_id` INT DEFAULT NULL COMMENT 'User đã đăng nhập. NULL nếu chơi bằng nickname (khách)',
  `nickname` VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị trong phòng (user hoặc khách)',
  `score` INT NOT NULL DEFAULT 0 COMMENT 'Điểm số trong game (khác điểm ví)',
  `rank` INT DEFAULT NULL COMMENT 'Thứ hạng chung cuộc trong phiên',
  `points_awarded` INT NOT NULL DEFAULT 0 COMMENT 'Điểm ví thực nhận sau phiên (nếu bật thưởng). Ghi POINT_EARNED tương ứng',
  `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_game_parts_session_score` (`session_id`, `score`),
  INDEX `idx_game_parts_session` (`session_id`),
  INDEX `idx_game_parts_user` (`user_id`),
  CONSTRAINT `fk_game_parts_session` FOREIGN KEY (`session_id`) REFERENCES `GAME_SESSIONS` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_game_parts_user` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '1 dòng/người chơi/phiên, bảng xếp hạng real-time sort theo score';
-- Seed permissions and default role mappings
INSERT INTO `PERMISSIONS` (`code`, `resource`, `action`, `name`, `description`, `is_system`) VALUES
('auth.login', 'auth', 'login', 'Đăng nhập', 'Đăng nhập hệ thống', 1),
('auth.logout', 'auth', 'logout', 'Đăng xuất', 'Đăng xuất phiên hiện tại', 1),
('auth.refresh', 'auth', 'refresh', 'Gia hạn phiên', 'Lấy access token mới bằng refresh token', 1),
('auth.change_password', 'auth', 'change_password', 'Đổi mật khẩu', 'Đổi mật khẩu tài khoản', 1),
('auth.request_password_reset', 'auth', 'request_password_reset', 'Yêu cầu reset mật khẩu', 'Gửi yêu cầu đặt lại mật khẩu', 1),
('auth.reset_password', 'auth', 'reset_password', 'Reset mật khẩu', 'Đặt lại mật khẩu bằng link (Laravel Password Broker)', 1),
('auth.verify_email', 'auth', 'verify_email', 'Xác minh email', 'Xác minh email qua link (Laravel MustVerifyEmail)', 1),
('auth.view_own_sessions', 'auth', 'view_own_sessions', 'Xem phiên đăng nhập', 'Xem danh sách thiết bị đang đăng nhập', 1),
('auth.revoke_own_session', 'auth', 'revoke_own_session', 'Thu hồi phiên của mình', 'Đăng xuất 1 thiết bị', 1),
('auth.revoke_all_sessions', 'auth', 'revoke_all_sessions', 'Thu hồi mọi phiên', 'Đăng xuất tất cả thiết bị của mình', 1),
('user.view_own', 'user', 'view_own', 'Xem hồ sơ của tôi', 'Xem thông tin tài khoản của chính mình', 1),
('user.edit_own', 'user', 'edit_own', 'Sửa hồ sơ của tôi', 'Sửa thông tin cá nhân của chính mình', 1),
('user.view', 'user', 'view', 'Xem user', 'Xem danh sách/tài khoản user', 1),
('user.create', 'user', 'create', 'Tạo user', 'Tạo tài khoản mới', 1),
('user.update', 'user', 'update', 'Cập nhật user', 'Sửa thông tin tài khoản', 1),
('user.delete', 'user', 'delete', 'Xóa user', 'Xóa mềm/xóa tài khoản', 1),
('user.lock', 'user', 'lock', 'Khóa user', 'Khóa tài khoản', 1),
('user.unlock', 'user', 'unlock', 'Mở khóa user', 'Mở khóa tài khoản', 1),
('user.assign_role', 'user', 'assign_role', 'Gán role', 'Gán vai trò cho user', 1),
('user.assign_facility', 'user', 'assign_facility', 'Gán cơ sở', 'Gán cơ sở cho staff/manager', 1),
('user.reset_password', 'user', 'reset_password', 'Reset mật khẩu user', 'Đặt lại mật khẩu cho user', 1),
('user.view_all_sessions', 'user', 'view_all_sessions', 'Xem mọi phiên', 'Xem session của user', 1),
('user.revoke_user_session', 'user', 'revoke_user_session', 'Thu hồi session', 'Thu hồi 1 session của user', 1),
('user.revoke_user_sessions_all', 'user', 'revoke_user_sessions_all', 'Thu hồi mọi session', 'Thu hồi toàn bộ session của user', 1),
('facility.view', 'facility', 'view', 'Xem cơ sở', 'Xem danh sách cơ sở', 1),
('facility.create', 'facility', 'create', 'Tạo cơ sở', 'Tạo cơ sở mới', 1),
('facility.update', 'facility', 'update', 'Cập nhật cơ sở', 'Sửa thông tin cơ sở', 1),
('facility.delete', 'facility', 'delete', 'Xóa cơ sở', 'Xóa mềm cơ sở', 1),
('facility.lock', 'facility', 'lock', 'Khóa cơ sở', 'Khóa hiển thị cơ sở', 1),
('facility.unlock', 'facility', 'unlock', 'Mở khóa cơ sở', 'Mở khóa hiển thị cơ sở', 1),
('measurement_unit.view', 'measurement_unit', 'view', 'Xem đơn vị đo', 'Xem danh sách đơn vị đo', 1),
('measurement_unit.create', 'measurement_unit', 'create', 'Tạo đơn vị đo', 'Thêm đơn vị đo mới', 1),
('measurement_unit.update', 'measurement_unit', 'update', 'Cập nhật đơn vị đo', 'Sửa đơn vị đo', 1),
('measurement_unit.delete', 'measurement_unit', 'delete', 'Xóa đơn vị đo', 'Xóa mềm đơn vị đo', 1),
('measurement_unit.lock', 'measurement_unit', 'lock', 'Khóa đơn vị đo', 'Ẩn đơn vị đo', 1),
('measurement_unit.unlock', 'measurement_unit', 'unlock', 'Mở khóa đơn vị đo', 'Hiện lại đơn vị đo', 1),
('waste_type.view', 'waste_type', 'view', 'Xem loại rác', 'Xem danh sách loại rác', 1),
('waste_type.create', 'waste_type', 'create', 'Tạo loại rác', 'Thêm loại rác mới', 1),
('waste_type.update', 'waste_type', 'update', 'Cập nhật loại rác', 'Sửa loại rác', 1),
('waste_type.delete', 'waste_type', 'delete', 'Xóa loại rác', 'Xóa mềm loại rác', 1),
('waste_type.lock', 'waste_type', 'lock', 'Khóa loại rác', 'Ẩn loại rác', 1),
('waste_type.unlock', 'waste_type', 'unlock', 'Mở khóa loại rác', 'Hiện lại loại rác', 1),
('waste_type.create_custom', 'waste_type', 'create_custom', 'Tự thêm loại rác', 'Cho user thêm loại rác riêng', 1),
('handover.view', 'handover', 'view', 'Xem đơn chuyển giao', 'Xem tất cả đơn', 1),
('handover.view_own', 'handover', 'view_own', 'Xem đơn của tôi', 'Xem đơn do chính mình tạo', 1),
('handover.create', 'handover', 'create', 'Tạo đơn chuyển giao', 'Tạo đơn mới', 1),
('handover.update', 'handover', 'update', 'Cập nhật đơn chuyển giao', 'Sửa đơn', 1),
('handover.cancel', 'handover', 'cancel', 'Hủy đơn chuyển giao', 'Hủy đơn', 1),
('handover.approve', 'handover', 'approve', 'Duyệt đơn chuyển giao', 'Duyệt đơn', 1),
('handover.reject', 'handover', 'reject', 'Từ chối đơn chuyển giao', 'Từ chối đơn', 1),
('handover.assign_staff', 'handover', 'assign_staff', 'Phân công staff', 'Gán staff xử lý', 1),
('handover.reschedule', 'handover', 'reschedule', 'Dời lịch đơn', 'Dời lịch hẹn', 1),
('handover.complete', 'handover', 'complete', 'Hoàn tất đơn', 'Đánh dấu hoàn tất đơn', 1),
('handover.view_logs', 'handover', 'view_logs', 'Xem log đơn', 'Xem lịch sử/thao tác đơn', 1),
('handover.record_weight', 'handover', 'record_weight', 'Ghi cân', 'Ghi nhận cân thực tế', 1),
('event.view', 'event', 'view', 'Xem sự kiện', 'Xem danh sách sự kiện', 1),
('event.create', 'event', 'create', 'Tạo sự kiện', 'Tạo sự kiện mới', 1),
('event.update', 'event', 'update', 'Cập nhật sự kiện', 'Sửa sự kiện', 1),
('event.delete', 'event', 'delete', 'Xóa sự kiện', 'Xóa mềm sự kiện', 1),
('event.lock', 'event', 'lock', 'Khóa sự kiện', 'Ẩn sự kiện', 1),
('event.unlock', 'event', 'unlock', 'Mở khóa sự kiện', 'Hiện lại sự kiện', 1),
('event.approve', 'event', 'approve', 'Duyệt sự kiện', 'Duyệt sự kiện', 1),
('event.publish', 'event', 'publish', 'Xuất bản sự kiện', 'Công bố sự kiện', 1),
('event.end', 'event', 'end', 'Kết thúc sự kiện', 'Đóng sự kiện', 1),
('event.assign_staff', 'event', 'assign_staff', 'Phân công staff sự kiện', 'Gán staff vào sự kiện', 1),
('event.manage_rewards', 'event', 'manage_rewards', 'Quản lý quà sự kiện', 'Quản lý quà minigame', 1),
('event.check_in_user', 'event', 'check_in_user', 'Check-in user', 'Điểm danh user tại sự kiện', 1),
('event.unlock_minigame', 'event', 'unlock_minigame', 'Mở khóa minigame', 'Mở minigame sau khi hoàn thành điều kiện', 1),
('event.play_minigame', 'event', 'play_minigame', 'Chơi minigame', 'Thực hiện lượt chơi', 1),
('event.grant_reward', 'event', 'grant_reward', 'Trao thưởng sự kiện', 'Trao quà vật lý', 1),
('event_registration.view', 'event_registration', 'view', 'Xem đăng ký sự kiện', 'Xem danh sách đăng ký', 1),
('event_registration.create', 'event_registration', 'create', 'Tạo đăng ký sự kiện', 'Tạo đăng ký mới', 1),
('event_registration.update', 'event_registration', 'update', 'Cập nhật đăng ký sự kiện', 'Sửa đăng ký', 1),
('event_registration.cancel', 'event_registration', 'cancel', 'Hủy đăng ký', 'Hủy đăng ký sự kiện', 1),
('event_registration.check_in', 'event_registration', 'check_in', 'Check-in đăng ký', 'Ghi nhận check-in', 1),
('event_registration.mark_absent', 'event_registration', 'mark_absent', 'Đánh dấu vắng mặt', 'Đánh dấu absent', 1),
('event_registration.view_own', 'event_registration', 'view_own', 'Xem đăng ký của tôi', 'Xem đăng ký sự kiện của chính mình', 1),
('wallet.view', 'wallet', 'view', 'Xem ví', 'Xem ví điểm của user', 1),
('wallet.view_own', 'wallet', 'view_own', 'Xem ví của tôi', 'Xem ví của chính mình', 1),
('points.view_own_history', 'points', 'view_own_history', 'Xem lịch sử điểm của tôi', 'Xem giao dịch điểm của chính mình', 1),
('points.adjust', 'points', 'adjust', 'Điều chỉnh điểm', 'Điều chỉnh cộng/trừ điểm', 1),
('redemption.view', 'redemption', 'view', 'Xem đổi quà', 'Xem tất cả đơn đổi quà', 1),
('redemption.view_own', 'redemption', 'view_own', 'Xem đổi quà của tôi', 'Xem đơn đổi quà của chính mình', 1),
('redemption.create', 'redemption', 'create', 'Tạo đổi quà', 'Đổi quà bằng điểm', 1),
('redemption.cancel', 'redemption', 'cancel', 'Hủy đổi quà', 'Hủy đổi quà', 1),
('redemption.fulfill', 'redemption', 'fulfill', 'Xác nhận giao quà', 'Xác nhận đã giao quà', 1),
('reward_catalog.view', 'reward_catalog', 'view', 'Xem danh mục quà', 'Xem danh mục quà đổi', 1),
('reward_catalog.create', 'reward_catalog', 'create', 'Tạo quà', 'Thêm quà vào danh mục', 1),
('reward_catalog.update', 'reward_catalog', 'update', 'Cập nhật quà', 'Sửa quà đổi', 1),
('reward_catalog.delete', 'reward_catalog', 'delete', 'Xóa quà', 'Xóa mềm quà', 1),
('reward_catalog.lock', 'reward_catalog', 'lock', 'Khóa quà', 'Ẩn quà khỏi danh mục', 1),
('reward_catalog.unlock', 'reward_catalog', 'unlock', 'Mở khóa quà', 'Hiện lại quà', 1),
('content.view', 'content', 'view', 'Xem bài', 'Xem bài giáo dục', 1),
('content.view_own', 'content', 'view_own', 'Xem bài của tôi', 'Xem bài do mình soạn', 1),
('content.create', 'content', 'create', 'Tạo bài', 'Soạn bài mới', 1),
('content.update', 'content', 'update', 'Cập nhật bài', 'Sửa bài', 1),
('content.delete', 'content', 'delete', 'Xóa bài', 'Xóa mềm bài', 1),
('content.submit', 'content', 'submit', 'Gửi duyệt bài', 'Gửi bài để manager duyệt', 1),
('content.approve', 'content', 'approve', 'Duyệt bài', 'Duyệt bài đăng', 1),
('content.reject', 'content', 'reject', 'Từ chối bài', 'Từ chối bài đăng', 1),
('content.publish', 'content', 'publish', 'Xuất bản bài', 'Đăng bài công khai', 1),
('content.unpublish', 'content', 'unpublish', 'Gỡ bài', 'Ẩn bài đã xuất bản', 1),
('content.read', 'content', 'read', 'Đọc bài', 'Thực hiện luồng đọc bài', 1),
('content.view_reads', 'content', 'view_reads', 'Xem lượt đọc', 'Xem lịch sử đọc bài', 1),
('sticker_set.view', 'sticker_set', 'view', 'Xem bộ sticker', 'Xem danh sách bộ sticker', 1),
('sticker_set.create', 'sticker_set', 'create', 'Tạo bộ sticker', 'Thêm bộ sticker mới', 1),
('sticker_set.update', 'sticker_set', 'update', 'Cập nhật bộ sticker', 'Sửa bộ sticker', 1),
('sticker_set.delete', 'sticker_set', 'delete', 'Xóa bộ sticker', 'Xóa mềm bộ sticker', 1),
('sticker_set.lock', 'sticker_set', 'lock', 'Khóa bộ sticker', 'Ẩn bộ sticker', 1),
('sticker_set.unlock', 'sticker_set', 'unlock', 'Mở khóa bộ sticker', 'Hiện lại bộ sticker', 1),
('sticker.view', 'sticker', 'view', 'Xem sticker', 'Xem danh sách sticker', 1),
('sticker.create', 'sticker', 'create', 'Tạo sticker', 'Thêm sticker mới', 1),
('sticker.update', 'sticker', 'update', 'Cập nhật sticker', 'Sửa sticker', 1),
('sticker.delete', 'sticker', 'delete', 'Xóa sticker', 'Xóa mềm sticker', 1),
('sticker.lock', 'sticker', 'lock', 'Khóa sticker', 'Ẩn sticker', 1),
('sticker.unlock', 'sticker', 'unlock', 'Mở khóa sticker', 'Hiện lại sticker', 1),
('sticker.obtain', 'sticker', 'obtain', 'Nhận sticker', 'Hệ thống trao sticker', 1),
('sticker.view_own', 'sticker', 'view_own', 'Xem sticker của tôi', 'Xem sticker của chính mình', 1),
('sticker.redeem', 'sticker', 'redeem', 'Đổi sticker', 'Đổi sticker vật lý', 1),
('sticker.view_redemptions', 'sticker', 'view_redemptions', 'Xem đổi sticker', 'Xem danh sách đổi sticker', 1),
('sticker.view_own_redemptions', 'sticker', 'view_own_redemptions', 'Xem đổi sticker của tôi', 'Xem lịch sử đổi sticker của chính mình', 1),
('title.view', 'title', 'view', 'Xem danh hiệu', 'Xem danh sách danh hiệu', 1),
('title.create', 'title', 'create', 'Tạo danh hiệu', 'Tạo danh hiệu mới', 1),
('title.update', 'title', 'update', 'Cập nhật danh hiệu', 'Sửa danh hiệu', 1),
('title.delete', 'title', 'delete', 'Xóa danh hiệu', 'Xóa mềm danh hiệu', 1),
('title.lock', 'title', 'lock', 'Khóa danh hiệu', 'Ẩn danh hiệu', 1),
('title.unlock', 'title', 'unlock', 'Mở khóa danh hiệu', 'Hiện lại danh hiệu', 1),
('title.view_own', 'title', 'view_own', 'Xem danh hiệu của tôi', 'Xem danh hiệu của chính mình', 1),
('title.assign', 'title', 'assign', 'Cấp danh hiệu', 'Cấp danh hiệu cho user', 1),
('title.recalculate', 'title', 'recalculate', 'Tính lại danh hiệu', 'Chạy cron/tính lại tiêu chí', 1),
('settings.view', 'settings', 'view', 'Xem cài đặt', 'Xem cấu hình hệ thống', 1),
('settings.update', 'settings', 'update', 'Cập nhật cài đặt', 'Sửa cấu hình hệ thống', 1),
('system_log.view', 'system_log', 'view', 'Xem log hệ thống', 'Xem audit log', 1),
('permission.view', 'permission', 'view', 'Xem quyền', 'Xem danh mục quyền', 1),
('permission.create', 'permission', 'create', 'Tạo quyền', 'Thêm quyền mới', 1),
('permission.update', 'permission', 'update', 'Cập nhật quyền', 'Sửa quyền', 1),
('permission.delete', 'permission', 'delete', 'Xóa quyền', 'Xóa quyền', 1),
('role_permission.view', 'role_permission', 'view', 'Xem mapping role', 'Xem quyền theo role', 1),
('role_permission.update', 'role_permission', 'update', 'Cập nhật mapping role', 'Sửa quyền theo role', 1),
('sticker_reward_item.view', 'sticker_reward_item', 'view', 'Xem vật phẩm quà sticker', 'Xem danh mục vật phẩm đổi sticker', 1),
('sticker_reward_item.create', 'sticker_reward_item', 'create', 'Tạo vật phẩm quà sticker', 'Thêm vật phẩm đổi sticker', 1),
('sticker_reward_item.update', 'sticker_reward_item', 'update', 'Cập nhật vật phẩm quà sticker', 'Sửa vật phẩm đổi sticker', 1),
('sticker_reward_item.delete', 'sticker_reward_item', 'delete', 'Xóa vật phẩm quà sticker', 'Xóa mềm vật phẩm đổi sticker', 1),
('sticker_reward_item.lock', 'sticker_reward_item', 'lock', 'Khóa vật phẩm quà sticker', 'Ẩn vật phẩm đổi sticker', 1),
('sticker_reward_item.unlock', 'sticker_reward_item', 'unlock', 'Mở khóa vật phẩm quà sticker', 'Hiện lại vật phẩm đổi sticker', 1),
('sticker_reward_item.adjust_stock', 'sticker_reward_item', 'adjust_stock', 'Chỉnh tồn kho vật phẩm', 'Điều chỉnh số lượng còn lại trong kho', 1),
('sticker_reward_rule.view', 'sticker_reward_rule', 'view', 'Xem rule bó quà', 'Xem cấu hình 1 sticker ra vật phẩm nào', 1),
('sticker_reward_rule.create', 'sticker_reward_rule', 'create', 'Tạo rule bó quà', 'Thêm cấu hình bó quà', 1),
('sticker_reward_rule.update', 'sticker_reward_rule', 'update', 'Cập nhật rule bó quà', 'Sửa cấu hình bó quà', 1),
('sticker_reward_rule.delete', 'sticker_reward_rule', 'delete', 'Xóa rule bó quà', 'Xóa cấu hình bó quà', 1),
('mini_game.view', 'mini_game', 'view', 'Xem mini game', 'Xem danh sách mini game', 1),
('mini_game.create', 'mini_game', 'create', 'Tạo mini game', 'Thêm mini game mới', 1),
('mini_game.update', 'mini_game', 'update', 'Cập nhật mini game', 'Sửa mini game', 1),
('mini_game.delete', 'mini_game', 'delete', 'Xóa mini game', 'Xóa mềm mini game', 1),
('mini_game.lock', 'mini_game', 'lock', 'Khóa mini game', 'Ẩn mini game', 1),
('mini_game.unlock', 'mini_game', 'unlock', 'Mở khóa mini game', 'Hiện lại mini game', 1),
('mini_game.host', 'mini_game', 'host', 'Chủ trì phiên game', 'Mở phòng và điều khiển phiên chơi', 1),
('mini_game.play', 'mini_game', 'play', 'Chơi mini game', 'Tham gia phiên chơi', 1),
('game_session.view', 'game_session', 'view', 'Xem phiên game', 'Xem danh sách/kết quả phiên chơi', 1),
('game_session.view_own', 'game_session', 'view_own', 'Xem phiên game của tôi', 'Xem phiên chơi của chính mình', 1),
('game_session.cancel', 'game_session', 'cancel', 'Hủy phiên game', 'Hủy phiên chơi', 1);

INSERT INTO `ROLE_PERMISSIONS` (`role`, `permission_id`, `created_at`)
SELECT 'user', `id`, CURRENT_TIMESTAMP FROM `PERMISSIONS`
WHERE `code` IN (
  'auth.login','auth.logout','auth.refresh','auth.change_password','auth.request_password_reset','auth.reset_password',
  'auth.verify_email','auth.view_own_sessions','auth.revoke_own_session','auth.revoke_all_sessions',
  'user.view_own','user.edit_own','facility.view','handover.view_own','handover.create','handover.update','handover.cancel',
  'handover.view_logs','event.view','event_registration.create','event_registration.view_own','wallet.view_own',
  'points.view_own_history','redemption.view_own','redemption.create','redemption.cancel','reward_catalog.view',
  'content.view','content.read','sticker.view_own','sticker.obtain','sticker.redeem','sticker.view_own_redemptions',
  'title.view_own','waste_type.view','waste_type.create_custom',
  'mini_game.view','mini_game.play','game_session.view_own'
);

INSERT INTO `ROLE_PERMISSIONS` (`role`, `permission_id`, `created_at`)
SELECT 'staff', `id`, CURRENT_TIMESTAMP FROM `PERMISSIONS`
WHERE `code` IN (
  'auth.login','auth.logout','auth.refresh','auth.change_password','auth.request_password_reset','auth.reset_password',
  'auth.verify_email','auth.view_own_sessions','auth.revoke_own_session','auth.revoke_all_sessions',
  'user.view_own','user.edit_own','user.view','user.update','facility.view','measurement_unit.view','waste_type.view',
  'handover.view','handover.view_own','handover.approve','handover.reject','handover.assign_staff','handover.reschedule',
  'handover.complete','handover.record_weight','handover.view_logs','event.view','event_registration.view',
  'event_registration.check_in','event_registration.mark_absent','event.check_in_user','event.unlock_minigame',
  'event.play_minigame','event.grant_reward','content.view','content.view_own','content.create','content.update',
  'content.submit','content.view_reads','sticker_set.view','sticker.view','sticker.obtain','sticker.redeem',
  'sticker.view_redemptions','sticker.view_own_redemptions','reward_catalog.view','redemption.view','redemption.fulfill',
  'wallet.view','wallet.view_own','points.view_own_history','title.view','title.view_own',
  'sticker_reward_item.view','sticker_reward_item.adjust_stock','sticker_reward_rule.view',
  'mini_game.view','mini_game.create','mini_game.update','mini_game.host','mini_game.play',
  'game_session.view','game_session.view_own','game_session.cancel'
);

INSERT INTO `ROLE_PERMISSIONS` (`role`, `permission_id`, `created_at`)
SELECT 'manager', `id`, CURRENT_TIMESTAMP FROM `PERMISSIONS`
WHERE `code` IN (
  'auth.login','auth.logout','auth.refresh','auth.change_password','auth.request_password_reset','auth.reset_password',
  'auth.verify_email','auth.view_own_sessions','auth.revoke_own_session','auth.revoke_all_sessions',
  'user.view','user.create','user.update','user.delete','user.lock','user.unlock','user.assign_role','user.assign_facility',
  'user.reset_password','user.view_all_sessions','user.revoke_user_session','user.revoke_user_sessions_all','facility.view',
  'facility.create','facility.update','facility.delete','facility.lock','facility.unlock','measurement_unit.view',
  'measurement_unit.create','measurement_unit.update','measurement_unit.delete','measurement_unit.lock','measurement_unit.unlock',
  'waste_type.view','waste_type.create','waste_type.update','waste_type.delete','waste_type.lock','waste_type.unlock',
  'waste_type.create_custom','handover.view','handover.view_own','handover.create','handover.update','handover.cancel',
  'handover.approve','handover.reject','handover.assign_staff','handover.reschedule','handover.complete','handover.record_weight',
  'handover.view_logs','event.view','event.create','event.update','event.delete','event.lock','event.unlock','event.approve',
  'event.publish','event.end','event.assign_staff','event.manage_rewards','event.check_in_user','event.unlock_minigame',
  'event.play_minigame','event.grant_reward','event_registration.view','event_registration.create','event_registration.update',
  'event_registration.cancel','event_registration.check_in','event_registration.mark_absent','wallet.view','wallet.view_own',
  'points.view_own_history','points.adjust','redemption.view','redemption.view_own','redemption.create',
  'redemption.cancel','redemption.fulfill','reward_catalog.view','reward_catalog.create','reward_catalog.update',
  'reward_catalog.delete','reward_catalog.lock','reward_catalog.unlock','content.view','content.view_own','content.create',
  'content.update','content.delete','content.submit','content.approve','content.reject','content.publish','content.unpublish',
  'content.read','content.view_reads','sticker_set.view','sticker_set.create','sticker_set.update','sticker_set.delete',
  'sticker_set.lock','sticker_set.unlock','sticker.view','sticker.create','sticker.update','sticker.delete','sticker.lock',
  'sticker.unlock','sticker.obtain','sticker.view_own','sticker.redeem','sticker.view_redemptions','sticker.view_own_redemptions',
  'title.view','title.create','title.update','title.delete','title.lock','title.unlock','title.view_own','title.assign',
  'title.recalculate','settings.view','settings.update','system_log.view','permission.view','permission.create',
  'permission.update','permission.delete','role_permission.view','role_permission.update',
  'sticker_reward_item.view','sticker_reward_item.create','sticker_reward_item.update','sticker_reward_item.delete',
  'sticker_reward_item.lock','sticker_reward_item.unlock','sticker_reward_item.adjust_stock',
  'sticker_reward_rule.view','sticker_reward_rule.create','sticker_reward_rule.update','sticker_reward_rule.delete',
  'mini_game.view','mini_game.create','mini_game.update','mini_game.delete','mini_game.lock','mini_game.unlock',
  'mini_game.host','mini_game.play','game_session.view','game_session.view_own','game_session.cancel'
);

-- Seed default app settings (manager chỉnh qua UI)
INSERT INTO `APP_SETTINGS` (`setting_key`, `setting_value`, `description`) VALUES
('storage_base_path', 'storage/public', 'Thư mục gốc lưu file upload trên server'),
('upload_max_size', '5242880', 'Dung lượng tối đa mỗi file upload (byte). Mặc định 5MB'),
('game_reward_enabled', 'false', 'Bật/tắt thưởng điểm ví khi chơi mini game. Backend parse boolean'),
('game_reward_default_points', '0', 'Điểm ví mặc định thưởng cho mỗi phiên game khi bật (GAME_SESSIONS.points_reward lấy default từ đây)'),
('game_reward_top_only', 'false', 'true = chỉ thưởng top hạng, false = mọi người chơi đều nhận'),
('sticker_delivery_enabled', 'true', 'Bật/tắt cho user chọn ship tận nhà khi đổi sticker/quà'),
('sticker_reward_low_stock_threshold', '10', 'Ngưỡng cảnh báo tồn kho vật phẩm quà sticker thấp');

SET FOREIGN_KEY_CHECKS = 1;