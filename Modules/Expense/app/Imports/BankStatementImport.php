<?php

namespace Modules\Expense\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Expense\Imports\Parsers\BankParser;
use Modules\Expense\Imports\Parsers\MomoParser;
use Modules\Expense\Imports\Parsers\TechcombankParser;
use Modules\Expense\Imports\Parsers\VcbParser;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Services\ExpenseService;
use Modules\Wallet\Models\Wallet;

class BankStatementImport
{
    /**
     * @var BankParser[]
     */
    private array $parsers;

    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {
        $this->parsers = [
            new VcbParser,
            new TechcombankParser,
            new MomoParser,
        ];
    }

    /**
     * Import transactions from a CSV file.
     *
     * @return array{success: int, errors: array<int, array{row: int, message: string}>, transactions: array<int, array<string, mixed>>}
     */
    public function import(string $filePath, int $homeId, User $user): array
    {
        $rows = $this->readCsv($filePath);

        if (empty($rows)) {
            return ['success' => 0, 'errors' => [], 'transactions' => []];
        }

        $headers = array_keys($rows[0]);

        // Detect parser
        $parser = $this->detectParser($headers);

        if ($parser === null) {
            return [
                'success' => 0,
                'errors' => [['row' => 0, 'message' => 'Không thể nhận diện định dạng file. Các định dạng hỗ trợ: VCB, Techcombank, Momo.']],
                'transactions' => [],
            ];
        }

        // Parse rows
        $transactions = $parser->parse($rows);

        if (empty($transactions)) {
            return ['success' => 0, 'errors' => [], 'transactions' => []];
        }

        // Get wallets and categories for matching
        $wallets = Wallet::where('home_id', $homeId)
            ->where('is_archived', false)
            ->get();

        $categories = ExpenseCategory::where('home_id', $homeId)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        $success = 0;
        $errors = [];

        foreach ($transactions as $index => $transaction) {
            try {
                $wallet = $this->matchWallet($transaction['description'], $wallets);
                $category = $this->matchCategory($transaction['description'], $transaction['type'], $categories);

                $payload = [
                    'home_id' => $homeId,
                    'wallet_id' => $wallet->id,
                    'category_id' => $category->id,
                    'amount' => $transaction['amount'],
                    'type' => $transaction['type'],
                    'description' => $transaction['description'],
                    'notes' => $transaction['reference'] ? 'Ref: '.$transaction['reference'] : null,
                    'occurred_at' => $transaction['date'].' '.now()->format('H:i:s'),
                ];

                $this->expenseService->createExpense($payload, $user);
                $success++;
            } catch (\Throwable $e) {
                Log::error('Bank statement import row failed', [
                    'row' => $index + 1,
                    'transaction' => $transaction,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = [
                    'row' => $index + 1,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $success,
            'errors' => $errors,
            'transactions' => $transactions,
        ];
    }

    /**
     * Preview parsed transactions from a CSV file without importing.
     *
     * @return array{parser: string, transactions: array<int, array<string, mixed>>, errors: array<int, array{row: int, message: string}>}
     */
    public function preview(string $filePath, int $homeId): array
    {
        $rows = $this->readCsv($filePath);

        if (empty($rows)) {
            return ['parser' => 'unknown', 'transactions' => [], 'errors' => []];
        }

        $headers = array_keys($rows[0]);
        $parser = $this->detectParser($headers);

        if ($parser === null) {
            return [
                'parser' => 'unknown',
                'transactions' => [],
                'errors' => [['row' => 0, 'message' => 'Không thể nhận diện định dạng file.']],
            ];
        }

        $transactions = $parser->parse($rows);

        $parserName = match (true) {
            $parser instanceof VcbParser => 'VCB',
            $parser instanceof TechcombankParser => 'Techcombank',
            $parser instanceof MomoParser => 'Momo',
            default => 'Unknown',
        };

        // Attach wallet and category suggestions
        $wallets = Wallet::where('home_id', $homeId)
            ->where('is_archived', false)
            ->get();

        $categories = ExpenseCategory::where('home_id', $homeId)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        foreach ($transactions as &$transaction) {
            $transaction['suggested_wallet_id'] = $this->matchWallet($transaction['description'], $wallets)?->id;
            $transaction['suggested_wallet_name'] = $this->matchWallet($transaction['description'], $wallets)?->name;
            $transaction['suggested_category_id'] = $this->matchCategory($transaction['description'], $transaction['type'], $categories)?->id;
            $transaction['suggested_category_name'] = $this->matchCategory($transaction['description'], $transaction['type'], $categories)?->name;
        }
        unset($transaction);

        return [
            'parser' => $parserName,
            'transactions' => $transactions,
            'errors' => [],
        ];
    }

    /**
     * Read a CSV file and return rows as associative arrays.
     */
    private function readCsv(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return [];
        }

        // Read BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map('trim', $headers);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= count($headers)) {
                $row = array_combine($headers, array_map('trim', $data));
                if ($row !== false) {
                    $rows[] = $row;
                }
            } elseif (count($data) > 0 && count($data) <= count($headers)) {
                // Pad shorter rows
                $padded = array_pad($data, count($headers), '');
                $row = array_combine($headers, array_map('trim', $padded));
                if ($row !== false) {
                    $rows[] = $row;
                }
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Detect which parser to use based on CSV headers.
     */
    private function detectParser(array $headers): ?BankParser
    {
        foreach ($this->parsers as $parser) {
            if ($parser->detect($headers)) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * Match a wallet by name similarity to the transaction description.
     */
    private function matchWallet(string $description, $wallets): ?Wallet
    {
        $descLower = mb_strtolower($description, 'UTF-8');
        $best = null;
        $bestLen = 0;

        foreach ($wallets as $wallet) {
            $walletNameLower = mb_strtolower($wallet->name, 'UTF-8');
            $walletNameNoSpaces = str_replace(' ', '', $walletNameLower);

            // Direct match
            if (str_contains($descLower, $walletNameLower) || str_contains($descLower, $walletNameNoSpaces)) {
                if (mb_strlen($wallet->name, 'UTF-8') > $bestLen) {
                    $best = $wallet;
                    $bestLen = mb_strlen($wallet->name, 'UTF-8');
                }
            }

            // Abbreviation matching
            $abbreviations = $this->walletAbbreviations($walletNameLower);
            foreach ($abbreviations as $abbr) {
                if (str_contains($descLower, $abbr) && mb_strlen($abbr, 'UTF-8') > $bestLen) {
                    $best = $wallet;
                    $bestLen = mb_strlen($abbr, 'UTF-8');
                }
            }
        }

        // Default to first cash wallet or first wallet
        return $best
            ?? $wallets->first(fn (Wallet $w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'tiền mặt'))
            ?? $wallets->first();
    }

    /**
     * Get known abbreviations for common wallet names.
     */
    private function walletAbbreviations(string $walletName): array
    {
        $abbrs = [];

        if (str_contains($walletName, 'vietcombank')) {
            $abbrs[] = 'vcb';
        }
        if (str_contains($walletName, 'techcombank')) {
            $abbrs[] = 'techcombank';
            $abbrs[] = 'tech';
            $abbrs[] = 'tcb';
        }
        if (str_contains($walletName, 'momo')) {
            $abbrs[] = 'momo';
        }
        if (str_contains($walletName, 'tiền mặt') || str_contains($walletName, 'tien mat')) {
            $abbrs[] = 'tien mat';
            $abbrs[] = 'tiền mặt';
            $abbrs[] = 'tm';
        }
        if (str_contains($walletName, 'vpbank') || str_contains($walletName, 'vp bank')) {
            $abbrs[] = 'vpbank';
            $abbrs[] = 'vp bank';
            $abbrs[] = 'vp';
        }

        return $abbrs;
    }

    /**
     * Match a category by keyword matching in the transaction description and type.
     */
    private function matchCategory(string $description, string $type, $categories): ?ExpenseCategory
    {
        $descLower = mb_strtolower($description, 'UTF-8');
        $best = null;
        $bestScore = 0;

        $typeCategories = $categories->where('type', $type);

        $keywordMap = $this->categoryKeywords();

        foreach ($typeCategories as $category) {
            $catNameLower = mb_strtolower($category->name, 'UTF-8');
            $score = 0;

            // Direct name match
            if (str_contains($descLower, $catNameLower)) {
                $score += 10;
            }

            // Keyword matching
            foreach ($keywordMap as $keyword => $catNames) {
                if (str_contains($descLower, $keyword)) {
                    foreach ($catNames as $catName) {
                        if ($catNameLower === mb_strtolower($catName, 'UTF-8')) {
                            $score += 5;
                        }
                    }
                }
            }

            if ($score > $bestScore) {
                $best = $category;
                $bestScore = $score;
            }
        }

        // Default to first category of matching type
        return $best ?? $typeCategories->first();
    }

    /**
     * Keyword-to-category mapping for auto-detection.
     */
    private function categoryKeywords(): array
    {
        return [
            'ăn' => ['Ăn uống', 'Thực phẩm'],
            'uống' => ['Ăn uống', 'Thực phẩm'],
            'cafe' => ['Ăn uống', 'Cà phê', 'Giải trí'],
            'cà phê' => ['Ăn uống', 'Cà phê', 'Giải trí'],
            'nhà hàng' => ['Ăn uống', 'Nhà hàng'],
            'siêu thị' => ['Mua sắm', 'Siêu thị'],
            'mua sắm' => ['Mua sắm'],
            'điện' => ['Hóa đơn', 'Điện', 'Sinh hoạt'],
            'nước' => ['Hóa đơn', 'Nước', 'Sinh hoạt'],
            'internet' => ['Hóa đơn', 'Internet', 'Viễn thông'],
            'xăng' => ['Xăng xe', 'Di chuyển'],
            'dầu' => ['Xăng xe', 'Di chuyển'],
            'xe' => ['Xăng xe', 'Di chuyển'],
            'grab' => ['Di chuyển', 'Xăng xe'],
            'taxi' => ['Di chuyển'],
            'lương' => ['Lương', 'Thu nhập'],
            'thưởng' => ['Lương', 'Thu nhập'],
            'bán' => ['Thu nhập khác', 'Bán hàng'],
            'chuyển khoản' => ['Chuyển tiền'],
            'rút' => ['Rút tiền'],
            'phí' => ['Phí', 'Phí dịch vụ'],
            'bảo hiểm' => ['Bảo hiểm'],
            'học' => ['Giáo dục', 'Học phí'],
            'thuốc' => ['Y tế', 'Sức khỏe'],
            'bệnh' => ['Y tế', 'Sức khỏe'],
            'quần áo' => ['Mua sắm', 'Thời trang'],
            'giải trí' => ['Giải trí'],
            'du lịch' => ['Du lịch'],
            'điện thoại' => ['Viễn thông', 'Điện thoại'],
            'netflix' => ['Giải trí', 'Dịch vụ'],
            'spotify' => ['Giải trí', 'Dịch vụ'],
            'youtube' => ['Giải trí', 'Dịch vụ'],
        ];
    }
}
