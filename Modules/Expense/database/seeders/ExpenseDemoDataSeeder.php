<?php

namespace Modules\Expense\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;

class ExpenseDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure a user exists
        $user = User::where('email', 'demo@homewatt.com')->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => 'demo@homewatt.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        // 2. Ensure a home exists
        $home = Home::where('name', 'Ngôi nhà mẫu')->first();
        if (! $home) {
            $home = Home::forceCreate([
                'owner_id' => $user->id,
                'name' => 'Ngôi nhà mẫu',
                'address' => '123 Đường Demo, TP. Hồ Chí Minh',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'currency' => 'VND',
                'status' => 'active',
            ]);

            // Add user as owner of the home
            HomeMember::create([
                'home_id' => $home->id,
                'user_id' => $user->id,
                'role' => HomeMember::ROLE_OWNER,
            ]);
        }

        // 3. Populate default categories for this home
        // This will update/create all default categories (including the newly added ones)
        $categoriesSeeder = new DefaultCategoriesSeeder();
        $categoriesSeeder->run();

        // 4. Ensure wallets exist for this home
        $cashWallet = Wallet::where('home_id', $home->id)->where('type', Wallet::TYPE_CASH)->first();
        if (! $cashWallet) {
            $cashWallet = Wallet::create([
                'home_id' => $home->id,
                'name' => 'Tiền mặt',
                'type' => Wallet::TYPE_CASH,
                'currency' => 'VND',
                'opening_balance' => 5000000,
                'balance' => 5000000,
                'icon' => '💵',
                'color' => '#10b981',
                'sort_order' => 1,
            ]);
        }

        $bankWallet = Wallet::where('home_id', $home->id)->where('type', Wallet::TYPE_BANK)->first();
        if (! $bankWallet) {
            $bankWallet = Wallet::create([
                'home_id' => $home->id,
                'name' => 'Tài khoản ngân hàng',
                'type' => Wallet::TYPE_BANK,
                'currency' => 'VND',
                'opening_balance' => 25000000,
                'balance' => 25000000,
                'icon' => '🏦',
                'color' => '#3b82f6',
                'sort_order' => 2,
            ]);
        }

        $creditWallet = Wallet::where('home_id', $home->id)->where('type', Wallet::TYPE_CREDIT_CARD)->first();
        if (! $creditWallet) {
            $creditWallet = Wallet::create([
                'home_id' => $home->id,
                'name' => 'Thẻ tín dụng',
                'type' => Wallet::TYPE_CREDIT_CARD,
                'currency' => 'VND',
                'opening_balance' => 0,
                'balance' => 0,
                'icon' => '💳',
                'color' => '#ef4444',
                'sort_order' => 3,
            ]);
        }

        // Cache category IDs for this home to avoid query overhead
        $categories = ExpenseCategory::where('home_id', $home->id)->get()->keyBy(function ($item) {
            return $item->type . '_' . $item->name;
        });

        // 5. Generate mock data for the last 90 days
        $startDate = Carbon::now()->subDays(90);
        $endDate = Carbon::now();

        // Keep track of credit card spending per month to pay it off
        $monthlyCreditSpending = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayOfMonth = $date->day;
            $dayOfWeek = $date->dayOfWeek;
            $monthKey = $date->format('Y-m');

            if (! isset($monthlyCreditSpending[$monthKey])) {
                $monthlyCreditSpending[$monthKey] = 0;
            }

            // === A. MONTHLY TRANSACTIONS ===

            // 1. Monthly Salaries (credited on the 5th)
            if ($dayOfMonth === 5) {
                $catId = $this->getCategory($categories, 'Lương cứng', ExpenseCategory::TYPE_INCOME);
                $this->createExpense($home->id, $bankWallet->id, $catId, $user->id, ExpenseCategory::TYPE_INCOME, 22000000, 'Nhận lương tháng', $date);
            }

            // 2. Freelance Job (around the 20th)
            if ($dayOfMonth === 20) {
                $catId = $this->getCategory($categories, 'Làm thêm (Freelance)', ExpenseCategory::TYPE_INCOME);
                $this->createExpense($home->id, $bankWallet->id, $catId, $user->id, ExpenseCategory::TYPE_INCOME, 6500000, 'Thanh toán dự án ngoài', $date);
            }

            // 3. Rent (1st of the month)
            if ($dayOfMonth === 1) {
                $catId = $this->getCategory($categories, 'Tiền thuê nhà', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $bankWallet->id, $catId, $user->id, ExpenseCategory::TYPE_EXPENSE, 5500000, 'Tiền nhà tháng này', $date);
            }

            // 4. Utilities: Electricity, Water, Waste (10th of the month)
            if ($dayOfMonth === 10) {
                $elecCatId = $this->getCategory($categories, 'Tiền điện', ExpenseCategory::TYPE_EXPENSE);
                $waterCatId = $this->getCategory($categories, 'Tiền nước', ExpenseCategory::TYPE_EXPENSE);
                $wasteCatId = $this->getCategory($categories, 'Tiền rác & vệ sinh', ExpenseCategory::TYPE_EXPENSE);

                $elecAmount = rand(1100000, 1750000);
                $waterAmount = rand(120000, 190000);

                $this->createExpense($home->id, $bankWallet->id, $elecCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $elecAmount, 'Hóa đơn tiền điện sinh hoạt', $date);
                $this->createExpense($home->id, $bankWallet->id, $waterCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $waterAmount, 'Hóa đơn tiền nước sinh hoạt', $date);
                $this->createExpense($home->id, $bankWallet->id, $wasteCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, 50000, 'Phí thu gom rác', $date);
            }

            // 5. Internet & TV (12th of the month)
            if ($dayOfMonth === 12) {
                $catId = $this->getCategory($categories, 'Internet & TV', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $bankWallet->id, $catId, $user->id, ExpenseCategory::TYPE_EXPENSE, 275000, 'Internet VNPT cáp quang', $date);
            }

            // 6. Gym Subscription (15th of the month)
            if ($dayOfMonth === 15) {
                $catId = $this->getCategory($categories, 'Gym & Yoga', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $creditWallet->id, $catId, $user->id, ExpenseCategory::TYPE_EXPENSE, 550000, 'Gia hạn gói tập gym', $date);
                $monthlyCreditSpending[$monthKey] += 550000;
            }

            // === B. WEEKLY TRANSACTIONS ===

            // 1. Saturdays: Supermarket, Eating Out, Cash withdrawal
            if ($dayOfWeek === Carbon::SATURDAY) {
                // Grocery shopping (Credit card)
                $groceryCatId = $this->getCategory($categories, 'Đi chợ/siêu thị', ExpenseCategory::TYPE_EXPENSE);
                $groceryAmount = rand(600000, 1100000);
                $this->createExpense($home->id, $creditWallet->id, $groceryCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $groceryAmount, 'Đi siêu thị Co.opmart mua đồ ăn tuần', $date);
                $monthlyCreditSpending[$monthKey] += $groceryAmount;

                // Restaurant dining (Credit card)
                $diningCatId = $this->getCategory($categories, 'Ăn tiệm', ExpenseCategory::TYPE_EXPENSE);
                $diningAmount = rand(350000, 750000);
                $this->createExpense($home->id, $creditWallet->id, $diningCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $diningAmount, 'Ăn uống tụ tập bạn bè cuối tuần', $date);
                $monthlyCreditSpending[$monthKey] += $diningAmount;

                // ATM Cash withdrawal every 2 weeks (Transfer bank -> cash)
                if ($dayOfMonth <= 7 || ($dayOfMonth >= 15 && $dayOfMonth <= 21)) {
                    $this->createTransfer($home->id, $bankWallet->id, $cashWallet->id, $user->id, 2000000, 'Rút tiền mặt ATM tiêu dùng', $date);
                }
            }

            // 2. Sundays: Coffee, Movies, Outing, Pet Food
            if ($dayOfWeek === Carbon::SUNDAY) {
                // Cafe (Cash)
                $cafeCatId = $this->getCategory($categories, 'Cafe', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $cashWallet->id, $cafeCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, rand(80000, 150000), 'Cafe Highland cuối tuần', $date);

                // Cinema (Credit card)
                if (rand(1, 100) < 50) {
                    $cinemaCatId = $this->getCategory($categories, 'Xem phim', ExpenseCategory::TYPE_EXPENSE);
                    $this->createExpense($home->id, $creditWallet->id, $cinemaCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, 260000, 'Xem phim rạp CGV', $date);
                    $monthlyCreditSpending[$monthKey] += 260000;
                }

                // Outing/Picnic once a month (around 25th)
                if ($dayOfMonth >= 22 && $dayOfMonth <= 28) {
                    $travelCatId = $this->getCategory($categories, 'Du lịch & dã ngoại', ExpenseCategory::TYPE_EXPENSE);
                    $travelAmount = rand(1500000, 2500000);
                    $this->createExpense($home->id, $creditWallet->id, $travelCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $travelAmount, 'Đi chơi cắm trại cuối tuần ngoại thành', $date);
                    $monthlyCreditSpending[$monthKey] += $travelAmount;
                }

                // Pet food every 3 weeks
                if ($dayOfMonth % 21 === 0) {
                    $petCatId = $this->getCategory($categories, 'Thức ăn thú cưng', ExpenseCategory::TYPE_EXPENSE);
                    $this->createExpense($home->id, $creditWallet->id, $petCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, 320000, 'Mua hạt thức ăn cho mèo', $date);
                    $monthlyCreditSpending[$monthKey] += 320000;
                }
            }

            // 3. Wednesdays: Petrol/Fuel
            if ($dayOfWeek === Carbon::WEDNESDAY) {
                $fuelCatId = $this->getCategory($categories, 'Xăng xe', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $cashWallet->id, $fuelCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, 80000, 'Đổ xăng xe máy', $date);
            }

            // === C. DAILY FREQUENT TRANSACTIONS ===

            // 1. Breakfast (Prob: 60%)
            if (rand(1, 100) < 60) {
                $breakfastCatId = $this->getCategory($categories, 'Ăn sáng', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $cashWallet->id, $breakfastCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, rand(35000, 55000), 'Ăn sáng (bún, phở, xôi)', $date);
            }

            // 2. Lunch (Prob: 80%)
            if (rand(1, 100) < 80) {
                $lunchCatId = $this->getCategory($categories, 'Ăn trưa', ExpenseCategory::TYPE_EXPENSE);
                // Sometimes cash, sometimes card
                $useWalletId = (rand(1, 100) < 30) ? $creditWallet->id : $cashWallet->id;
                $lunchAmount = rand(45000, 75000);
                $this->createExpense($home->id, $useWalletId, $lunchCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $lunchAmount, 'Cơm trưa văn phòng', $date);
                if ($useWalletId === $creditWallet->id) {
                    $monthlyCreditSpending[$monthKey] += $lunchAmount;
                }
            }

            // 3. Coffee (Prob: 40%)
            if (rand(1, 100) < 40) {
                $cafeCatId = $this->getCategory($categories, 'Cafe', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $cashWallet->id, $cafeCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, rand(30000, 60000), 'Mua cafe mang đi làm', $date);
            }

            // 4. Dinner (Prob: 40%)
            if (rand(1, 100) < 40) {
                $dinnerCatId = $this->getCategory($categories, 'Ăn tối', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $cashWallet->id, $dinnerCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, rand(60000, 120000), 'Mua đồ ăn tối mang về', $date);
            }

            // === D. OCCASIONAL AND RANDOM TRANSACTIONS ===

            // 1. Clothes Shopping
            if ($dayOfMonth === 18 && rand(1, 100) < 50) {
                $clothesCatId = $this->getCategory($categories, 'Quần áo', ExpenseCategory::TYPE_EXPENSE);
                $shopAmount = rand(350000, 850000);
                $this->createExpense($home->id, $creditWallet->id, $clothesCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $shopAmount, 'Mua quần áo thời trang mới', $date);
                $monthlyCreditSpending[$monthKey] += $shopAmount;
            }

            // 2. Buy Self-development book
            if ($dayOfMonth === 22 && rand(1, 100) < 60) {
                $bookCatId = $this->getCategory($categories, 'Sách & Tài liệu', ExpenseCategory::TYPE_EXPENSE);
                $bookAmount = rand(120000, 240000);
                $this->createExpense($home->id, $creditWallet->id, $bookCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $bookAmount, 'Mua sách giấy phát triển bản thân', $date);
                $monthlyCreditSpending[$monthKey] += $bookAmount;
            }

            // 3. Health check/Medicine
            if ($dayOfMonth === 14 && rand(1, 100) < 25) {
                $medsCatId = $this->getCategory($categories, 'Thuốc men', ExpenseCategory::TYPE_EXPENSE);
                $this->createExpense($home->id, $cashWallet->id, $medsCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, rand(120000, 280000), 'Mua thuốc bổ và vitamin', $date);
            }

            // 4. Baby Diapers/Milk
            if ($dayOfMonth === 8 && rand(1, 100) < 70) {
                $babyCatId = $this->getCategory($categories, 'Bỉm sữa & Ăn dặm', ExpenseCategory::TYPE_EXPENSE);
                $babyAmount = rand(450000, 780000);
                $this->createExpense($home->id, $creditWallet->id, $babyCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $babyAmount, 'Mua sữa bột và tã cho con', $date);
                $monthlyCreditSpending[$monthKey] += $babyAmount;
            }

            // 5. Gifts
            if ($dayOfMonth === 27 && rand(1, 100) < 30) {
                $giftCatId = $this->getCategory($categories, 'Quà sinh nhật & Kỷ niệm', ExpenseCategory::TYPE_EXPENSE);
                if (! $giftCatId) {
                    $giftCatId = $this->getCategory($categories, 'Khác', ExpenseCategory::TYPE_EXPENSE);
                }
                $giftAmount = rand(400000, 800000);
                $this->createExpense($home->id, $creditWallet->id, $giftCatId, $user->id, ExpenseCategory::TYPE_EXPENSE, $giftAmount, 'Mua quà sinh nhật bạn thân', $date);
                $monthlyCreditSpending[$monthKey] += $giftAmount;
            }

            // 6. Cashback/Refunds (Income)
            if ($dayOfMonth === 28 && rand(1, 100) < 50) {
                $refundCatId = $this->getCategory($categories, 'Hoàn tiền & Cashback', ExpenseCategory::TYPE_INCOME);
                $this->createExpense($home->id, $creditWallet->id, $refundCatId, $user->id, ExpenseCategory::TYPE_INCOME, rand(45000, 160000), 'Hoàn tiền chi tiêu thẻ tín dụng', $date);
            }

            // === E. CREDIT CARD PAYOFF (Transfer at month end) ===
            if ($dayOfMonth === 28) {
                $amountToPay = $monthlyCreditSpending[$monthKey] ?? 0;
                if ($amountToPay > 0) {
                    $this->createTransfer($home->id, $bankWallet->id, $creditWallet->id, $user->id, $amountToPay, 'Thanh toán số dư thẻ tín dụng tháng này', $date);
                    $monthlyCreditSpending[$monthKey] = 0;
                }
            }
        }

        // 6. Refresh balances for all wallets
        $cashWallet->refreshBalance();
        $bankWallet->refreshBalance();
        $creditWallet->refreshBalance();
    }

    private function createExpense($homeId, $walletId, $categoryId, $userId, $type, $amount, $description, $occurredAt)
    {
        return Expense::create([
            'home_id' => $homeId,
            'wallet_id' => $walletId,
            'category_id' => $categoryId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'VND',
            'description' => $description,
            'occurred_at' => $occurredAt,
        ]);
    }

    private function createTransfer($homeId, $fromWalletId, $toWalletId, $userId, $amount, $description, $occurredAt)
    {
        $transfer = Transfer::create([
            'home_id' => $homeId,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'user_id' => $userId,
            'amount' => $amount,
            'fee' => 0,
            'currency' => 'VND',
            'description' => $description,
            'occurred_at' => $occurredAt,
        ]);

        $outCategory = $this->getOrCreateTransferCategory($homeId, Expense::TYPE_EXPENSE);
        $inCategory = $this->getOrCreateTransferCategory($homeId, Expense::TYPE_INCOME);

        Expense::create([
            'home_id' => $homeId,
            'wallet_id' => $fromWalletId,
            'category_id' => $outCategory->id,
            'user_id' => $userId,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => $amount,
            'currency' => 'VND',
            'description' => $description,
            'occurred_at' => $occurredAt,
            'transfer_id' => $transfer->id,
        ]);

        Expense::create([
            'home_id' => $homeId,
            'wallet_id' => $toWalletId,
            'category_id' => $inCategory->id,
            'user_id' => $userId,
            'type' => Expense::TYPE_INCOME,
            'amount' => $amount,
            'currency' => 'VND',
            'description' => $description,
            'occurred_at' => $occurredAt,
            'transfer_id' => $transfer->id,
        ]);

        return $transfer;
    }

    private function getOrCreateTransferCategory(int $homeId, string $type)
    {
        $name = $type === Expense::TYPE_INCOME ? 'Chuyển tiền vào' : 'Chuyển tiền ra';

        return ExpenseCategory::firstOrCreate(
            ['home_id' => $homeId, 'name' => $name, 'type' => $type],
            [
                'category_group' => ExpenseCategory::GROUP_TRANSFER,
                'icon' => $type === Expense::TYPE_INCOME ? '⬇️' : '⬆️',
                'color' => '#6b7280',
                'is_system' => true,
            ]
        );
    }

    private function getCategory($categories, $name, $type)
    {
        $key = $type . '_' . $name;
        if ($categories->has($key)) {
            return $categories->get($key)->id;
        }

        $dbCat = ExpenseCategory::where('name', $name)->where('type', $type)->first();
        if ($dbCat) {
            return $dbCat->id;
        }

        $fallback = ExpenseCategory::where('type', $type)->first();
        return $fallback ? $fallback->id : null;
    }
}
