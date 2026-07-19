# HomeWatt - Kế hoạch phát triển tính năng

## Tổng quan

| Giai đoạn | Thời gian dự kiến | Số tính năng | Mức độ ưu tiên |
|-----------|-------------------|-------------|----------------|
| Phase 1 | Tuần 1-2 | 4 tính năng | CAO |
| Phase 2 | Tuần 3-4 | 4 tính năng | TRUNG BÌNH |
| Phase 3 | Tuần 5-6 | 4 tính năng | THẤP |

---

## Phase 1 — Tác động lớn, nền tảng cho các phase sau

---

### 1.1 Dark Mode

**Mục tiêu:** Hỗ trợ giao diện tối toàn bộ ứng dụng, toggle thủ công + auto theo OS

**Hiện trạng:**
- Tailwind CSS 3 đã hỗ trợ `dark:` variant
- Layout chính: `app.blade.php`, `guest.blade.php`, `navigation.blade.php`, `sidebar.blade.php`
- Tất cả component dùng class cứng: `bg-white`, `text-slate-900`, `border-slate-200`...

**Các bước thực hiện:**

1. **Bật dark mode trong Tailwind config**
```js
// tailwind.config.js
module.exports = {
    darkMode: 'class', // toggle bằng class trên <html>
    // ...
}
```

2. **Thêm Alpine.js store quản lý theme**
```js
// resources/js/app.js
document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        mode: localStorage.getItem('theme') || 'system',

        init() {
            this.apply()
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.mode === 'system') this.apply()
            })
        },

        toggle() {
            this.mode = this.mode === 'dark' ? 'light' : 'dark'
            localStorage.setItem('theme', this.mode)
            this.apply()
        },

        apply() {
            const isDark = this.mode === 'dark' ||
                (this.mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
            document.documentElement.classList.toggle('dark', isDark)
        },

        get icon() {
            if (this.mode === 'dark') return '🌙'
            if (this.mode === 'light') return '☀️'
            return '🖥️'
        }
    })
})
```

3. **Thêm toggle button vào navigation**
```blade
{{-- resources/views/layouts/navigation.blade.php --}}
<button @click="$store.theme.toggle()"
        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"
        :aria-label="$store.theme.mode">
    <span x-text="$store.theme.icon"></span>
</button>
```

4. **Migrate CSS — chiến lược 3 bước:**
- **Step A:** Tạo base dark variables
```css
/* resources/css/app.css */
@layer base {
    :root {
        --bg-primary: #ffffff;
        --bg-secondary: #f8fafc;
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --border: #e2e8f0;
    }
    .dark {
        --bg-primary: #0f172a;
        --bg-secondary: #1e293b;
        --text-primary: #f1f5f9;
        --text-secondary: #94a3b8;
        --border: #334155;
    }
}
```
- **Step B:** Tạo component Blade tái sử dụng (`card`, `input`, `badge`...) với dark variant
- **Step C:** Cập nhật từng view: `bg-white` → `bg-white dark:bg-slate-900`, `text-slate-900` → `text-slate-900 dark:text-slate-100`, `border-slate-200` → `border-slate-200 dark:border-slate-700`

5. **CSS classes cần dark variant — mapping nhanh:**
```
bg-white          → bg-white dark:bg-slate-900
bg-gray-50        → bg-gray-50 dark:bg-slate-800
bg-slate-50       → bg-slate-50 dark:bg-slate-800
text-slate-900    → text-slate-900 dark:text-slate-100
text-slate-700    → text-slate-700 dark:text-slate-300
text-slate-500    → text-slate-500 dark:text-slate-400
border-slate-200  → border-slate-200 dark:border-slate-700
border-slate-100  → border-slate-100 dark:border-slate-800
shadow-sm         → shadow-sm dark:shadow-slate-900/30
bg-gradient-*     → giữ nguyên vì đã có màu riêng
```

6. **Testing checklist:**
- Toggle hoạt động trên tất cả các trang
- Persist qua reload (localStorage)
- Auto-detect OS preference khi mode = 'system'
- Chart.js chart có dark variant (đổi màu grid, label)
- Không bị flash khi load trang (dùng inline script trong `<head>`)

**Files cần sửa (ước tính):**
- `tailwind.config.js` — 1 dòng
- `resources/css/app.css` — thêm CSS variables
- `resources/js/app.js` — thêm Alpine store
- `resources/views/layouts/navigation.blade.php` — thêm toggle button
- `resources/views/layouts/app.blade.php` — thêm dark class
- `resources/views/layouts/guest.blade.php` — thêm dark class
- `resources/views/layouts/sidebar.blade.php` — ~10 dòng
- `resources/views/components/*.blade.php` — ~5 files
- Tất cả module Blade views (~40 files) — mỗi file ~5-10 dòng

**Thời gian:** 1-2 ngày

---

### 1.2 Hệ thống Notification đa kênh

**Mục tiêu:** User nhận được thông báo qua in-app + email + Telegram (đã có) + push web. Có thể chọn kênh theo loại thông báo.

**Database Schema mới:**

```sql
-- Bảng 1: Notification Templates (cho phép admin/quản lý custom mẫu)
CREATE TABLE notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,          -- 'budget_80_percent', 'energy_spike', 'weekly_summary'
    name VARCHAR(100) NOT NULL,                -- 'Cảnh báo ngân sách 80%'
    channels JSON NOT NULL DEFAULT '[]',        -- ['mail', 'telegram', 'push', 'in_app']
    mail_subject VARCHAR(255) NULL,
    mail_body TEXT NULL,                        -- Hỗ trợ Markdown + placeholder {{amount}}
    telegram_body TEXT NULL,
    push_title VARCHAR(100) NULL,
    push_body TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Bảng 2: User Notification Preferences
CREATE TABLE user_notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    template_code VARCHAR(50) NOT NULL,       -- NULL = global default
    channels JSON NOT NULL,                    -- Kênh user chọn cho template này
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,  -- Tắt hoàn toàn loại thông báo này
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, template_code),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_code) REFERENCES notification_templates(code) ON DELETE CASCADE
);

-- Bảng 3: Notification Log (đã gửi)
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    home_id BIGINT UNSIGNED NULL,
    template_code VARCHAR(50) NOT NULL,
    channel VARCHAR(20) NOT NULL,              -- 'mail', 'telegram', 'push', 'in_app'
    title VARCHAR(255) NULL,
    body TEXT NULL,
    data JSON NULL,                             -- Context data đã dùng để render
    read_at TIMESTAMP NULL,                     -- Cho in-app notification
    sent_at TIMESTAMP NOT NULL DEFAULT NOW(),
    created_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, read_at),
    INDEX idx_sent (sent_at)
);

-- Bảng 4: Push Subscription (cho Web Push)
CREATE TABLE push_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    endpoint VARCHAR(500) NOT NULL UNIQUE,
    public_key VARCHAR(255) NULL,
    auth_token VARCHAR(255) NULL,
    content_encoding VARCHAR(50) DEFAULT 'aesgcm',
    user_agent VARCHAR(255) NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Kiến trúc Service:**

```php
// Modules/Notification/app/Services/NotificationService.php

class NotificationService
{
    public function __construct(
        private MailChannel $mail,
        private TelegramChannel $telegram,
        private PushChannel $push,
        private InAppChannel $inApp,
    ) {}

    /**
     * Gửi notification theo template code.
     * Tự động chọn kênh dựa trên user preference + template config.
     */
    public function send(string $templateCode, User $user, array $data = []): void
    {
        $template = NotificationTemplate::where('code', $templateCode)->firstOrFail();
        $pref = UserNotificationPreference::where('user_id', $user->id)
            ->where('template_code', $templateCode)
            ->first();

        if ($pref && !$pref->is_enabled) return;

        $channels = $pref?->channels ?? $template->channels;

        foreach ($channels as $channel) {
            $this->channels()[$channel]->send($template, $user, $data);
        }
    }
}
```

**Tích hợp vào các service hiện tại:**

```php
// Modules/Expense/Services/ExpenseService.php - checkBudgetsAndAlert()
// Thay thế đoạn gửi Telegram trực tiếp bằng:
app(NotificationService::class)->send('budget_80_percent', $user, [
    'category_name' => $catName,
    'limit' => $limit,
    'current_spending' => $currentSpending,
    'percentage' => round($newPct, 1),
    'expense_description' => $expense->description,
    'expense_amount' => $amount,
]);
```

**In-App Notification UI:**

```blade
{{-- Dropdown notification bell trong navigation --}}
<div x-data="{ open: false, notifications: [], unread: 0 }"
     x-init="loadNotifications()"
     class="relative">
    <button @click="open = !open; if (open) markAllRead()"
            class="relative p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
        🔔
        <span x-show="unread > 0"
              x-text="unread"
              class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
        </span>
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 z-50 max-h-96 overflow-y-auto">
        <template x-if="notifications.length === 0">
            <p class="p-4 text-sm text-slate-500 text-center">Chưa có thông báo nào</p>
        </template>
        <template x-for="n in notifications">
            <div class="p-3 border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <p class="text-sm font-medium text-slate-800 dark:text-slate-200" x-text="n.title"></p>
                <p class="text-xs text-slate-500 mt-0.5" x-text="n.body"></p>
                <p class="text-[10px] text-slate-400 mt-1" x-text="n.time_ago"></p>
            </div>
        </template>
    </div>
</div>
```

**Web Push Integration (PWA):**

```js
// resources/js/push.js
export async function subscribeToPush() {
    const registration = await navigator.serviceWorker.ready
    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(PUSH_PUBLIC_KEY) // từ config
    })

    await fetch('/api/v1/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(subscription.toJSON()),
    })
}
```

**Seeder cho notification_templates:**

```php
// Modules/Notification/database/seeders/DefaultTemplatesSeeder.php
NotificationTemplate::insert([
    [
        'code' => 'budget_80_percent',
        'name' => 'Cảnh báo ngân sách 80%',
        'channels' => json_encode(['telegram', 'in_app']),
        'telegram_body' => "⚠️ *CẢNH BÁO SẮP ĐẠT HẠN MỨC*\n\nHạn mức: *{{limit}}*\nĐã chi: *{{current_spending}}* ({{percentage}}%)",
        'push_title' => '⚠️ Sắp vượt ngân sách',
        'push_body' => 'Bạn đã chi {{percentage}}% hạn mức {{category_name}}',
    ],
    [
        'code' => 'budget_100_percent',
        'name' => 'Cảnh báo vượt ngân sách',
        'channels' => json_encode(['telegram', 'in_app', 'push']),
        // ...
    ],
    // ... thêm các template khác
]);
```

**API Routes:**

```php
// Modules/Notification/routes/api.php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('notification-preferences', [NotificationPreferenceController::class, 'update']);
    Route::post('push/subscribe', [PushSubscriptionController::class, 'subscribe']);
    Route::delete('push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
});
```

**Thời gian:** 3-5 ngày

---

### 1.3 So sánh cùng kỳ (Year-over-Year)

**Mục tiêu:** Dashboard hiển thị % thay đổi so với tháng trước và cùng kỳ năm trước. Áp dụng cho cả Energy Dashboard và Expense Dashboard.

**Các bước thực hiện:**

1. **Thêm helper methods vào model Home**

```php
// Modules/Home/app/Models/Home.php

/**
 * So sánh chi tiêu tháng hiện tại với tháng trước / cùng kỳ.
 */
public function expenseComparison(string $period = 'month'): array
{
    $now = now();
    $currentStart = $period === 'month' ? $now->copy()->startOfMonth() : $now->copy()->startOfDay();
    $currentEnd = $now->copy()->endOfMonth();

    $lastStart = $currentStart->copy()->subMonth();
    $lastEnd = $currentEnd->copy()->subMonth();

    $lastYearStart = $currentStart->copy()->subYear();
    $lastYearEnd = $currentEnd->copy()->subYear();

    $currentTotal = Expense::where('home_id', $this->id)
        ->where('type', 'expense')
        ->whereNull('transfer_id')
        ->whereBetween('occurred_at', [$currentStart, $currentEnd])
        ->sum('amount');

    $lastMonthTotal = Expense::where('home_id', $this->id)
        ->where('type', 'expense')
        ->whereNull('transfer_id')
        ->whereBetween('occurred_at', [$lastStart, $lastEnd])
        ->sum('amount');

    $lastYearTotal = Expense::where('home_id', $this->id)
        ->where('type', 'expense')
        ->whereNull('transfer_id')
        ->whereBetween('occurred_at', [$lastYearStart, $lastYearEnd])
        ->sum('amount');

    return [
        'current' => (float) $currentTotal,
        'vs_last_month' => $lastMonthTotal > 0
            ? round((($currentTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : null,
        'vs_last_year' => $lastYearTotal > 0
            ? round((($currentTotal - $lastYearTotal) / $lastYearTotal) * 100, 1)
            : null,
    ];
}
```

2. **Thêm ComparisonCard Blade component**

```blade
{{-- resources/views/components/comparison-card.blade.php --}}
@props(['label', 'current', 'previous', 'previousLabel', 'currency' => 'đ', 'inverted' => false])

@php
    $diff = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null;
    $isUp = $diff > 0;
    $isBad = $inverted ? !$isUp : $isUp; // Với expense: tăng là xấu, với income: tăng là tốt
@endphp

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
    <p class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-1">
        {{ number_format($current, 0, ',', '.') }} {{ $currency }}
    </p>
    @if ($diff !== null)
        <div class="flex items-center gap-1 mt-2">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                {{ $isBad ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                           'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                {{ $isUp ? '↑' : '↓' }} {{ abs($diff) }}%
            </span>
            <span class="text-xs text-slate-400">vs {{ $previousLabel }}</span>
        </div>
    @endif
</div>
```

3. **Tích hợp vào Dashboard**

```php
// Modules/Dashboard/app/Http/Controllers/DashboardController.php

public function index(Request $request): View
{
    // ... existing code ...
    $comparison = $home->expenseComparison('month');

    return view('dashboard::index', compact('comparison', /* ... */));
}
```

4. **Chart so sánh tháng này vs tháng trước trên cùng 1 biểu đồ**

```js
// Trong dashboard/index.blade.php - Chart.js config
const comparisonChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: daysInMonth,
        datasets: [
            {
                label: '{{ __("dashboard.this_month") }}',
                data: currentMonthData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
            },
            {
                label: '{{ __("dashboard.last_month") }}',
                data: lastMonthData,
                borderColor: '#94a3b8',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
            },
        ],
    },
});
```

**Thời gian:** 1-2 ngày

---

### 1.4 Goal / Savings Tracking

**Mục tiêu:** User tạo mục tiêu tài chính hoặc năng lượng, theo dõi tiến độ % trên dashboard.

**Database Schema:**

```sql
CREATE TABLE goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    home_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,           -- Người tạo goal
    name VARCHAR(255) NOT NULL,                  -- 'Tiết kiệm mua xe', 'Giảm 20% tiền điện'
    type ENUM('savings', 'debt_payoff', 'energy_reduction', 'expense_limit', 'income_target') NOT NULL,
    target_amount DECIMAL(15,2) NOT NULL,        -- Số tiền / kWh mục tiêu
    current_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    icon VARCHAR(10) DEFAULT '🎯',
    color VARCHAR(7) DEFAULT '#3b82f6',
    category_id BIGINT UNSIGNED NULL,            -- NULL = toàn bộ, có = chỉ category đó
    wallet_id BIGINT UNSIGNED NULL,              -- NULL = tất cả ví
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (home_id) REFERENCES homes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL,
    INDEX idx_home_status (home_id, status),
    INDEX idx_ends_at (ends_at)
);

-- Bảng snapshot hàng ngày để vẽ biểu đồ tiến độ
CREATE TABLE goal_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id BIGINT UNSIGNED NOT NULL,
    snapshot_date DATE NOT NULL,
    current_amount DECIMAL(15,2) NOT NULL,       -- Giá trị tại ngày snapshot
    percentage DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    UNIQUE KEY (goal_id, snapshot_date)
);
```

**Model:**

```php
// Modules/Goal/app/Models/Goal.php

class Goal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'home_id', 'user_id', 'name', 'type',
        'target_amount', 'current_amount',
        'starts_at', 'ends_at', 'icon', 'color',
        'category_id', 'wallet_id', 'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function home(): BelongsTo { return $this->belongsTo(Home::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(ExpenseCategory::class); }
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function snapshots(): HasMany { return $this->hasMany(GoalSnapshot::class); }

    /** Tính % hoàn thành */
    public function percentage(): float
    {
        if ($this->target_amount <= 0) return 0;
        return min(100, round(($this->current_amount / $this->target_amount) * 100, 1));
    }

    /** Cập nhật current_amount dựa vào loại goal */
    public function recalculate(): void
    {
        $this->current_amount = match ($this->type) {
            'savings' => $this->calculateSavings(),
            'debt_payoff' => $this->calculateDebtPayoff(),
            'energy_reduction' => $this->calculateEnergySaved(),
            'expense_limit' => $this->calculateExpenseTotal(),
            'income_target' => $this->calculateIncomeTotal(),
        };
        $this->save();
    }

    private function calculateSavings(): float
    {
        $income = Expense::where('home_id', $this->home_id)
            ->where('type', 'income')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$this->starts_at, $this->ends_at])
            ->sum('amount');

        $expense = Expense::where('home_id', $this->home_id)
            ->where('type', 'expense')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$this->starts_at, $this->ends_at])
            ->sum('amount');

        return (float) $income - (float) $expense;
    }
    // ... các helper calculation method khác
}
```

**Artisan Command cho daily snapshot:**

```php
// Modules/Goal/app/Console/Commands/SnapshotGoals.php

class SnapshotGoals extends Command
{
    protected $signature = 'goals:snapshot';
    protected $description = 'Take daily snapshots of all active goals';

    public function handle(): void
    {
        Goal::where('status', 'active')
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->chunkById(100, function ($goals) {
                foreach ($goals as $goal) {
                    $goal->recalculate();
                    $goal->snapshots()->firstOrCreate(
                        ['snapshot_date' => now()->toDateString()],
                        [
                            'current_amount' => $goal->current_amount,
                            'percentage' => $goal->percentage(),
                        ]
                    );

                    if ($goal->percentage() >= 100) {
                        $goal->forceFill([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ])->save();
                    }
                }
            });
    }
}
```

**Dashboard Widget:**

```blade
{{-- Progress bar component --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
    <div class="flex justify-between items-start mb-2">
        <div class="flex items-center gap-2">
            <span>{{ $goal->icon }}</span>
            <h3 class="font-semibold text-slate-900 dark:text-slate-100 text-sm">{{ $goal->name }}</h3>
        </div>
        <span class="text-xs font-bold text-slate-500">{{ $goal->percentage() }}%</span>
    </div>
    <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all duration-500"
             style="width: {{ $goal->percentage() }}%; background-color: {{ $goal->color }}">
        </div>
    </div>
    <div class="flex justify-between mt-1.5 text-[10px] text-slate-400">
        <span>{{ number_format($goal->current_amount, 0, ',', '.') }} {{ $goal->home?->currency }}</span>
        <span>{{ number_format($goal->target_amount, 0, ',', '.') }} {{ $goal->home?->currency }}</span>
    </div>
</div>
```

**Thời gian:** 3-5 ngày

---

## Phase 2 — Tăng trải nghiệm người dùng

---

### 2.1 Bank Statement Import (CSV/Excel)

**Mục tiêu:** User upload file sao kê ngân hàng → hệ thống parse và tự động tạo expense, match category + wallet.

**Supported formats:**
- **VCB**: `Ngày, Số tiền ghi nợ, Số tiền ghi có, Nội dung chi tiết`
- **Techcombank**: `Transaction Date, Amount, Description, Balance`
- **Momo**: CSV export với cột `Số tiền, Nội dung, Ngày giao dịch`

**Kiến trúc:**

```php
// Modules/Expense/app/Imports/BankStatementImport.php

class BankStatementImport
{
    public function __construct(
        private BankParserFactory $parserFactory,
        private ExpenseService $expenseService,
    ) {}

    public function import(string $filePath, int $homeId, User $user): ImportResult
    {
        $rows = $this->readExcel($filePath);
        $bank = $this->detectBank($rows[0] ?? []); // Detect VCB/Techcombank/Momo từ header

        $parser = $this->parserFactory->make($bank);
        $parsed = $parser->parse($rows);

        $result = new ImportResult();

        DB::transaction(function () use ($parsed, $homeId, $user, $result) {
            foreach ($parsed as $transaction) {
                try {
                    // Auto-match wallet
                    $wallet = $this->matchWallet($transaction, $homeId);
                    // Auto-match category
                    $category = $this->matchCategory($transaction, $homeId);

                    $expense = $this->expenseService->createExpense([
                        'home_id' => $homeId,
                        'wallet_id' => $wallet->id,
                        'category_id' => $category->id,
                        'type' => $transaction['type'],
                        'amount' => $transaction['amount'],
                        'description' => $transaction['description'],
                        'occurred_at' => $transaction['date'],
                        'reference' => $transaction['reference'] ?? null,
                    ], $user);

                    $result->addSuccess($expense);
                } catch (\Throwable $e) {
                    $result->addError($transaction, $e->getMessage());
                }
            }
        });

        return $result;
    }
}
```

**Parser Strategy Pattern:**

```php
// Modules/Expense/app/Imports/Parsers/BankParser.php
interface BankParser
{
    /** @return array<int, array{date: string, amount: float, type: string, description: string}> */
    public function parse(array $rows): array;
}

// VcbParser.php - đọc cột: Ngày giao dịch, Số tiền, Nội dung
// TechcombankParser.php - đọc cột: Transaction Date, Amount, Description
// MomoParser.php - đọc cột: Ngày, Số tiền, Nội dung
```

**Auto-categorization logic (tận dụng TelegramParserService):**

```php
private function matchCategory(array $transaction, int $homeId): ExpenseCategory
{
    // Dùng logic TelegramParserService::autoMatchCategory() đã có
    $categoryName = TelegramParserService::autoMatchCategory(
        mb_strtolower($transaction['description']),
        $transaction['type']
    );

    return ExpenseCategory::where('home_id', $homeId)
        ->where('type', $transaction['type'])
        ->where('name', $categoryName)
        ->first()
        ?? $this->fallbackCategory($homeId, $transaction['type']);
}
```

**Import UI Flow:**

```blade
{{-- Trang import --}}
<div x-data="bankImport()">
    {{-- Step 1: Chọn home + upload file --}}
    <input type="file" accept=".csv,.xlsx,.xls" @change="handleFile">

    {{-- Step 2: Preview table --}}
    <table x-show="preview.length > 0">
        <thead><tr><th>Ngày</th><th>Mô tả</th><th>Số tiền</th><th>Ví</th><th>Danh mục</th></tr></thead>
        <tbody>
            <template x-for="(row, i) in preview">
                <tr>
                    <td x-text="row.date"></td>
                    <td x-text="row.description"></td>
                    <td x-text="formatMoney(row.amount)"></td>
                    <td>
                        <select x-model="row.wallet_id">...</select>  {{-- User có thể sửa --}}
                    </td>
                    <td>
                        <select x-model="row.category_id">...</select> {{-- User có thể sửa --}}
                    </td>
                </tr>
            </template>
        </tbody>
    </table>

    {{-- Step 3: Confirm import --}}
    <button @click="importAll">Nhập {{ preview.length }} giao dịch</button>
</div>
```

**Thời gian:** 3-5 ngày

---

### 2.2 Predictive Maintenance cho thiết bị

**Mục tiêu:** Phát hiện thiết bị tiêu thụ điện bất thường → gợi ý bảo trì.

**Logic phát hiện:**

```php
// Modules/Energy/app/Services/AnomalyDetector.php

class AnomalyDetector
{
    /**
     * Phát hiện thiết bị có mức tiêu thụ tăng đột biến.
     * So sánh trung bình 7 ngày gần nhất với trung bình 30 ngày.
     */
    public function detect(Device $device): ?AnomalyResult
    {
        $recentAvg = EnergyReading::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subDays(7))
            ->avg('kwh');

        $baselineAvg = EnergyReading::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subDays(37))
            ->where('recorded_at', '<', now()->subDays(7))
            ->avg('kwh');

        if (!$baselineAvg || $baselineAvg <= 0) return null;

        $ratio = $recentAvg / $baselineAvg;

        if ($ratio >= 1.5) {
            $severity = $ratio >= 2.0 ? 'high' : 'medium';

            return new AnomalyResult(
                device: $device,
                ratio: round($ratio, 1),
                severity: $severity,
                recommendation: $this->buildRecommendation($device, $severity),
            );
        }

        // Kiểm tra lịch bảo trì
        if ($device->last_maintained_at && $device->last_maintained_at->diffInMonths(now()) >= 6) {
            return new AnomalyResult(
                device: $device,
                ratio: null,
                severity: 'low',
                recommendation: "Thiết bị chưa được bảo trì hơn 6 tháng. Cân nhắc kiểm tra định kỳ.",
            );
        }

        return null;
    }

    private function buildRecommendation(Device $device, string $severity): string
    {
        $extraKwh = /* calculate extra kWh/month */;
        $extraCost = /* convert to VND using tariff */;

        return match ($device->deviceType?->slug) {
            'air_conditioner' => "Máy lạnh tiêu thụ cao hơn bình thường. Vệ sinh lưới lọc và kiểm tra gas.",
            'refrigerator' => "Tủ lạnh hao điện bất thường. Kiểm tra gioăng cửa và dàn nóng.",
            'water_heater' => "Bình nóng lạnh tiêu thụ cao bất thường. Kiểm tra thanh đốt và cặn bám.",
            default => "Thiết bị đang tiêu thụ {$severity === 'high' ? 'cao' : 'hơi cao'} hơn mức trung bình. Cân nhắc kiểm tra, bảo trì.",
        } . " Chi phí tăng thêm ước tính: " . number_format($extraCost) . 'đ/tháng.';
    }
}
```

**Tích hợp vào Dashboard:**

```blade
@foreach($anomalies as $anomaly)
    <div class="flex items-start gap-3 p-3 rounded-xl border
        {{ $anomaly->severity === 'high' ? 'bg-red-50 border-red-200 dark:bg-red-900/10 dark:border-red-800' :
           ($anomaly->severity === 'medium' ? 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/10 dark:border-yellow-800' :
            'bg-blue-50 border-blue-200 dark:bg-blue-900/10 dark:border-blue-800') }}">
        <span class="text-xl">{{ $anomaly->severity === 'high' ? '🔴' : ($anomaly->severity === 'medium' ? '🟡' : '🔵') }}</span>
        <div>
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $anomaly->device->name }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ $anomaly->recommendation }}</p>
        </div>
    </div>
@endforeach
```

**Thời gian:** 2-3 ngày

---

### 2.3 Receipt Gallery

**Mục tiêu:** Xem lại tất cả hóa đơn đã scan theo dạng gallery ảnh, kèm transaction tương ứng.

**Schema bổ sung:**

```sql
-- Thêm cột media_id vào bảng expenses (nếu scan từ ảnh)
ALTER TABLE expenses ADD COLUMN media_id BIGINT UNSIGNED NULL AFTER transfer_id;
ALTER TABLE expenses ADD FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE SET NULL;
```

**Khi AI scan hóa đơn, lưu ảnh vào Media + liên kết:**

```php
// Trong TelegramWebhookController::storeReceiptPreview()
$media = Media::create([
    'home_id' => $pending['payload']['home_id'],
    'user_id' => $user->id,
    'file_path' => 'receipts/' . $fileName,
    'file_name' => $fileName,
    'mime_type' => 'image/jpeg',
    'file_size' => strlen($imageBinary),
    'collection' => 'receipts',
]);

$expense = $expenseService->createExpense([
    ...$payload,
    'media_id' => $media->id,
], $user);
```

**Gallery View:**

```blade
{{-- Modules/Expense/resources/views/receipts/index.blade.php --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($receipts as $receipt)
        <div class="relative group bg-white dark:bg-slate-800 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
            <img src="{{ $receipt->media->url }}"
                 loading="lazy"
                 class="w-full h-48 object-cover"
                 alt="Hóa đơn {{ $receipt->description }}">
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition flex items-end p-3">
                <div class="opacity-0 group-hover:opacity-100 transition text-white text-sm w-full">
                    <p class="font-semibold">{{ number_format($receipt->amount, 0, ',', '.') }}đ</p>
                    <p class="text-xs text-white/80">{{ $receipt->category?->name }}</p>
                    <p class="text-xs text-white/60">{{ $receipt->occurred_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
```

**Thời gian:** 2-3 ngày

---

### 2.4 Link mời Home

**Mục tiêu:** Tạo link mời với token, gửi cho người khác → click link → join home tự động.

**Schema:**

```sql
CREATE TABLE home_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    home_id BIGINT UNSIGNED NOT NULL,
    invited_by BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    role ENUM('manager', 'member', 'viewer') NOT NULL DEFAULT 'member',
    expires_at TIMESTAMP NOT NULL,
    max_uses INT DEFAULT 1,
    use_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP,
    FOREIGN KEY (home_id) REFERENCES homes(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
);
```

**Flow:**

```
1. Owner/Manager click "Mời thành viên" → chọn role → tạo link
2. Link: homewatt.app/invite/abc123def456
3. Người nhận click link:
   - Nếu chưa đăng nhập → redirect login → sau login redirect về invite
   - Nếu đã đăng nhập → hiển thị "Bạn được mời vào nhà X với role Y" → Accept
4. Accept → HomeMember::create() → redirect vào home dashboard
```

**Controller:**

```php
// Modules/Home/app/Http/Controllers/InvitationController.php

class InvitationController extends Controller
{
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = HomeInvitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->with('home')
            ->firstOrFail();

        if ($invitation->use_count >= $invitation->max_uses) {
            abort(410, 'Link mời đã hết lượt sử dụng.');
        }

        $user = $request->user();
        if ($invitation->home->isMember($user->id)) {
            return redirect()->route('homes.show', $invitation->home)
                ->with('info', 'Bạn đã là thành viên của nhà này.');
        }

        DB::transaction(function () use ($invitation, $user) {
            $membership = HomeMember::create([
                'home_id' => $invitation->home_id,
                'user_id' => $user->id,
            ]);
            $membership->assignRole($invitation->role);

            $invitation->increment('use_count');
        });

        return redirect()->route('homes.show', $invitation->home)
            ->with('success', 'Chào mừng bạn đến với '.$invitation->home->name.'!');
    }
}
```

**Thời gian:** 1-2 ngày

---

## Phase 3 — Nice to have, nâng cao trải nghiệm

---

### 3.1 Voice Input qua Telegram

**Flow:**

```
1. User gửi voice message cho Telegram Bot
2. Webhook nhận voice → download file .ogg
3. Gửi file .ogg đến Google Speech-to-Text API hoặc OpenAI Whisper
4. Nhận text transcript → parse bằng TelegramParserService hiện có
5. Xác nhận kết quả cho user (giống như scan hóa đơn)
```

**Implementation:**

```php
// TelegramWebhookController::handle() - thêm xử lý voice
$voice = $request->input('message.voice');
if (!empty($voice)) {
    $this->handleVoiceInput($chatId, $voice, $expenseService);
    return response()->json(['ok' => true]);
}

private function handleVoiceInput(int $chatId, array $voice, ExpenseService $expenseService): void
{
    $token = config('services.telegram.bot_token');
    $fileId = $voice['file_id'];

    // 1. Download voice file from Telegram
    $filePath = $this->getTelegramFilePath($token, $fileId);
    $audioContent = Http::get("https://api.telegram.org/file/bot{$token}/{$filePath}")->body();

    // 2. Transcribe via Whisper
    $text = app(VoiceTranscriber::class)->transcribe($audioContent);

    // 3. Parse text like normal message
    $parser = app(TelegramParserService::class);
    // ... reuse existing text processing flow
}

// Modules/AI/app/Services/VoiceTranscriber.php
class VoiceTranscriber
{
    public function transcribe(string $audioContent): string
    {
        $response = Http::timeout(30)
            ->withHeader('Authorization', 'Bearer ' . config('ai.providers.openai.api_key'))
            ->attach('file', $audioContent, 'voice.ogg')
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'vi',
            ]);

        return $response->json('text', '');
    }
}
```

**Thời gian:** 3-5 ngày

---

### 3.2 Energy Anomaly Detection

**Đã có nền tảng từ Predictive Maintenance (Phase 2).** Mở rộng thêm:

```php
// Modules/Energy/app/Services/AnomalyDetector.php

class AnomalyDetector
{
    /**
     * Phát hiện spike bất thường trong 24h qua.
     * So sánh mỗi giờ với trung bình 7 ngày cùng khung giờ.
     */
    public function detectHourlySpikes(int $homeId): array
    {
        $devices = Device::whereHas('room', fn($q) => $q->where('home_id', $homeId))->get();
        $anomalies = [];

        foreach ($devices as $device) {
            $hourlyData = EnergyReading::where('device_id', $device->id)
                ->where('recorded_at', '>=', now()->subHours(24))
                ->selectRaw('HOUR(recorded_at) as hour, SUM(kwh) as total')
                ->groupBy('hour')
                ->get()
                ->keyBy('hour');

            $baseline = EnergyReading::where('device_id', $device->id)
                ->where('recorded_at', '>=', now()->subDays(8))
                ->where('recorded_at', '<', now()->subHours(24))
                ->selectRaw('HOUR(recorded_at) as hour, AVG(kwh) as avg_total')
                ->groupBy('hour')
                ->get()
                ->keyBy('hour');

            foreach ($hourlyData as $hour => $data) {
                $avg = $baseline[$hour]->avg_total ?? 0;
                if ($avg > 0 && $data->total > $avg * 2) {
                    $anomalies[] = [
                        'device' => $device,
                        'hour' => $hour,
                        'actual' => $data->total,
                        'expected' => $avg,
                        'ratio' => round($data->total / $avg, 1),
                    ];
                }
            }
        }

        return $anomalies;
    }
}
```

**Alert qua NotificationService:**

```php
// App/Console/Commands/CheckEnergyAnomalies.php
// Chạy mỗi giờ qua schedule

foreach ($anomalies as $a) {
    app(NotificationService::class)->send('energy_anomaly', $user, [
        'device_name' => $a['device']->name,
        'actual' => $a['actual'],
        'expected' => $a['expected'],
        'ratio' => $a['ratio'],
    ]);
}
```

**Thời gian:** 2-3 ngày

---

### 3.3 Multi-home Dashboard Tổng hợp

**Mục tiêu:** User có nhiều home (nhà riêng, nhà bố mẹ, văn phòng) → xem tổng quan tất cả.

**Logic:**

```php
// Modules/Dashboard/app/Http/Controllers/DashboardController.php

public function overview(Request $request): View
{
    $user = $request->user();
    $memberships = $user->homeMembers()->with('home')->get();

    $totals = [
        'total_balance' => 0,
        'monthly_income' => 0,
        'monthly_expense' => 0,
        'monthly_energy_kwh' => 0,
        'monthly_energy_cost' => 0,
    ];

    $homeDetails = [];

    foreach ($memberships as $membership) {
        $home = $membership->home;
        $stats = $this->getHomeStats($home);

        $totals['total_balance'] += $stats['balance'];
        $totals['monthly_income'] += $stats['income'];
        $totals['monthly_expense'] += $stats['expense'];
        $totals['monthly_energy_kwh'] += $stats['energy_kwh'];
        $totals['monthly_energy_cost'] += $stats['energy_cost'];

        $homeDetails[] = [
            'home' => $home,
            'stats' => $stats,
        ];
    }

    return view('dashboard::overview', compact('totals', 'homeDetails'));
}
```

**UI: Dashboard tổng hợp với các card + bảng so sánh các home:**

```blade
{{-- Tổng tất cả các home --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Tổng số dư" :value="$totals['total_balance']" icon="💰" />
    <x-stat-card label="Thu nhập tháng" :value="$totals['monthly_income']" icon="📈" />
    <x-stat-card label="Chi tiêu tháng" :value="$totals['monthly_expense']" icon="📉" />
    <x-stat-card label="Tiền điện tháng" :value="$totals['monthly_energy_cost']" icon="⚡" />
</div>

{{-- Bảng so sánh các home --}}
@foreach($homeDetails as $detail)
    <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl border ...">
        <div>
            <h3 class="font-semibold">{{ $detail['home']->name }}</h3>
            <p class="text-xs text-slate-500">{{ $detail['home']->members->count() }} thành viên</p>
        </div>
        <div class="flex gap-4 text-sm text-right">
            <div>
                <p class="text-slate-500">Số dư</p>
                <p class="font-bold">{{ number_format($detail['stats']['balance']) }}đ</p>
            </div>
            <div>
                <p class="text-slate-500">Điện</p>
                <p class="font-bold">{{ number_format($detail['stats']['energy_kwh'], 1) }} kWh</p>
            </div>
        </div>
    </div>
@endforeach
```

**Thời gian:** 1-2 ngày

---

### 3.4 PWA Shortcuts & Web Share Target

**1. PWA Shortcut Actions:**

```json
// public/manifest.json
{
    "shortcuts": [
        {
            "name": "Thêm chi tiêu nhanh",
            "short_name": "Chi tiêu",
            "description": "Thêm giao dịch chi tiêu mới",
            "url": "/expenses/create?source=pwa",
            "icons": [{ "src": "/icons/expense-96x96.png", "sizes": "96x96" }]
        },
        {
            "name": "Xem số dư ví",
            "short_name": "Số dư",
            "description": "Xem số dư tất cả các ví",
            "url": "/wallets?source=pwa",
            "icons": [{ "src": "/icons/wallet-96x96.png", "sizes": "96x96" }]
        },
        {
            "name": "Dashboard",
            "short_name": "Dashboard",
            "description": "Xem tổng quan",
            "url": "/dashboard?source=pwa",
            "icons": [{ "src": "/icons/dashboard-96x96.png", "sizes": "96x96" }]
        }
    ]
}
```

**2. Web Share Target API:**

```json
// public/manifest.json
{
    "share_target": {
        "action": "/expenses/create?source=share",
        "method": "POST",
        "enctype": "multipart/form-data",
        "params": {
            "title": "description",
            "text": "notes",
            "files": [
                {
                    "name": "receipts",
                    "accept": ["image/*", "application/pdf"]
                }
            ]
        }
    }
}
```

Khi user share hóa đơn từ app khác (Email, Zalo, Files...) → tự động mở homeWatt với form tạo expense có sẵn ảnh.

**Thời gian:** 1 ngày

---

## Tổng kết

| # | Tính năng | Module mới | Migration | Service | Controller | Blade views | Thời gian |
|---|-----------|-----------|-----------|---------|------------|-------------|-----------|
| 1.1 | Dark Mode | Không | Không | Không | Không | ~45 files | 1-2 ngày |
| 1.2 | Notification đa kênh | Notification | 4 bảng | NotificationService + channels | 2 controllers | 3-5 views | 3-5 ngày |
| 1.3 | So sánh YoY | Không | Không | ComparisonTrait | 1 method | 2 components | 1-2 ngày |
| 1.4 | Goal Tracking | Goal | 2 bảng | GoalService + command | GoalController | 3-4 views | 3-5 ngày |
| 2.1 | Bank Import | Không | Không | BankParserFactory + parsers | ImportController | 1 view | 3-5 ngày |
| 2.2 | Predictive Maintenance | Không | Không | AnomalyDetector | Dashboard mở rộng | 1 component | 2-3 ngày |
| 2.3 | Receipt Gallery | Không | ALTER expenses | Không | ExpenseController mở rộng | 1 view | 2-3 ngày |
| 2.4 | Link mời Home | Không | 1 bảng | InvitationService | InvitationController | 2 views | 1-2 ngày |
| 3.1 | Voice Input | Không | Không | VoiceTranscriber | Webhook mở rộng | Không | 3-5 ngày |
| 3.2 | Energy Anomaly | Không | Không | Mở rộng AnomalyDetector | Command | 1 component | 2-3 ngày |
| 3.3 | Multi-home Dashboard | Không | Không | DashboardService | Dashboard mở rộng | 1 view | 1-2 ngày |
| 3.4 | PWA Shortcuts | Không | Không | Không | Không | manifest.json | 1 ngày |

**Tổng thời gian ước tính:** 6 tuần (23-38 ngày làm việc)

**Module mới cần tạo:** `Notification`, `Goal`

**Tổng migration mới:** 7 bảng + 1 ALTER

**Backward-compatible:** Tất cả tính năng đều là thêm mới, không breaking change.
