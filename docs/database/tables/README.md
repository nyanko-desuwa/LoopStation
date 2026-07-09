# Tài liệu Database - Mô tả từng bảng

Thư mục này tổng hợp tài liệu chi tiết cho từng bảng trong schema `loop_station` (MariaDB). Mỗi bảng có 1 file riêng: mục đích bảng, mô tả từng cột, quan hệ khóa ngoại và index.

Nguồn schema: [schema.dbml](../schema/schema.dbml) • DDL deploy: [schema.sql](../../../database/schema/schema.sql) • Tổng quan: [schema/README.md](../schema/README.md)

## Danh sách bảng

### Người dùng & phân quyền
| Bảng | Vai trò |
| --- | --- |
| [USERS](users.md) | Tài khoản 3 role (user/staff/manager) + tài khoản vãng lai |
| [PERMISSIONS](permissions.md) | Danh mục quyền RBAC |
| [ROLE_PERMISSIONS](role_permissions.md) | Mapping role → quyền |
| [USER_SESSIONS](user_sessions.md) | Phiên đăng nhập API/JWT trên từng thiết bị |
| [SESSIONS](sessions.md) | Session web chuẩn Laravel (session driver = database) |
| [LOGIN_LOGS](login_logs.md) | Audit trail mọi lần đăng nhập |
| [PASSWORD_RESET_TOKENS](password_reset_tokens.md) | Token reset mật khẩu bằng link (Laravel Password Broker) |

### Cơ sở & thu hồi rác
| Bảng | Vai trò |
| --- | --- |
| [FACILITIES](facilities.md) | Cơ sở/trạm thu hồi của công ty |
| [MEASUREMENT_UNITS](measurement_units.md) | Danh mục đơn vị đo |
| [WASTE_TYPES](waste_types.md) | Danh mục loại rác |
| [HANDOVER_REQUESTS](handover_requests.md) | Đơn chuyển giao (bảng trung tâm) |
| [HANDOVER_WASTE_ITEMS](handover_waste_items.md) | Dòng rác trong đơn (N-N) |
| [HANDOVER_WEIGHT_LOGS](handover_weight_logs.md) | Lịch sử cân thực tế |

### Sự kiện
| Bảng | Vai trò |
| --- | --- |
| [EVENTS](events.md) | Sự kiện Ngày hội sống xanh |
| [EVENT_STAFF_ASSIGNMENTS](event_staff_assignments.md) | Phân công staff cho sự kiện |
| [EVENT_REWARDS](event_rewards.md) | Quà minigame trong sự kiện |
| [EVENT_REGISTRATIONS](event_registrations.md) | Đăng ký tham gia sự kiện |

### Ví điểm & giao dịch
| Bảng | Vai trò |
| --- | --- |
| [USER_WALLETS](user_wallets.md) | Ví điểm xanh (1-1 với user) |
| [POINT_EARNED](point_earned.md) | Lịch sử cộng điểm |
| [POINT_SPENT](point_spent.md) | Lịch sử trừ điểm |

### Giáo dục môi trường
| Bảng | Vai trò |
| --- | --- |
| [EDUCATIONAL_CONTENTS](educational_contents.md) | Bài học môi trường |
| [CONTENT_READS](content_reads.md) | Lượt đọc bài + quota |

### Sticker sưu tập
| Bảng | Vai trò |
| --- | --- |
| [STICKER_SETS](sticker_sets.md) | Bộ sưu tập sticker |
| [STICKERS](stickers.md) | Từng sticker trong bộ |
| [USER_STICKERS](user_stickers.md) | Sticker user đang giữ |
| [STICKER_OBTAIN_LOGS](sticker_obtain_logs.md) | Lịch sử nhận sticker |
| [STICKER_REWARD_ITEMS](sticker_reward_items.md) | Danh mục vật phẩm quà sticker |
| [STICKER_REWARD_RULES](sticker_reward_rules.md) | Rule đổi sticker ra vật phẩm |
| [STICKER_REDEMPTIONS](sticker_redemptions.md) | Đổi sticker vật lý tại cơ sở hoặc ship |
| [STICKER_REDEMPTION_ITEMS](sticker_redemption_items.md) | Snapshot vật phẩm đổi sticker |

### Mini game real-time
| Bảng | Vai trò |
| --- | --- |
| [MINI_GAMES](mini_games.md) | Định nghĩa mini game |
| [GAME_SESSIONS](game_sessions.md) | Phiên/phòng chơi mini game |
| [GAME_PARTICIPANTS](game_participants.md) | Người chơi trong phiên |

### Danh hiệu theo kỳ
| Bảng | Vai trò |
| --- | --- |
| [TITLE_DEFINITIONS](title_definitions.md) | Định nghĩa danh hiệu |
| [USER_TITLES](user_titles.md) | Danh hiệu user đã đạt |

### Đổi quà bằng điểm
| Bảng | Vai trò |
| --- | --- |
| [REWARD_CATALOG](reward_catalog.md) | Danh mục quà đổi bằng điểm |
| [REDEMPTIONS](redemptions.md) | Lịch sử đổi quà |

### Cấu hình & audit
| Bảng | Vai trò |
| --- | --- |
| [APP_SETTINGS](app_settings.md) | Cấu hình key-value toàn hệ thống |
| [SYSTEM_LOGS](system_logs.md) | Audit log mọi thao tác |

## Quy ước

- **PK**: khóa chính • **FK**: khóa ngoại • **UQ**: unique
- Cột `created_at` mặc định `DEFAULT CURRENT_TIMESTAMP`; `updated_at` thêm `ON UPDATE CURRENT_TIMESTAMP`
- `deleted_at` = soft delete (NULL = còn hoạt động)
- Cộng điểm ghi ở `POINT_EARNED`, trừ điểm ghi ở `POINT_SPENT`, `points` luôn dương
