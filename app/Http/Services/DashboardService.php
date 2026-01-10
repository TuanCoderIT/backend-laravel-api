<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Exam;
use App\Models\Group;
use App\Models\Post;
use App\Models\Transaction;

class DashboardService
{
    /**
     * Lấy dữ liệu dashboard (cards, charts, latest transactions)
     *
     * @param int $days số ngày để lấy biểu đồ (mặc định 30)
     * @return array
     */
    public function getDashboardData(int $days = 30): array
    {
        $cacheKey = "admin:dashboard:v1:days_{$days}";
        // cache 60s - 5 phút tùy mong muốn (đặt ngắn nếu cần realtime)
        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($days) {
            return $this->buildDashboard($days);
        });
    }

    protected function buildDashboard(int $days): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays($days - 1)->startOfDay(); // inclusive

        // ------------- CARDS -------------
        $totalUsers = User::count();
        $newUsers7Days = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $totalExams = Exam::count();
        $totalGroups = Group::count();
        $totalPosts = Post::count();
        $totalTransactions = Transaction::count();

        // ------------- CHART: users per day (last $days) -------------
        $usersPerDayRaw = User::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $usersPerDay = $this->fillDateSeries($startDate, $days, $usersPerDayRaw);

        // ------------- CHART: revenue per day (last $days) -------------
        // Giả sử Transaction.amount là số tiền (số token hoặc tiền). type = 'deposit' là nạp
        $revenueRaw = Transaction::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->where('created_at', '>=', $startDate)
            ->where('type', 'top_up')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $revenuePerDay = $this->fillDateSeries($startDate, $days, $revenueRaw, 0);

        // ------------- CHART: quiz created per month (last 12 months) -------------
        $examPerMonthRaw = Exam::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('count(*) as total'))
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $examPerMonth = $this->fillMonthSeries(12, $examPerMonthRaw);

        // ------------- Latest transactions -------------
        $latestTransactions = Transaction::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'user_id', 'amount', 'type', 'created_at']);

        return [
            'cards' => [
                'totalUsers' => $totalUsers,
                'newUsers7Days' => $newUsers7Days,
                'totalExams' => $totalExams,
                'totalGroups' => $totalGroups,
                'totalPosts' => $totalPosts,
                'totalTransactions' => $totalTransactions,
            ],
            'charts' => [
                'usersPerDay' => $usersPerDay,      // [{date: '2025-12-01', total: 5}, ...]
                'revenuePerDay' => $revenuePerDay,  // [{date, total}]
                'examPerMonth' => $examPerMonth,    // [{month: '2025-01', total: 3}, ...]
            ],
            'tables' => [
                'latestTransactions' => $latestTransactions,
            ],
            'meta' => [
                'generated_at' => Carbon::now()->toDateTimeString(),
            ],
        ];
    }

    /**
     * Fill missing dates between startDate for count days.
     * $raw is associative array ['YYYY-MM-DD' => value]
     */
    protected function fillDateSeries(Carbon $startDate, int $days, array $raw, $default = 0): array
    {
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $startDate->copy()->addDays($i)->toDateString();
            $data[] = [
                'date' => $d,
                'total' => isset($raw[$d]) ? (int) $raw[$d] : $default,
            ];
        }
        return $data;
    }

    /**
     * Fill last $months months series
     * $raw is assoc ['YYYY-MM' => value]
     */
    protected function fillMonthSeries(int $months, array $raw, $default = 0): array
    {
        $data = [];
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i)->format('Y-m');
            $data[] = [
                'month' => $m,
                'total' => isset($raw[$m]) ? (int) $raw[$m] : $default,
            ];
        }
        return $data;
    }
}
