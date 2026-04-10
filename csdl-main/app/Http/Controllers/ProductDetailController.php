<?php

namespace App\Http\Controllers;

use App\Imports\ProductDetailImport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductDetailController extends Controller
{
    public function index($productId)
    {
        $infoProduct = DB::selectOne(
            "SELECT p.*, c.categoryName
             FROM products p
             JOIN categories c ON p.cateID = c.categoryID
             WHERE p.isDeleted = 0 AND p.productID = ? AND c.isDeleted = 0",
            [$productId]
        );
        if (!$infoProduct) {
            abort(404);
        }

        $images = DB::select(
            "SELECT * FROM product_images WHERE prdID = ? ORDER BY imageID ASC",
            [$productId]
        );
        $infoProductObj = (object)$infoProduct;
        $infoProductObj->images = $images;

        $perPage = 10;
        $page = max((int) request()->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $totalRow = DB::selectOne(
            "SELECT COUNT(*) AS aggregate FROM product_details WHERE isDeleted = 0 AND prdID = ?",
            [$productId]
        );
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $rows = DB::select(
            "SELECT pd.*, p.productName, p.productSellPrice, s.sizeName, c.colorName
             FROM product_details pd
             JOIN products p ON pd.prdID = p.productID
             LEFT JOIN sizes s ON pd.sizeId = s.sizeId
             LEFT JOIN colors c ON pd.colorId = c.colorId
             WHERE pd.isDeleted = 0 AND pd.prdID = ?
             ORDER BY pd.id DESC
             LIMIT ? OFFSET ?",
            [$productId, $perPage, $offset]
        );

        $productDetails = collect($rows)->map(function ($row) {
            $detail = new \stdClass();
            foreach ($row as $k => $v) {
                $detail->{$k} = $v;
            }
            $detail->product = (object)[
                'productName' => $row->productName,
                'productSellPrice' => $row->productSellPrice,
            ];
            $detail->size = $row->sizeName ? (object)['sizeName' => $row->sizeName] : null;
            $detail->color = $row->colorName ? (object)['colorName' => $row->colorName] : null;
            return $detail;
        });

        $productDetailsPaginator = new LengthAwarePaginator(
            $productDetails,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $sizes = collect(DB::select("SELECT * FROM sizes WHERE isDeleted = 0"));
        $colors = collect(DB::select("SELECT * FROM colors WHERE isDeleted = 0"));

        return view('AdminPage.ProductDetails', [
            'productDetails' => $productDetailsPaginator,
            'sizes' => $sizes,
            'colors' => $colors,
            'infoProduct' => $infoProductObj
        ]);
    }

    public function store(Request $request, $productId)
    {
        // Upload Excel
        if ($request->hasFile('excel_file')) {
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls'
            ]);

            Excel::import(new ProductDetailImport($productId), $request->file('excel_file'));

            return redirect()->back()->with('success', 'Nhập Excel thành công!');
        }

        // Nhập thủ công
        $request->validate([
            'sizeId' => 'required',
            'colorId' => 'required',
            'productQuantity' => 'required|integer|min:1',
        ]);

        $existing = DB::selectOne(
            "SELECT id, productQuantity FROM product_details WHERE prdID = ? AND sizeId = ? AND colorId = ? AND isDeleted = 0",
            [$productId, $request->sizeId, $request->colorId]
        );

        if ($existing) {
            DB::update(
                "UPDATE product_details SET productQuantity = productQuantity + ?, updated_at = ? WHERE id = ?",
                [$request->productQuantity, now(), $existing->id]
            );
        } else {
            DB::insert(
                "INSERT INTO product_details (prdID, sizeId, colorId, productQuantity, isDeleted, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 0, ?, ?)",
                [
                    $productId,
                    $request->sizeId,
                    $request->colorId,
                    $request->productQuantity,
                    now(),
                    now(),
                ]
            );
        }

        return redirect()->back()->with('success', 'Thêm chi tiết sản phẩm thành công.');
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'prdID' => 'required',
            'sizeId' => 'required',
            'colorId' => 'required',
            'productQuantity' => 'required',
        ]);

        DB::update(
            "UPDATE product_details
             SET prdID = ?, sizeId = ?, colorId = ?, productQuantity = ?, updated_at = ?
             WHERE id = ?",
            [
                $request->prdID,
                $request->sizeId,
                $request->colorId,
                $request->productQuantity,
                now(),
                $id,
            ]
        );

        return redirect()->back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy($id)
    {
        DB::update(
            "UPDATE product_details SET isDeleted = 1, updated_at = ? WHERE id = ?",
            [now(), $id]
        );
        return redirect()->back()->with('success', 'Đã xóa (ẩn) chi tiết sản phẩm.');
    }
}
