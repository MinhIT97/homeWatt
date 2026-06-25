# Security Practices

Tài liệu này mô tả các biện pháp bảo mật được áp dụng trong HomeWatt và hướng dẫn
triển khai an toàn cho production.

## Nguyên tắc cốt lõi (Defense-in-Depth)

HomeWatt tuân thủ 4 nguyên tắc bảo mật cốt lõi (xem `README.md`):

1. **Photos are private by default** - Ảnh được serve qua signed URL với expiration.
2. **AI proposes, user confirms** - Output AI không ghi đè dữ liệu đã xác nhận.
3. **Measured ≠ Estimated** - Dữ liệu đo và ước tính được phân biệt rõ ràng.
4. **Tariffs are versioned data** - Không hardcode giá điện.
5. **Home-level isolation** - Mọi resource đều scope theo `home_id` và check membership.

## Layers bảo vệ

### 1. Authentication
- Laravel Breeze với email/password authentication
- Email verification bắt buộc cho sensitive actions
- Password hash với bcrypt (cost factor configurable via `BCRYPT_ROUNDS`)

### 2. Authorization (Defense-in-Depth)
- **Middleware**: `auth`, `verified`, `admin` (cho admin routes)
- **FormRequest `authorize()`**: Kiểm tra policy ngay tại validation layer
- **Policy classes**: `HomePolicy`, `MediaPolicy`, `EnergyReadingPolicy`, `TariffPlanPolicy`
- **Controller-level checks**: `$this->authorize(...)` cho sensitive operations
- **Model helpers**: `Home::isMember($userId)`, `Home::member($userId)`, `Home::hasMemberWithRole($userId, $roles)`

### 3. IDOR Prevention
Mọi controller đều verify ownership:
- `HomeController::show/update/destroy`: Check member qua `HomePolicy`
- `MediaController::store`: Check ownership của target resource
- `EnergyController::show/calculate`: Verify home membership
- `SmartPlugController`: API key + device ownership (idempotency key)
- `AiAnalysisController::store/show`: Verify media ownership + user_id check

### 4. Mass Assignment Protection
Sensitive fields KHÔNG có trong `$fillable`:
- `Home::$fillable` không có `status` → dùng method `updateStatus()`
- `HomeMember::$fillable` không có `role` → dùng method `assignRole()`
- `Device::$fillable` không có `status` → dùng method `updateStatus()`

### 5. Race Condition Prevention
Multi-step write operations đều wrap trong `DB::transaction`:
- `HomeController::invite()`: lockForUpdate trên home + check existing member
- `HomeController::removeMember()`: lockForUpdate + role check
- `EnergyController::store()`: lockForUpdate + member check
- `EnergyController::calculate()`: lockForUpdate + updateOrCreate với unique constraint
- `MediaController::store()`: DB transaction
- `AiAnalysisController::store()`: firstOrCreate với pending status

### 6. Rate Limiting
- **General API**: `throttle:60,1` (60 requests/minute)
- **Smartplug API**: `throttle:60,1` (60 readings/minute)
- **AI Analysis**: `throttle.ai` middleware (per-user + per-home limits từ `config/ai.php`)

### 7. Secret Sanitization
- AI Job logs: `sanitizeErrorMessage()` strips API keys, tokens, credentials
- Error responses: Generic messages, không leak stack traces ở production
- `APP_DEBUG=false` trong production

### 8. Signed URLs
- Media serving yêu cầu valid signature: `URL::temporarySignedRoute()`
- Expiration: 30 phút mặc định
- Cache-Control: `private, max-age=300`

## Production Deployment Checklist

### Environment
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `LOG_LEVEL=info` (hoặc `warning`)
- [ ] `APP_KEY` đã generate với `php artisan key:generate`

### Database
- [ ] Tạo MySQL user riêng cho app (xem `database/sql/create-app-user.sql`)
- [ ] KHÔNG dùng root user cho application
- [ ] Connection over SSL nếu DB tách riêng

### Session Security
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS only)
- [ ] `SESSION_HTTP_ONLY=true`
- [ ] `SESSION_SAME_SITE=lax` hoặc `strict`

### Trusted Proxies
- [ ] Set `TRUSTED_PROXIES` env variable cho load balancer/CDN
- [ ] KHÔNG set `*` trong production

### CORS
- [ ] Configure `config/cors.php` với domains cụ thể
- [ ] KHÔNG dùng wildcard `*`

### Secrets Rotation
- [ ] `OPENAI_API_KEY` - rotate mỗi 90 ngày
- [ ] `SMARTPLUG_API_KEY` - rotate mỗi 90 ngày
- [ ] `APP_KEY` - rotate khi compromise; nếu đã commit vào git, dùng `git filter-repo`

### File Permissions
- [ ] `.env` permissions: 600 (chỉ owner đọc)
- [ ] Storage directories: 755
- [ ] KHÔNG expose `storage/` ra public

## Audit Logging

Sensitive operations đều được log qua `App\Support\AuditLogger`:

```php
AuditLogger::log('home.deleted', ['home_id' => $homeId]);
AuditLogger::log('home.member_invited', ['home_id' => $homeId, 'user_id' => $userId]);
AuditLogger::log('home.member_removed', ['home_id' => $homeId, 'user_id' => $userId]);
AuditLogger::log('media.uploaded', ['media_id' => $mediaId]);
AuditLogger::log('media.deleted', ['media_id' => $mediaId]);
AuditLogger::log('tariff.created', ['tariff_plan_id' => $planId]);
AuditLogger::log('tariff.deleted', ['tariff_plan_id' => $planId]);
```

Logs bao gồm: IP, user agent, user_id, timestamp, custom context.

## Reporting Security Issues

Nếu phát hiện lỗ hổng bảo mật, vui lòng:
1. **KHÔNG** tạo public issue
2. Email trực tiếp cho maintainer
3. Cung cấp: mô tả, reproduction steps, impact assessment