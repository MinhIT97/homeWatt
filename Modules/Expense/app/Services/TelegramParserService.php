<?php

namespace Modules\Expense\Services;

use Modules\Expense\Models\ExpenseCategory;

class TelegramParserService
{
    /**
     * Parse a Telegram message into structured transaction data.
     *
     * @param string $text
     * @param int $homeId
     * @return array|null
     */
    public function parse(string $text, int $homeId): ?array
    {
        $text = trim($text);
        if (empty($text)) {
            return null;
        }

        $lowercaseText = mb_strtolower($text, 'UTF-8');

        // 1. Determine Type and Category based on prefix / keywords
        $type = 'expense'; // default
        $categoryGroup = null;
        $categoryName = 'Khác'; // default
        $cleanText = $lowercaseText;

        // Pattern matching for Lending / Borrowing/Transfer first since they are multi-word
        if ($this->hasPrefix($lowercaseText, ['chuyen khoan', 'chuyển khoản', 'chuyen tien', 'chuyển tiền', 'chuyen', 'chuyển', 'ck', 'transfer'])) {
            $type = 'transfer';
            $categoryName = 'Chuyển tiền';
            $cleanText = $this->removePrefix($lowercaseText, ['chuyen khoan', 'chuyển khoản', 'chuyen tien', 'chuyển tiền', 'chuyen', 'chuyển', 'ck', 'transfer']);
        } elseif ($this->hasPrefix($lowercaseText, ['cho vay', 'cho muon', 'cho mượn'])) {
            $type = 'expense';
            $categoryGroup = ExpenseCategory::GROUP_LENDING;
            $categoryName = 'Cho vay';
            $cleanText = $this->removePrefix($lowercaseText, ['cho vay', 'cho muon', 'cho mượn']);
        } elseif ($this->hasPrefix($lowercaseText, ['thu no', 'thu nợ', 'đòi nợ', 'doi no'])) {
            $type = 'income';
            $categoryGroup = ExpenseCategory::GROUP_DEBT_COLLECTION;
            $categoryName = 'Thu nợ';
            $cleanText = $this->removePrefix($lowercaseText, ['thu no', 'thu nợ', 'đòi nợ', 'doi no']);
        } elseif ($this->hasPrefix($lowercaseText, ['tra no', 'trả nợ', 'trả tiền', 'tra tien'])) {
            $type = 'expense';
            $categoryGroup = ExpenseCategory::GROUP_DEBT_REPAYMENT;
            $categoryName = 'Trả nợ';
            $cleanText = $this->removePrefix($lowercaseText, ['tra no', 'trả nợ', 'trả tiền', 'tra tien']);
        } elseif ($this->hasPrefix($lowercaseText, ['di vay', 'đi vay', 'vay tiền', 'vay tien', 'vay', 'mượn', 'muon'])) {
            $type = 'income';
            $categoryGroup = ExpenseCategory::GROUP_BORROWING;
            $categoryName = 'Đi vay';
            $cleanText = $this->removePrefix($lowercaseText, ['di vay', 'đi vay', 'vay tiền', 'vay tien', 'vay', 'mượn', 'muon']);
        } elseif ($this->hasPrefix($lowercaseText, ['chi', 'tieu', 'tiêu', 'mua', 'pay', 'out'])) {
            $type = 'expense';
            $cleanText = $this->removePrefix($lowercaseText, ['chi', 'tieu', 'tiêu', 'mua', 'pay', 'out']);
        } elseif ($this->hasPrefix($lowercaseText, ['thu', 'nhan', 'nhận', 'luong', 'lương', 'in'])) {
            $type = 'income';
            $cleanText = $this->removePrefix($lowercaseText, ['thu', 'nhan', 'nhận', 'luong', 'lương', 'in']);
        }

        // 2. Extract Amount (e.g. 50k, 1.5m, 2tr, 500000, 50.000)
        // Find numbers with units: k, m, tr, triệu, k, c
        // Regex: \d+([.,]\d+)?\s*(k|m|tr|triệu|trieu)?
        preg_match('/(\d+(?:[.,]\d+)?)\s*(k|m|tr|triệu|trieu)?/i', $cleanText, $matches);

        if (empty($matches)) {
            return null; // amount is required
        }

        $rawNumber = str_replace(',', '.', $matches[1]);
        $amount = (float) $rawNumber;
        $unit = isset($matches[2]) ? strtolower($matches[2]) : '';

        // Apply multipliers
        if ($unit === 'k') {
            $amount *= 1000;
        } elseif (in_array($unit, ['m', 'tr', 'triệu', 'trieu'])) {
            $amount *= 1000000;
        }

        // Remove the matched amount from the text to get description
        $description = trim(str_replace($matches[0], '', $cleanText));
        
        // Clean up description prefix/suffix characters
        $description = trim($description, " -:,=");

        // 3. Match Category if it hasn't been explicitly matched above
        if ($categoryName === 'Khác') {
            $categoryName = $this->autoMatchCategory($description, $type);
        }

        // Find category model in DB
        if ($categoryGroup) {
            $category = ExpenseCategory::where('home_id', $homeId)
                ->where('category_group', $categoryGroup)
                ->first();
        } else {
            $category = ExpenseCategory::where('home_id', $homeId)
                ->where('type', $type)
                ->where('name', $categoryName)
                ->first();
        }

        // Fallback to general "Khác" if not found
        if (!$category) {
            $category = ExpenseCategory::where('home_id', $homeId)
                ->where('category_group', ExpenseCategory::GROUP_OTHER)
                ->first();
        }

        if (!$category) {
            throw new \RuntimeException(__('expense.default_category_not_found'));
        }

        // Original casing for description if possible
        $originalDesc = $text;
        // Strip out command prefix from original casing
        foreach (['chuyen khoan', 'chuyển khoản', 'chuyen tien', 'chuyển tiền', 'chuyen', 'chuyển', 'ck', 'transfer', 'cho vay', 'cho muon', 'cho mượn', 'thu no', 'thu nợ', 'đòi nợ', 'doi no', 'tra no', 'trả nợ', 'trả tiền', 'tra tien', 'di vay', 'đi vay', 'vay tiền', 'vay tien', 'vay', 'mượn', 'muon', 'chi', 'tieu', 'tiêu', 'mua', 'pay', 'out', 'thu', 'nhan', 'nhận', 'luong', 'lương', 'in'] as $prefix) {
            if (str_starts_with(mb_strtolower($originalDesc, 'UTF-8'), $prefix)) {
                $originalDesc = trim(mb_substr($originalDesc, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8'));
                break;
            }
        }
        // Strip out amount match
        preg_match('/(\d+(?:[.,]\d+)?)\s*(k|m|tr|triệu|trieu)?/i', $originalDesc, $origAmountMatches);
        if (!empty($origAmountMatches)) {
            $originalDesc = trim(str_replace($origAmountMatches[0], '', $originalDesc));
        }

        // For transfers, strip prepositions and wallet candidates to make description clean
        if ($type === 'transfer') {
            $wallets = \Modules\Wallet\Models\Wallet::where('home_id', $homeId)
                ->where('is_archived', false)
                ->get();
            $candidates = ['từ', 'tu', 'sang', 'đến', 'den', 'qua', 'vào', 'vao', '->'];
            foreach ($wallets as $w) {
                $walletNameLower = mb_strtolower($w->name, 'UTF-8');
                $walletNameNoSpaces = str_replace(' ', '', $walletNameLower);
                $candidates[] = $w->name;
                $candidates[] = $walletNameLower;
                $candidates[] = $walletNameNoSpaces;
                $candidates[] = 'tài khoản ' . $walletNameLower;
                $candidates[] = 'tài khoản ' . $walletNameNoSpaces;
                $candidates[] = 'taikhoan ' . $walletNameLower;
                $candidates[] = 'taikhoan ' . $walletNameNoSpaces;
                $candidates[] = 'tk ' . $walletNameLower;
                $candidates[] = 'tk ' . $walletNameNoSpaces;
                $candidates[] = 'ví ' . $walletNameLower;
                $candidates[] = 'ví ' . $walletNameNoSpaces;
                $candidates[] = 'vi ' . $walletNameLower;
                $candidates[] = 'vi ' . $walletNameNoSpaces;

                if (str_contains($walletNameLower, 'techcombank')) {
                    $candidates[] = 'tech';
                    $candidates[] = 'tcb';
                    $candidates[] = 'ví thấu chi tech';
                    $candidates[] = 'ví thấu chi techcombank';
                }
                if (str_contains($walletNameLower, 'vietcombank')) {
                    $candidates[] = 'vcb';
                }
                if (str_contains($walletNameLower, 'momo')) {
                    $candidates[] = 'momo';
                }
                if (str_contains($walletNameLower, 'tiền mặt') || str_contains($walletNameLower, 'tien mat')) {
                    $candidates[] = 'tien mat';
                    $candidates[] = 'tiền mặt';
                    $candidates[] = 'tm';
                }
                if (str_contains($walletNameLower, 'vpbank') || str_contains($walletNameLower, 'vp bank')) {
                    $candidates[] = 'vpbank';
                    $candidates[] = 'vp bank';
                    $candidates[] = 'vp';
                }
            }

            // Sort by length descending to replace longer candidates first
            $candidates = array_values(array_unique(array_filter($candidates)));
            usort($candidates, fn($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

            foreach ($candidates as $cand) {
                // Use case-insensitive replacement
                $originalDesc = preg_replace('/\b' . preg_quote($cand, '/') . '\b/iu', '', $originalDesc);
                $originalDesc = str_ireplace($cand, '', $originalDesc);
            }
        }

        $originalDesc = trim($originalDesc, " -:,=");
        
        if (empty($originalDesc)) {
            $originalDesc = $categoryName;
        }

        return [
            'type' => $type,
            'amount' => $amount,
            'category_id' => $category?->id,
            'category_name' => $category?->name ?? $categoryName,
            'description' => mb_convert_case($originalDesc, MB_CASE_TITLE, "UTF-8"),
        ];
    }

    private function hasPrefix(string $text, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($text, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function removePrefix(string $text, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($text, $prefix)) {
                return trim(mb_substr($text, mb_strlen($prefix)));
            }
        }
        return $text;
    }

    private function autoMatchCategory(string $desc, string $type): string
    {
        if ($type === 'income') {
            $keywords = [
                'Lương' => ['lương', 'luong', 'salary', 'salary'],
                'Thưởng' => ['thưởng', 'thuong', 'bonus'],
                'Quà tặng' => ['quà', 'qua', 'gift'],
                'Bán đồ' => ['bán', 'ban', ' thanh lý', 'thanh ly'],
                'Đi vay' => ['vay', 'mượn', 'borrow'],
                'Thu nợ' => ['nợ', 'no', 'đòi', 'doi'],
            ];
        } else {
            $keywords = [
                'Ăn uống' => ['ăn', 'uống', 'an', 'uong', 'sang', 'trua', 'toi', 'cafe', 'phở', 'bún', 'cơm', 'com', 'nước', 'nuoc', 'lẩu', 'lau', 'nhậu', 'bia', 'sữa', 'sua'],
                'Đi lại' => ['xe', 'xăng', 'xang', 'taxi', 'grab', 'bus', 'vé', 've', 'gửi', 'gui', 'sửa xe', 'sua xe'],
                'Nhà cửa' => ['nhà', 'nha', 'điện', 'dien', 'nước', 'nuoc', 'internet', 'wifi', 'thuê', 'thue', 'rác', 'rac', 'phòng', 'phong'],
                'Hóa đơn' => ['hóa đơn', 'hoa don', 'cước', 'cuoc', 'điện thoại', 'dien thoai', 'nạp tiền', 'nap tien', '3g', '4g'],
                'Mua sắm' => ['mua sắm', 'mua sam', 'quần', 'quan', 'áo', 'ao', 'giày', 'giay', 'dép', 'dep', 'shopee', 'lazada', 'tiki', 'mỹ phẩm', 'my pham'],
                'Giải trí' => ['phim', 'rạp', 'rap', 'netflix', 'spotify', 'chơi', 'choi', 'game', 'du lịch', 'du lich', 'karaoke'],
                'Sức khỏe' => ['thuốc', 'thuoc', 'khám', 'kham', 'bệnh viện', 'benh vien', 'bác sĩ', 'bac si', 'gym', 'răng', 'rang'],
                'Giáo dục' => ['học', 'hoc', 'sách', 'sach', 'vở', 'vo', 'bút', 'but', 'học phí', 'hoc phi', 'khóa học', 'khoa hoc'],
                'Cho vay' => ['vay', 'mượn', 'muon', 'lend'],
                'Trả nợ' => ['nợ', 'no', 'trả', 'tra'],
            ];
        }

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($desc, $word)) {
                    return $category;
                }
            }
        }

        return 'Khác';
    }
}
