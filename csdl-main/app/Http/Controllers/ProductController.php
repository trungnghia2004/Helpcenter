<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = 10;
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $where = 'p.isDeleted = 0 AND c.isDeleted = 0';
        $bindings = [];
        if (!empty($search)) {
            $where .= ' AND (p.productName LIKE ? OR p.productSellPrice LIKE ?)';
            $bindings[] = '%' . $search . '%';
            $bindings[] = '%' . $search . '%';
        }

        $totalRow = DB::selectOne(
            "SELECT COUNT(*) AS aggregate
             FROM products p
             JOIN categories c ON p.cateID = c.categoryID
             WHERE $where",
            $bindings
        );
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $rows = DB::select(
            "SELECT 
                p.*,
                c.categoryName,
                (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS firstImage
             FROM products p
             JOIN categories c ON p.cateID = c.categoryID
             WHERE $where
             ORDER BY p.productID DESC
             LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );

        $commentStats = $this->getCommentStats();
        $products = collect($rows)->map(function ($row) use ($commentStats) {
            $product = $this->mapProductRow($row, true, true, $commentStats);
            return $product;
        });

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

        $categories = collect(DB::select("SELECT * FROM categories WHERE isDeleted = 0"));
        $sizes = collect(DB::select("SELECT * FROM sizes WHERE isDeleted = 0"));
        $colors = collect(DB::select("SELECT * FROM colors WHERE isDeleted = 0"));

        return view('AdminPage.Products', [
            'products' => $productsPaginator,
            'categories' => $categories,
            'search' => $search,
            'sizes' => $sizes,
            'colors' => $colors,
            'total' => $total
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'productName' => 'required',
            'productBuyPrice' => 'required|numeric',
            'productSellPrice' => 'required|numeric',
            'productForGender' => 'required',
            'cateID' => 'required|exists:categories,categoryID',
            'productDesc' => 'required'
        ]);

        $categoryRow = DB::selectOne("SELECT categoryName FROM categories WHERE categoryID = ?", [$request->cateID]);
        $categoryName = $categoryRow->categoryName ?? '';

        $prefix = $this->resolvePrefix($categoryName);
        $productCode = $this->generateProductCode($prefix);

        DB::insert(
            "INSERT INTO products (productName, productBuyPrice, productSellPrice, productForGender, cateID, productDesc, productCode, isDeleted, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)",
            [
                $request->productName,
                $request->productBuyPrice,
                $request->productSellPrice,
                $request->productForGender,
                $request->cateID,
                $request->productDesc,
                $productCode,
                now(),
                now(),
            ]
        );

        return redirect()->route('products.index')->with('success', 'Tạo sản phẩm thành công.');
    }

    public function update(Request $request, $productId)
    {
        $request->validate([
            'productName' => 'required',
            'productBuyPrice' => 'required|numeric',
            'productSellPrice' => 'required|numeric',
            'productForGender' => 'required',
            'cateID' => 'required|exists:categories,categoryID',
            'productDesc' => 'required'
        ]);

        $categoryRow = DB::selectOne("SELECT categoryName FROM categories WHERE categoryID = ?", [$request->cateID]);
        $categoryName = $categoryRow->categoryName ?? '';
        $prefix = $this->resolvePrefix($categoryName);
        $productCode = $this->generateProductCode($prefix);

        DB::update(
            "UPDATE products
             SET productName = ?, productBuyPrice = ?, productSellPrice = ?, productDesc = ?, productForGender = ?, productQuantity = ?, cateID = ?, productCode = ?, updated_at = ?
             WHERE productID = ?",
            [
                $request->productName,
                $request->productBuyPrice,
                $request->productSellPrice,
                $request->productDesc,
                $request->productForGender,
                $request->productQuantity,
                $request->cateID,
                $productCode,
                now(),
                $productId,
            ]
        );

        return redirect()->route('products.index')->with('success', 'Chỉnh sửa sản phẩm thành công.');
    }

    public function destroy($productId)
    {
        DB::update(
            "UPDATE products SET isDeleted = 1, updated_at = ? WHERE productID = ?",
            [now(), $productId]
        );
        return redirect()->route('products.index')->with('success', 'Xóa sản phẩm thành công.');
    }

    public function getColorsBySize(Request $request)
    {
        $sizeName = $request->input('size');
        $prdID = $request->input('prdID');
        $sizeRow = DB::selectOne("SELECT sizeId FROM sizes WHERE sizeName = ?", [$sizeName]);
        if (!$sizeRow) {
            return response()->json(['colors' => []]);
        }

        $colors = DB::select(
            "SELECT DISTINCT c.colorName
             FROM product_details pd
             JOIN colors c ON pd.colorId = c.colorId
             WHERE pd.sizeId = ? AND pd.prdID = ?",
            [$sizeRow->sizeId, $prdID]
        );
        $colorNames = collect($colors)->pluck('colorName')->values();

        return response()->json(['colors' => $colorNames]);
    }

    public function filterByCategory($cateID)
    {
        return $this->filterCommon(function (&$where, &$bindings) use ($cateID) {
            $where .= ' AND p.cateID = ?';
            $bindings[] = $cateID;
        });
    }

    public function filterByGender($genderID)
    {
        return $this->filterCommon(function (&$where, &$bindings) use ($genderID) {
            $where .= ' AND p.productForGender = ?';
            $bindings[] = $genderID;
        });
    }

    public function findByPrice(Request $request)
    {
        return $this->filterCommon(function (&$where, &$bindings) use ($request) {
            if ($request->filled('min_price')) {
                $where .= ' AND p.productSellPrice >= ?';
                $bindings[] = (int) $request->min_price;
            }
            if ($request->filled('max_price')) {
                $where .= ' AND p.productSellPrice <= ?';
                $bindings[] = (int) $request->max_price;
            }
        });
    }

    private function filterCommon(callable $whereCallback)
    {
        $perPage = 8;
        $page = max((int) request()->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $where = 'p.isDeleted = 0';
        $bindings = [];
        $whereCallback($where, $bindings);

        // Đảm bảo category active
        $where .= ' AND c.isDeleted = 0';

        $totalRow = DB::selectOne(
            "SELECT COUNT(*) AS aggregate FROM products p JOIN categories c ON p.cateID = c.categoryID WHERE $where",
            $bindings
        );
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $rows = DB::select(
            "SELECT 
                p.*,
                c.categoryName,
                (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS firstImage
             FROM products p
             JOIN categories c ON p.cateID = c.categoryID
             WHERE $where
             ORDER BY p.productID DESC
             LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );

        $commentStats = $this->getCommentStats();
        $products = collect($rows)->map(fn($row) => $this->mapProductRow($row, true, true, $commentStats));

        $categories = collect(DB::select("SELECT * FROM categories WHERE isDeleted = 0"));

        $productsPaginator = new LengthAwarePaginator(
            $products,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('UserPage.Product', [
            'products' => $productsPaginator,
            'categories' => $categories,
        ]);
    }

    private function resolvePrefix(string $categoryName): string
    {
        $name = Str::lower($categoryName);
        return match ($name) {
            'áo thun' => 'AT',
            'quần jeans' => 'QJ',
            'quần dài' => 'QD',
            'áo hoodie' => 'AH',
            'áo gile' => 'AG',
            'quần âu' => 'QAU',
            'quần short' => 'QS',
            'áo khoác' => 'AK',
            default => 'PRD',
        };
    }

    private function generateProductCode(string $prefix): string
    {
        $row = DB::selectOne(
            "SELECT productCode FROM products WHERE productCode LIKE ? ORDER BY productCode DESC LIMIT 1",
            [$prefix . '%']
        );
        if ($row && isset($row->productCode)) {
            $lastNumber = (int) substr($row->productCode, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
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
            // Bảng chưa tồn tại hoặc chưa migrate
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

    private function mapProductRow($row, bool $includeCategory = true, bool $includeAvg = false, array $commentStats = [])
    {
        $product = new \stdClass();
        foreach ($row as $k => $v) {
            $product->{$k} = $v;
        }
        $product->firstImage = $row->firstImage ? (object)['imageLink' => $row->firstImage] : null;
        if ($includeCategory) {
            $product->category = (object)[
                'categoryName' => $row->categoryName ?? null,
            ];
        }
        if ($includeAvg) {
            $product->average_star = $commentStats[$row->productID]['avg'] ?? 0;
            $product->quantityComment = $commentStats[$row->productID]['count'] ?? 0;
        }
        return $product;
    }
}
