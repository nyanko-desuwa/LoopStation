# Loop Station - Database Documentation

Tài liệu database của dự án Loop Station. Được tách thành 2 phần cho dễ tra cứu.

> Ghi chú auth/email: bảng `USERS` có thêm `email_canonical` để chuẩn hóa mailbox và chặn trùng tài khoản, còn `email` vẫn là email gốc để hiển thị và gửi mail. Bảng `USERS` cũng lưu `locale` mặc định `vi` để phục vụ dropdown `vi` / `en` và i18n theo user.

## Cấu trúc

| Thư mục | Nội dung |
|---|---|
| [schema/](schema/) | Tổng quan schema, kiến trúc, luồng nghiệp vụ, quyết định thiết kế |
| [tables/](tables/) | Tài liệu chi tiết cho từng bảng (39 bảng) |

## Schema nguồn

- [schema.dbml](schema/schema.dbml) — file thiết kế trên dbdiagram.io (thuộc tài liệu, không phải file DB)
- [schema.sql](../../database/schema/schema.sql) — DDL MariaDB để deploy, nằm trong [`database/schema/`](../../database/schema/)

## Liên quan

- Tài liệu nghiệp vụ / use case: [`docs/business/`](../business/)
- Migration Laravel thực tế: [`database/migrations/`](../../database/migrations/)