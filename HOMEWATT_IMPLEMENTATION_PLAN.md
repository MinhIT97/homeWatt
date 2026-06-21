# Kế hoạch triển khai HomeWatt

## 1. Mục tiêu sản phẩm

HomeWatt giúp một hộ gia đình:

1. Quản lý nhà, thành viên, phòng và thiết bị điện.
2. Chụp hoặc tải ảnh thiết bị và tem thông số.
3. Dùng AI Vision để đề xuất loại thiết bị, hãng, model, công suất và thông tin trên nhãn.
4. Cho người dùng kiểm tra, sửa và xác nhận dữ liệu AI.
5. Ghi nhận công suất, thời gian sử dụng hoặc chỉ số từ thiết bị đo điện.
6. Ước lượng điện năng và chi phí theo ngày/tháng.
7. So sánh mức tiêu thụ theo thiết bị, loại thiết bị và phòng.
8. Cảnh báo dữ liệu bất thường hoặc thiết bị tiêu thụ cao.

MVP không tuyên bố kết quả là số liệu đo chính xác tuyệt đối. Mỗi kết quả phải ghi rõ nguồn và độ tin cậy: `AI đọc từ ảnh`, `người dùng nhập`, `ước lượng`, hoặc `đo thực tế`.

## 2. Nền tảng kỹ thuật

HomeWatt sẽ dùng cấu trúc đã chứng minh hiệu quả ở WorkLens, nhưng chỉ lấy các phần phù hợp:

- PHP 8.4 và Laravel 12.
- Modular monolith với `nwidart/laravel-modules`.
- Blade, Alpine.js, Tailwind CSS và Vite.
- MySQL 8.
- Redis 7 cho cache, rate limit và queue.
- Queue riêng `default` và `ai`.
- Docker multi-stage build.
- Nginx là web entrypoint.
- PHPUnit, Laravel Pint, Composer Audit và NPM Audit.
- CI/CD trên GitHub Actions và self-hosted Linux runner.

### Các module dự kiến

| Module | Trách nhiệm |
|---|---|
| Core | Layout, thành phần UI dùng chung, health/version endpoint |
| Home | Nhà, thành viên, quyền truy cập |
| Room | Phòng và phân nhóm không gian |
| Device | Loại thiết bị, thiết bị, thông số kỹ thuật |
| Media | Ảnh private, metadata, lifecycle và quyền xem |
| AI | Provider AI Vision, job phân tích ảnh, usage/cost |
| Energy | Hồ sơ sử dụng, lần đo, công thức ước lượng |
| Tariff | Biểu giá điện có phiên bản và thời gian hiệu lực |
| Dashboard | Tổng hợp, biểu đồ, xếp hạng tiêu thụ |
| Admin | Danh mục thiết bị, biểu giá, AI usage và vận hành |

Không tạo repository cho CRUD đơn giản. Business workflow dùng Action/Service; tác vụ AI chạy bằng Job; quyền dữ liệu dùng Policy.

## 3. Cấu hình Docker và port

### Port đã chốt

| Service | Host port | Container port | Ghi chú |
|---|---:|---:|---|
| Web (nginx/app) | `8087` | `80` | Truy cập `http://localhost:8087` |
| MySQL | `3311` | `3306` | Laravel trong Docker kết nối `db:3306` |
| Redis | `6384` | `6379` | Laravel trong Docker kết nối `redis:6379` |
| PHP-FPM | Không public | `9000` | Chỉ Nginx truy cập trong network |

### Biến môi trường

```env
APP_NAME=HomeWatt
APP_URL=http://localhost:8087
APP_HTTP_PORT=8087
APP_RELEASE=local

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=homewatt
DB_USERNAME=root
DB_PASSWORD=
DB_FORWARD_PORT=3311

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_FORWARD_PORT=6384

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

AI_PROVIDER=openai
OPENAI_API_KEY=
OPENAI_VISION_MODEL=
```

Mật khẩu thật và API key chỉ nằm trong `.env` của máy chạy, không commit vào Git.

### Service trong Docker Compose

| Service | Vai trò |
|---|---|
| `app` | PHP-FPM xử lý request |
| `nginx` | Public web tại port `8087` |
| `queue` | Email, cleanup và tác vụ nền thông thường |
| `queue-ai` | Phân tích ảnh; timeout và retry riêng |
| `scheduler` | Tổng hợp tháng, cleanup và cảnh báo |
| `db` | MySQL 8, volume riêng |
| `redis` | Cache/queue/rate-limit, volume riêng |

Tất cả container dùng prefix `homewatt-`, network `homewatt-network` và named volume riêng để không xung đột với WorkLens hoặc project khác trên cùng self-host.

## 4. Mô hình dữ liệu cốt lõi

### Nhà và phòng

- `homes`: chủ sở hữu, tên, địa chỉ/múi giờ, loại tiền, trạng thái.
- `home_members`: user, home, role (`owner`, `manager`, `member`, `viewer`).
- `rooms`: home, tên, loại phòng, tầng, thứ tự hiển thị.

Mọi truy vấn nghiệp vụ phải giới hạn theo `home_id` và quyền thành viên.

### Thiết bị

- `device_types`: điều hòa, TV, tủ lạnh, máy giặt, quạt, bình nóng lạnh...
- `devices`: room, type, tên, hãng, model, serial, trạng thái, ngày mua.
- `device_specifications`: điện áp, dòng điện, công suất định mức/tối đa/chờ, dung lượng và metadata mở rộng.
- `device_usage_profiles`: giờ/ngày, ngày/tuần, duty cycle, mùa sử dụng.

Không dùng một trường `power_watts` duy nhất cho mọi trường hợp. Cần phân biệt:

- Công suất định mức.
- Công suất tối đa.
- Công suất chờ.
- Công suất đo thực tế.
- Công suất trung bình đã hiệu chỉnh.

### Ảnh và AI

- `media`: owner type/id, storage disk/path, MIME, size, checksum, trạng thái.
- `ai_analysis_requests`: ảnh đầu vào, provider/model, status, attempts, error.
- `ai_analysis_results`: raw JSON, dữ liệu chuẩn hóa, confidence, token/cost.
- `device_extractions`: các trường AI đề xuất và giá trị người dùng xác nhận.

Ảnh thiết bị/tem mặc định là private. File được xem qua endpoint đã authorize hoặc signed URL, không public path trực tiếp.

### Năng lượng và chi phí

- `energy_readings`: thiết bị, thời điểm, watts/kWh, nguồn đo, khoảng đo.
- `energy_estimates`: kỳ tính, phương pháp, kWh, confidence, input snapshot.
- `tariff_plans`: nhà cung cấp, khu vực, loại giá, hiệu lực từ/đến.
- `tariff_tiers`: bậc, giới hạn kWh, đơn giá, thuế/phụ phí.
- `monthly_energy_summaries`: tổng kWh và chi phí theo nhà/phòng/thiết bị.

Biểu giá không viết cứng trong code. Admin có thể thêm phiên bản mới, và mỗi dự toán lưu snapshot biểu giá đã dùng.

## 5. Luồng nghiệp vụ chính

### 5.1 Thêm thiết bị bằng ảnh

1. Người dùng chọn nhà và phòng.
2. Chụp ảnh toàn cảnh thiết bị.
3. Chụp gần tem thông số hoặc nhãn năng lượng.
4. Backend kiểm tra quyền, MIME, kích thước và lưu ảnh private.
5. Tạo `ai_analysis_request` ở trạng thái `pending`.
6. Dispatch `AnalyzeDeviceImageJob` vào queue `ai`.
7. AI trả JSON theo schema cố định.
8. Backend kiểm tra schema, đơn vị và giới hạn hợp lý.
9. UI hiển thị dữ liệu đề xuất, confidence và ảnh nguồn.
10. Người dùng sửa/xác nhận trước khi tạo hoặc cập nhật thiết bị.

AI không tự động ghi đè dữ liệu đã được người dùng xác nhận.

### 5.2 Ghi nhận công suất

MVP hỗ trợ ba cách:

1. Đọc công suất trên tem bằng AI.
2. Người dùng nhập công suất và thời gian sử dụng.
3. Người dùng nhập/chụp kết quả từ ổ cắm hoặc công tơ đo điện.

Một ảnh “công suất” phải được xác định là ảnh tem định mức hay ảnh màn hình đo thực tế; hai loại này không được coi như nhau.

### 5.3 Ước lượng điện năng

Thiết bị chạy tương đối ổn định:

```text
kWh/tháng = watts × giờ/ngày × ngày/tháng ÷ 1000
```

Thiết bị chạy theo chu kỳ:

```text
kWh/tháng = watts × giờ/ngày × ngày/tháng × duty_cycle ÷ 1000
```

Ví dụ tủ lạnh, điều hòa và máy nước nóng cần duty cycle hoặc hệ số theo loại thiết bị. Nếu có số đo thực tế, hệ thống ưu tiên số đo thay cho công suất định mức.

Mỗi kết quả hiển thị:

- Giá trị kWh và chi phí.
- Khoảng ước lượng thấp/cao khi dữ liệu chưa chắc chắn.
- Phương pháp tính.
- Dữ liệu đầu vào.
- Độ tin cậy.
- Ngày tính và biểu giá áp dụng.

### 5.4 Dashboard

- Tổng kWh và chi phí dự kiến tháng.
- Top thiết bị tiêu thụ nhiều.
- So sánh theo phòng và loại thiết bị.
- Tỷ lệ dữ liệu đo thực tế so với dữ liệu ước lượng.
- Thiết bị thiếu công suất hoặc lịch sử dụng.
- So sánh tháng hiện tại với tháng trước khi đủ dữ liệu.

## 6. Thiết kế AI an toàn và kiểm soát chi phí

- Tạo contract `DeviceImageAnalyzer` để có thể đổi OpenAI/Gemini.
- Chỉ gửi ảnh được người dùng chủ động chọn.
- Không gửi địa chỉ nhà, tên thành viên hoặc metadata không cần thiết.
- Bắt AI trả JSON schema có version.
- Chuẩn hóa `W`, `kW`, `V`, `A`, `kWh/year`.
- Không suy diễn model/công suất khi ảnh không đủ rõ; trả `unknown`.
- Lưu confidence theo từng trường, không chỉ một confidence chung.
- Rate limit theo user/home.
- Giới hạn kích thước và số ảnh/lần phân tích.
- Ghi token, chi phí, provider, model và thời gian xử lý.
- Retry có backoff; lỗi vĩnh viễn phải hiển thị nút thử lại.
- Dùng checksum để tránh phân tích lại cùng một ảnh không cần thiết.
- Test provider bằng HTTP fake, không gọi AI thật trong test suite.

## 7. Bảo mật và quyền riêng tư

- Policy cho Home, Room, Device, Media và EnergyReading.
- Mọi dữ liệu đều thuộc một `home_id`.
- Upload phải kiểm tra MIME thực, dung lượng, extension và ảnh hợp lệ.
- Tên file do server tạo; không tin tên file từ client.
- Xóa thiết bị phải có chiến lược xử lý ảnh và lịch sử đo.
- Audit các hành động: xác nhận AI, sửa thông số, xóa ảnh, đổi biểu giá.
- Rate limit login, upload và AI.
- Không log API key, ảnh nhạy cảm hoặc raw prompt chứa dữ liệu cá nhân.
- Backup MySQL và volume ảnh; có quy trình kiểm tra restore.

## 8. Lộ trình triển khai

### Giai đoạn 0 — Khởi tạo nền tảng

- Khởi tạo Laravel 12, auth và modular structure.
- Tạo `agent.md`, README và quy ước module.
- Tạo Dockerfile multi-stage, Compose và Nginx.
- Cấu hình đúng port `8087`, `3311`, `6384`.
- Tạo `app`, `nginx`, `queue`, `queue-ai`, `scheduler`, `db`, `redis`.
- Thêm healthcheck, `/up`, `/version` và `APP_RELEASE`.
- Tạo CI: install, lint, test, audit, build và validate Compose.

**Hoàn thành khi:** app chạy qua `http://localhost:8087`, DB/Redis healthy, queue và scheduler healthy, CI xanh.

### Giai đoạn 1 — Nhà, thành viên, phòng

- Tạo module Home và Room.
- CRUD nhà/phòng.
- Mời thành viên và phân quyền cơ bản.
- Dashboard rỗng có onboarding.
- Policy và test chống truy cập chéo nhà.

**Hoàn thành khi:** người dùng chỉ xem/sửa được dữ liệu nhà mình được cấp quyền.

### Giai đoạn 2 — Danh mục và thiết bị

- Seed loại thiết bị phổ biến.
- CRUD thiết bị và thông số.
- Usage profile theo loại thiết bị.
- Tìm kiếm, lọc theo phòng/type/trạng thái.
- Trang chi tiết thiết bị có lịch sử thay đổi.

**Hoàn thành khi:** có thể tạo thiết bị thủ công và đủ dữ liệu để tính kWh cơ bản.

### Giai đoạn 3 — Upload ảnh và AI Vision

- Kích hoạt module Media với private storage.
- Camera/upload UX ưu tiên mobile.
- Queue AI, provider contract và JSON schema.
- Màn hình theo dõi trạng thái phân tích.
- Màn hình review/confirm từng trường.
- Retry, rate limit, usage/cost log và test fake provider.

**Hoàn thành khi:** ảnh tem rõ có thể tạo đề xuất thiết bị, nhưng chỉ dữ liệu đã xác nhận mới trở thành dữ liệu chính thức.

### Giai đoạn 4 — Tính điện năng và biểu giá

- Energy readings và usage profile.
- Công thức continuous/duty-cycle/measured.
- Tariff plan có phiên bản và bậc giá.
- Tính kWh, khoảng ước lượng và chi phí.
- Lưu snapshot đầu vào để có thể giải thích kết quả.

**Hoàn thành khi:** mỗi thiết bị hiển thị được dự toán tháng và giải thích rõ cách tính.

### Giai đoạn 5 — Dashboard và báo cáo

- Tổng hợp theo nhà/phòng/type/device.
- Biểu đồ ngày/tháng.
- Top tiêu thụ và dữ liệu còn thiếu.
- Monthly summary chạy bằng scheduler.
- Export CSV trước; PDF để sau nếu thật sự cần.

**Hoàn thành khi:** người dùng biết thiết bị nào đang chiếm phần lớn điện năng và dữ liệu nào còn thiếu để tăng độ chính xác.

### Giai đoạn 6 — Deploy production

- GitHub Actions test/build.
- Self-hosted deploy sau khi test thành công.
- Image tag theo commit SHA.
- Migration trước khi recreate app services.
- Restart queue sau deploy.
- Chờ health của app, queue, queue-ai và scheduler.
- Smoke test `/up`, `/version`, `/login` và Vite asset.
- Rollback bằng image tag nếu deploy lỗi.
- Backup DB/ảnh và kiểm tra restore.

## 9. Thứ tự sprint đề xuất

| Sprint | Phạm vi |
|---|---|
| 1 | Giai đoạn 0 |
| 2 | Giai đoạn 1 |
| 3 | Giai đoạn 2 |
| 4 | Media và upload thuộc Giai đoạn 3 |
| 5 | AI Vision, review và confirmation |
| 6 | Energy engine và tariff |
| 7 | Dashboard, báo cáo và polish mobile |
| 8 | Production deploy, backup, monitoring và hardening |

Mỗi sprint phải kết thúc với: migration, factory/seeder cần thiết, Feature/Unit tests, cập nhật README module, Pint, build frontend và kiểm tra quyền truy cập.

## 10. Phạm vi chưa làm trong MVP

- Điều khiển bật/tắt thiết bị.
- Kết nối trực tiếp mọi hãng smart plug.
- Theo dõi điện thời gian thực.
- Tự động đọc hóa đơn điện với độ chính xác cam kết.
- Machine learning dự báo phức tạp.
- Marketplace hoặc so sánh giá mua thiết bị.

Sau MVP có thể thêm adapter MQTT/Home Assistant/Tuya hoặc smart plug, nhưng dữ liệu tích hợp phải đi qua cùng mô hình `energy_readings`.

## 11. Các quyết định cần giữ ổn định

1. Port host: web `8087`, MySQL `3311`, Redis `6384`.
2. Port nội bộ: MySQL `3306`, Redis `6379`, PHP-FPM `9000`.
3. AI chỉ đề xuất; người dùng xác nhận.
4. Ảnh là private theo mặc định.
5. Kết quả phải phân biệt đo thực tế và ước lượng.
6. Biểu giá có version, không hardcode.
7. AI chạy qua queue riêng.
8. Mọi dữ liệu được scope theo nhà và policy.
9. Docs + Tests là điều kiện hoàn thành.
10. Deploy phải có healthcheck, release visibility, smoke test và rollback.

## 12. Bước triển khai tiếp theo

Bắt đầu Sprint 1 bằng việc scaffold Laravel 12 và tạo đầy đủ runtime Docker theo mục 3. Chưa tích hợp AI ngay trong bước đầu; trước hết phải làm cho app, MySQL, Redis, queue, scheduler, CI và release endpoint chạy ổn định trên đúng các port đã chốt.
