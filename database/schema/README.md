# Database Schema

Folder này chứa DDL SQL của database, dùng làm tài liệu tham chiếu và source-of-truth cho việc deploy DB.

## Files

| File | Vai trò |
|---|---|
| [schema.sql](schema.sql) | DDL MariaDB export từ DBML, dùng để deploy/reference production |

File thiết kế nguồn `schema.dbml` ([dbdiagram.io](https://dbdiagram.io/d/LOOP-STATION-DATABASE-SCHEMA-6a3a315f5c789b8acbdfd6fa)) là tài liệu, nằm trong [`docs/database/schema/`](../../docs/database/schema/schema.dbml).

## Lưu ý

- **Không sửa trực tiếp SQL** — sửa trên dbdiagram.io rồi export lại để đồng bộ.
- Migration thực tế nằm trong `database/migrations/` (chuẩn Laravel).
- Tổng quan schema + luồng nghiệp vụ: [`docs/database/schema/`](../../docs/database/schema/).
- Tài liệu chi tiết từng bảng: [`docs/database/tables/`](../../docs/database/tables/).
