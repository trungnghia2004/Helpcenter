<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $selectedMonth = request()->get('month', now()->month);

        $currentDate = Carbon::now();
        $currentMonth = $selectedMonth;
        $lastMonth = $currentDate->copy()->subMonth()->month;

        // Top 3 sản phẩm bán chạy trong tháng (đã giao - staID=4)
        $topProducts = collect(DB::select(
            "SELECT 
                p.productID,
                p.productName,
                p.productCode,
                p.productSellPrice,
                (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS first_image,
                SUM(od.orderQuantity) AS total_sold
            FROM products p
            JOIN product_details pd ON p.productID = pd.prdID
            JOIN order_details od ON pd.id = od.productDetailID
            JOIN orders o ON od.orderID = o.orderID
            WHERE MONTH(o.created_at) = ? 
              AND YEAR(o.created_at) = ?
              AND o.staID = 4
            GROUP BY p.productID, p.productName, p.productCode, p.productSellPrice, first_image
            ORDER BY total_sold DESC
            LIMIT 3",
            [$currentMonth, $currentDate->year]
        ))->map(function ($row) {
            $row->firstImage = $row->first_image ? (object)['imageLink' => $row->first_image] : null;
            return $row;
        });

        // Đơn gần đây kèm khách và trạng thái
        $recentOrders = collect(DB::select(
            "SELECT o.*, u.name AS customer_name, s.statusValue
             FROM orders o
             LEFT JOIN users u ON o.cusID = u.id
             LEFT JOIN status s ON o.staID = s.statusID
             ORDER BY o.created_at DESC
             LIMIT 5"
        ));

        $currentRevenue = (float) (DB::selectOne(
            "SELECT SUM(totalPrice) AS total FROM orders 
             WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND staID = 4",
            [$currentMonth, $currentDate->year]
        )->total ?? 0);

        $lastRevenue = (float) (DB::selectOne(
            "SELECT SUM(totalPrice) AS total FROM orders 
             WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND staID = 4",
            [$lastMonth, $currentDate->year]
        )->total ?? 0);

        $currentOrders = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM orders 
             WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?",
            [$currentMonth, $currentDate->year]
        )->total ?? 0);

        $lastOrders = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM orders 
             WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?",
            [$lastMonth, $currentDate->year]
        )->total ?? 0);

        // Sản phẩm
        $totalProducts = (int) (DB::selectOne("SELECT COUNT(*) AS total FROM products")->total ?? 0);
        $newProducts = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM products WHERE MONTH(created_at) = ?",
            [$currentMonth]
        )->total ?? 0);

        // Khách hàng
        $totalCustomers = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"
        )->total ?? 0);

        $newCustomers = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM users WHERE role = 'customer' AND MONTH(created_at) = ?",
            [$currentMonth]
        )->total ?? 0);

        $lastCustomers = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM users WHERE role = 'customer' AND MONTH(created_at) = ?",
            [$lastMonth]
        )->total ?? 0);

        // Doanh thu theo ngày trong tháng
        $revenuePerDay = DB::select(
            "SELECT DAY(created_at) AS day, SUM(totalPrice) AS total
             FROM orders
             WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND staID = 4
             GROUP BY DAY(created_at)
             ORDER BY day",
            [$currentMonth, $currentDate->year]
        );
        $revenuePerDay = collect($revenuePerDay)->keyBy('day');

        $daysInMonth = $currentDate->daysInMonth;
        $revenueLabels = [];
        $revenueData = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $label = str_pad($day, 2, '0', STR_PAD_LEFT);
            $revenueLabels[] = $label;
            $revenueData[] = (float) ($revenuePerDay[$day]->total ?? 0);
        }

        return view('AdminPage.Dashboard', [
            'currentRevenue' => $currentRevenue,
            'revenueChange' => $lastRevenue > 0 ? (($currentRevenue - $lastRevenue) / $lastRevenue) * 100 : 100,

            'currentOrders' => $currentOrders,
            'orderChange' => $lastOrders > 0 ? (($currentOrders - $lastOrders) / $lastOrders) * 100 : 100,

            'totalProducts' => $totalProducts,
            'newProducts' => $newProducts,

            'totalCustomers' => $totalCustomers,
            'customerChange' => $lastCustomers > 0 ? (($totalCustomers - $lastCustomers) / $lastCustomers) * 100 : 100,

            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,

            'revenueLabels' => $revenueLabels,
            'revenueData' => $revenueData,
        ]);
    }
}
