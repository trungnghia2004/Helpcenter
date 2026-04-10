<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DisplayProductController extends Controller
{
    // Trang chủ: sản phẩm, danh mục, bình luận nổi bật
    public function customerPage()
    {
        // Thống kê đánh giá theo productID
        $commentStats = $this->getCommentStats();

        // Lấy 4 sản phẩm kèm ảnh đầu tiên, danh mục
        $products = $this->fetchProducts(
            limit: 4,
            search: null,
            includeCategory: true,
            onlyActiveCategory: true,
            includeAvg: true,
            commentStats: $commentStats
        );

        // 3 bình luận có rate cao nhất (ưu tiên rate rồi thời gian)
        try {
            $comments = collect(DB::select(
                "SELECT car.*, u.name AS user_name
                 FROM comment_and_rate car
                 LEFT JOIN users u ON car.cusID = u.id
                 ORDER BY car.rate DESC, car.created_at DESC
                 LIMIT 3"
            ))->map(function ($row) {
                $row->user = (object)['name' => $row->user_name];
                return $row;
            });
        } catch (\Throwable $e) {
            $comments = collect();
        }

        // 4 danh mục đầu
        $categories = collect(DB::select(
            "SELECT * FROM categories WHERE isDeleted = 0 ORDER BY categoryID ASC LIMIT 4"
        ));

        return view('UserPage.HomePage', compact('products', 'categories', 'comments'));
    }

    // Trang danh sách sản phẩm (có tìm kiếm, phân trang)
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = 8;
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $commentStats = $this->getCommentStats();

        // Tổng số bản ghi
        $where = 'p.isDeleted = 0';
        $bindings = [];
        if ($search) {
            $where .= ' AND p.productName LIKE ?';
            $bindings[] = '%' . $search . '%';
        }
        $totalRow = DB::selectOne(
            "SELECT COUNT(*) AS aggregate
             FROM products p
             JOIN categories c ON p.cateID = c.categoryID
             WHERE $where AND c.isDeleted = 0",
            $bindings
        );
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $products = $this->fetchProducts(
            limit: $perPage,
            offset: $offset,
            search: $search,
            includeCategory: true,
            onlyActiveCategory: true,
            includeAvg: true,
            commentStats: $commentStats
        );

        $categories = collect(DB::select("SELECT * FROM categories WHERE isDeleted = 0"));

        $productsPaginator = new LengthAwarePaginator(
            $products,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('UserPage.Product', [
            'products' => $productsPaginator,
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function productDetails($productID)
    {
        $userId = Auth::id();

        // Sản phẩm + hình ảnh
        $productRow = DB::selectOne(
            "SELECT p.*, c.categoryName,
                    (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS firstImage
             FROM products p
             LEFT JOIN categories c ON p.cateID = c.categoryID
             WHERE p.productID = ?",
            [$productID]
        );
        if (!$productRow) {
            abort(404);
        }

        // Toàn bộ ảnh
        $images = collect(DB::select(
            "SELECT * FROM product_images WHERE prdID = ? ORDER BY imageID ASC",
            [$productID]
        ));

        $product = $this->mapProductRow($productRow, true);
        $product->images = $images;

        // Bình luận của sản phẩm + user
        try {
            $comments = collect(DB::select(
                "SELECT car.*, u.name AS user_name
                 FROM comment_and_rate car
                 LEFT JOIN users u ON car.cusID = u.id
                 WHERE car.productID = ?
                 ORDER BY car.created_at DESC",
                [$productID]
            ))->map(function ($row) {
                $row->user = (object)['name' => $row->user_name];
                return $row;
            });
        } catch (\Throwable $e) {
            $comments = collect();
        }

        // Người dùng đã mua?
        $hasPurchased = false;
        if ($userId) {
            $hasPurchasedRow = DB::selectOne(
                "SELECT 1
                 FROM order_details od
                 JOIN orders o ON od.orderID = o.orderID
                 JOIN product_details pd ON od.productDetailID = pd.id
                 WHERE o.cusID = ? AND pd.prdID = ?
                 LIMIT 1",
                [$userId, $productID]
            );
            $hasPurchased = (bool) $hasPurchasedRow;
        }

        // Bình luận của chính user (nếu có)
        $userComment = null;
        if ($userId) {
            try {
                $userComment = DB::selectOne(
                    "SELECT * FROM comment_and_rate WHERE productID = ? AND cusID = ? LIMIT 1",
                    [$productID, $userId]
                );
            } catch (\Throwable $e) {
                $userComment = null;
            }
        }

        // Sản phẩm gợi ý (4 cái)
        $relatedProducts = $this->fetchProducts(
            limit: 4,
            search: null,
            includeCategory: true,
            onlyActiveCategory: true,
            includeAvg: true,
            commentStats: $this->getCommentStats()
        );

        // Biến thể sản phẩm
        $productDetails = collect(DB::select(
            "SELECT 
                pd.*,
                s.sizeName,
                c.colorName,
                p.productName,
                p.productSellPrice
             FROM product_details pd
             JOIN products p ON pd.prdID = p.productID
             LEFT JOIN sizes s ON pd.sizeId = s.sizeId
             LEFT JOIN colors c ON pd.colorId = c.colorId
             WHERE pd.isDeleted = 0 AND pd.prdID = ?",
            [$productID]
        ))->map(function ($row) {
            $detail = new \stdClass();
            $detail->id = $row->id;
            $detail->prdID = $row->prdID;
            $detail->productQuantity = $row->productQuantity;
            $detail->product = (object)[
                'productName' => $row->productName,
                'productSellPrice' => $row->productSellPrice,
            ];
            $detail->size = $row->sizeName ? (object)['sizeName' => $row->sizeName, 'sizeId' => $row->sizeId] : null;
            $detail->color = $row->colorName ? (object)['colorName' => $row->colorName] : null;
            return $detail;
        });

        // Điểm trung bình + số lượng bình luận
        $stats = $this->getCommentStats();
        $product->average_star = $stats[$productID]['avg'] ?? 0;
        $product->quantityComment = $stats[$productID]['count'] ?? 0;

        return view('UserPage.ProductDetails', [
            'product' => $product,
            'products' => $relatedProducts,
            'userComment' => $userComment,
            'productDetails' => $productDetails,
            'comments' => $comments,
            'hasPurchased' => $hasPurchased,
        ]);
    }

    private function getCommentStats(): array
    {
        try {
            $rows = DB::select(
                "SELECT productID, AVG(rate) AS avg_rate, COUNT(*) AS cnt
                 FROM comment_and_rate
                 GROUP BY productID"
            );
        } catch (\Throwable $e) {
            // Bảng chưa có hoặc chưa migrate: trả về rỗng để không lỗi trang
            return [];
        }
        $stats = [];
        foreach ($rows as $row) {
            $stats[$row->productID] = [
                'avg' => (float) $row->avg_rate,
                'count' => (int) $row->cnt,
            ];
        }
        return $stats;
    }

    /**
     * Lấy danh sách sản phẩm với ảnh đầu, danh mục, và điểm trung bình (nếu cần).
     */
    private function fetchProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $search = null,
        bool $includeCategory = true,
        bool $onlyActiveCategory = true,
        bool $includeAvg = false,
        array $commentStats = []
    ) {
        $where = 'p.isDeleted = 0';
        $bindings = [];
        if ($search) {
            $where .= ' AND p.productName LIKE ?';
            $bindings[] = '%' . $search . '%';
        }
        if ($onlyActiveCategory) {
            $where .= ' AND c.isDeleted = 0';
        }

        $limitSql = '';
        if (!is_null($limit)) {
            $limitSql = ' LIMIT ?';
            $bindings[] = $limit;
            if (!is_null($offset)) {
                $limitSql .= ' OFFSET ?';
                $bindings[] = $offset;
            }
        }

        $rows = DB::select(
            "SELECT 
                p.*,
                c.categoryName,
                (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS firstImage
             FROM products p
             LEFT JOIN categories c ON p.cateID = c.categoryID
             WHERE $where
             ORDER BY p.productID DESC
             $limitSql",
            $bindings
        );

        $products = collect($rows)->map(function ($row) use ($includeCategory, $includeAvg, $commentStats) {
            return $this->mapProductRow($row, $includeCategory, $includeAvg, $commentStats);
        });

        return $products;
    }

    private function mapProductRow($row, bool $includeCategory = true, bool $includeAvg = false, array $commentStats = [])
    {
        $product = new \stdClass();
        foreach ($row as $k => $v) {
            $product->{$k} = $v;
        }
        // Ảnh đầu tiên
        $product->firstImage = $row->firstImage ? (object)['imageLink' => $row->firstImage] : null;

        // Danh mục
        if ($includeCategory) {
            $product->category = (object)[
                'categoryName' => $row->categoryName ?? null,
            ];
        }

        // Bảo đảm có thuộc tính tồn kho (nếu bảng products không có cột này thì đặt mặc định 0)
        $product->productQuantity = property_exists($product, 'productQuantity') ? $product->productQuantity : 0;

        // Điểm đánh giá
        if ($includeAvg) {
            $product->average_star = $commentStats[$row->productID]['avg'] ?? 0;
            $product->quantityComment = $commentStats[$row->productID]['count'] ?? 0;
        }

        return $product;
    }
}
