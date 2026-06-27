<?php

namespace Modules\Expense\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;

class DefaultCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $expenseCategories = [
            [
                'name' => 'Ăn uống', 'icon' => '🍜', 'color' => '#f97316',
                'children' => [
                    ['name' => 'Ăn sáng', 'icon' => '🥞'],
                    ['name' => 'Ăn trưa', 'icon' => '🍜'],
                    ['name' => 'Ăn tối', 'icon' => '🥩'],
                    ['name' => 'Ăn tiệm', 'icon' => '🍽️'],
                    ['name' => 'Cafe', 'icon' => '☕'],
                    ['name' => 'Trà sữa', 'icon' => '🧋'],
                    ['name' => 'Đi chợ/siêu thị', 'icon' => '🛒'],
                    ['name' => 'Nước ngọt', 'icon' => '🥤'],
                    ['name' => 'Thực phẩm bổ sung', 'icon' => '💊'],
                ]
            ],
            [
                'name' => 'Đi lại', 'icon' => '🚗', 'color' => '#3b82f6',
                'children' => [
                    ['name' => 'Xăng xe', 'icon' => '⛽'],
                    ['name' => 'Taxi/thuê xe', 'icon' => '🚕'],
                    ['name' => 'Gửi xe', 'icon' => '🅿️'],
                    ['name' => 'Rửa xe', 'icon' => '🧼'],
                    ['name' => 'Sửa xe/bảo dưỡng', 'icon' => '🛠️'],
                    ['name' => 'Vé xe/tàu/máy bay', 'icon' => '🎫'],
                    ['name' => 'Bảo hiểm xe', 'icon' => '🛡️'],
                ]
            ],
            [
                'name' => 'Nhà cửa', 'icon' => '🏠', 'color' => '#8b5cf6',
                'children' => [
                    ['name' => 'Tiền thuê nhà', 'icon' => '🔑'],
                    ['name' => 'Tiền điện', 'icon' => '⚡'],
                    ['name' => 'Tiền nước', 'icon' => '💧'],
                    ['name' => 'Tiền rác & vệ sinh', 'icon' => '🗑️'],
                    ['name' => 'Tiền gas', 'icon' => '🔥'],
                    ['name' => 'Internet & TV', 'icon' => '🌐'],
                    ['name' => 'Đồ dùng gia đình', 'icon' => '🛋️'],
                    ['name' => 'Sửa chữa nhà', 'icon' => '🔨'],
                ]
            ],
            [
                'name' => 'Hóa đơn', 'icon' => '📄', 'color' => '#ef4444',
                'children' => [
                    ['name' => 'Điện thoại/3G/4G', 'icon' => '📱'],
                    ['name' => 'Phí dịch vụ chung cư', 'icon' => '🏢'],
                    ['name' => 'Gia hạn dịch vụ khác', 'icon' => '💳'],
                ]
            ],
            [
                'name' => 'Mua sắm', 'icon' => '🛍️', 'color' => '#ec4899',
                'children' => [
                    ['name' => 'Quần áo', 'icon' => '👕'],
                    ['name' => 'Giày dép', 'icon' => '👟'],
                    ['name' => 'Mỹ phẩm & làm đẹp', 'icon' => '💄'],
                    ['name' => 'Thiết bị điện tử', 'icon' => '💻'],
                    ['name' => 'Sách truyện', 'icon' => '📚'],
                ]
            ],
            [
                'name' => 'Giải trí', 'icon' => '🎮', 'color' => '#06b6d4',
                'children' => [
                    ['name' => 'Xem phim', 'icon' => '🎬'],
                    ['name' => 'Du lịch & dã ngoại', 'icon' => '✈️'],
                    ['name' => 'Chơi game', 'icon' => '🎮'],
                    ['name' => 'Đồ chơi/sở thích', 'icon' => '🧩'],
                ]
            ],
            [
                'name' => 'Sức khỏe', 'icon' => '🏥', 'color' => '#10b981',
                'children' => [
                    ['name' => 'Thuốc men', 'icon' => '💊'],
                    ['name' => 'Khám sức khỏe', 'icon' => '🏥'],
                    ['name' => 'Bảo hiểm y tế', 'icon' => '🛡️'],
                    ['name' => 'Gym & Yoga', 'icon' => '🏋️'],
                ]
            ],
            [
                'name' => 'Giáo dục', 'icon' => '📚', 'color' => '#6366f1',
                'children' => [
                    ['name' => 'Học phí', 'icon' => '🏫'],
                    ['name' => 'Khóa học ngoài', 'icon' => '📝'],
                    ['name' => 'Đồ dùng học tập', 'icon' => '✏️'],
                ]
            ],
            [
                'name' => 'Cho vay', 'icon' => '🤝', 'color' => '#eab308', 'category_group' => 'lending',
                'children' => [
                    ['name' => 'Cho bạn bè vay', 'icon' => '🤝', 'category_group' => 'lending'],
                    ['name' => 'Cho người thân mượn', 'icon' => '🏠', 'category_group' => 'lending'],
                ]
            ],
            [
                'name' => 'Trả nợ', 'icon' => '💸', 'color' => '#a855f7', 'category_group' => 'debt_repayment',
                'children' => [
                    ['name' => 'Trả nợ ngân hàng', 'icon' => '🏦', 'category_group' => 'debt_repayment'],
                    ['name' => 'Trả nợ bạn bè', 'icon' => '💸', 'category_group' => 'debt_repayment'],
                ]
            ],
            [
                'name' => 'Khác', 'icon' => '📝', 'color' => '#64748b'
            ],
        ];

        $incomeCategories = [
            [
                'name' => 'Lương', 'icon' => '💼', 'color' => '#10b981',
                'children' => [
                    ['name' => 'Lương cứng', 'icon' => '💵'],
                    ['name' => 'Làm thêm (Freelance)', 'icon' => '💻'],
                ]
            ],
            [
                'name' => 'Thưởng', 'icon' => '🎁', 'color' => '#22c55e',
                'children' => [
                    ['name' => 'Thưởng dự án', 'icon' => '🚀'],
                    ['name' => 'Thưởng tết/lễ', 'icon' => '🧧'],
                ]
            ],
            [
                'name' => 'Quà tặng', 'icon' => '🎉', 'color' => '#f59e0b'
            ],
            [
                'name' => 'Bán đồ', 'icon' => '🏷️', 'color' => '#06b6d4',
                'children' => [
                    ['name' => 'Thanh lý đồ cũ', 'icon' => '📦'],
                    ['name' => 'Bán sản phẩm tự làm', 'icon' => '🎨'],
                ]
            ],
            [
                'name' => 'Đi vay', 'icon' => '🏦', 'color' => '#0284c7', 'category_group' => 'borrowing',
                'children' => [
                    ['name' => 'Vay bạn bè', 'icon' => '🏦', 'category_group' => 'borrowing'],
                    ['name' => 'Vay ngân hàng', 'icon' => '🏛️', 'category_group' => 'borrowing'],
                ]
            ],
            [
                'name' => 'Thu nợ', 'icon' => '🪙', 'color' => '#14b8a6', 'category_group' => 'debt_collection',
                'children' => [
                    ['name' => 'Thu nợ bạn bè', 'icon' => '🪙', 'category_group' => 'debt_collection'],
                    ['name' => 'Thu nợ người thân', 'icon' => '🏡', 'category_group' => 'debt_collection'],
                ]
            ],
            [
                'name' => 'Thu nhập khác', 'icon' => '💰', 'color' => '#64748b'
            ],
        ];

        $homes = Home::all();

        foreach ($homes as $home) {
            foreach ($expenseCategories as $index => $cat) {
                $parent = ExpenseCategory::updateOrCreate(
                    [
                        'home_id' => $home->id,
                        'name' => $cat['name'],
                        'type' => ExpenseCategory::TYPE_EXPENSE,
                    ],
                    [
                        'parent_id' => null,
                        'icon' => $cat['icon'],
                        'color' => $cat['color'],
                        'is_system' => true,
                        'category_group' => $cat['category_group'] ?? null,
                        'sort_order' => $index,
                    ]
                );

                if (isset($cat['children'])) {
                    foreach ($cat['children'] as $childIndex => $child) {
                        ExpenseCategory::updateOrCreate(
                            [
                                'home_id' => $home->id,
                                'parent_id' => $parent->id,
                                'name' => $child['name'],
                                'type' => ExpenseCategory::TYPE_EXPENSE,
                            ],
                            [
                                'icon' => $child['icon'],
                                'color' => $cat['color'],
                                'is_system' => true,
                                'category_group' => $child['category_group'] ?? $parent->category_group ?? null,
                                'sort_order' => $childIndex,
                            ]
                        );
                    }
                }
            }

            foreach ($incomeCategories as $index => $cat) {
                $parent = ExpenseCategory::updateOrCreate(
                    [
                        'home_id' => $home->id,
                        'name' => $cat['name'],
                        'type' => ExpenseCategory::TYPE_INCOME,
                    ],
                    [
                        'parent_id' => null,
                        'icon' => $cat['icon'],
                        'color' => $cat['color'],
                        'is_system' => true,
                        'category_group' => $cat['category_group'] ?? null,
                        'sort_order' => $index,
                    ]
                );

                if (isset($cat['children'])) {
                    foreach ($cat['children'] as $childIndex => $child) {
                        ExpenseCategory::updateOrCreate(
                            [
                                'home_id' => $home->id,
                                'parent_id' => $parent->id,
                                'name' => $child['name'],
                                'type' => ExpenseCategory::TYPE_INCOME,
                            ],
                            [
                                'icon' => $child['icon'],
                                'color' => $cat['color'],
                                'is_system' => true,
                                'category_group' => $child['category_group'] ?? $parent->category_group ?? null,
                                'sort_order' => $childIndex,
                            ]
                        );
                    }
                }
            }
        }
    }
}
