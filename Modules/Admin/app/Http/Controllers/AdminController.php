<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\Device\Models\DeviceType;
use Modules\Home\Models\Home;
use Modules\Tariff\Models\TariffPlan;

class AdminController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_homes' => Home::count(),
            'total_device_types' => DeviceType::count(),
            'total_tariff_plans' => TariffPlan::count(),
            'total_ai_analyses' => AiAnalysisRequest::count(),
            'ai_usage_today' => AiAnalysisRequest::whereDate('created_at', today())->count(),
            'ai_total_cost' => AiAnalysisRequest::whereHas('result')
                ->with('result')
                ->get()
                ->sum(fn ($r) => $r->result?->cost ?? 0),
        ];

        $recentAnalyses = AiAnalysisRequest::with(['user', 'result'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin::index', compact('stats', 'recentAnalyses'));
    }
}
