<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Lấy hoặc tạo cart
        $cartRow = DB::selectOne('SELECT * FROM cart WHERE userID = ?', [$userId]);
        if (!$cartRow) {
            DB::insert(
                'INSERT INTO cart (userID, created_at, updated_at) VALUES (?, ?, ?)',
                [$userId, now(), now()]
            );
            $cartRow = DB::selectOne('SELECT * FROM cart WHERE userID = ?', [$userId]);
        }

        // Chương trình giảm giá đang hoạt động
        $programs = collect(DB::select(
            "SELECT * FROM discount_programs 
             WHERE (start_date IS NULL OR start_date <= ?) 
               AND (end_date IS NULL OR end_date >= ?)",
            [Carbon::now(), Carbon::now()]
        ));

        // Lấy chi tiết giỏ hàng với thông tin sản phẩm/size/color/ảnh
        $cartDetails = collect(DB::select(
            "SELECT 
                cd.id,
                cd.cartID,
                cd.productDetailID,
                cd.quantity,
                pd.prdID,
                pd.sizeId,
                pd.colorId,
                pd.productQuantity,
                p.productName,
                p.productSellPrice,
                s.sizeName,
                c.colorName,
                img.imageLink AS firstImage
            FROM cart_details cd
            JOIN product_details pd ON cd.productDetailID = pd.id
            JOIN products p ON pd.prdID = p.productID
            LEFT JOIN sizes s ON pd.sizeId = s.sizeId
            LEFT JOIN colors c ON pd.colorId = c.colorId
            LEFT JOIN (
                SELECT pi.prdID, pi.imageLink
                FROM product_images pi
                JOIN (
                    SELECT prdID, MIN(imageID) AS firstImageID
                    FROM product_images
                    GROUP BY prdID
                ) x ON pi.imageID = x.firstImageID
            ) img ON img.prdID = pd.prdID
            WHERE cd.cartID = ?",
            [$cartRow->cartID]
        ));
        $cartDetails = $cartDetails->map(function ($row) {
            $detail = new \stdClass();
            $detail->id = $row->id;
            $detail->cartID = $row->cartID;
            $detail->productDetailID = $row->productDetailID;
            $detail->quantity = $row->quantity;

            $product = new \stdClass();
            $product->productName = $row->productName;
            $product->productSellPrice = $row->productSellPrice;
            $product->firstImage = $row->firstImage ? (object)['imageLink' => $row->firstImage] : null;

            $productDetail = new \stdClass();
            $productDetail->id = $row->productDetailID;
            $productDetail->prdID = $row->prdID;
            $productDetail->product = $product;
            $productDetail->size = $row->sizeName ? (object)['sizeName' => $row->sizeName] : null;
            $productDetail->color = $row->colorName ? (object)['colorName' => $row->colorName] : null;

            $detail->productDetail = $productDetail;
            return $detail;
        });

        $subtotal = 0;
        foreach ($cartDetails as $detail) {
            $price = $detail->productDetail->product->productSellPrice ?? 0;
            $quantity = $detail->quantity;
            $subtotal += $price * $quantity;
        }

        $discountAmount = 0;
        if ($programs->isNotEmpty()) {
            // áp dụng chương trình đầu tiên (hoặc có thể chọn logic khác)
            $program = $programs->first();
            $discountAmount = $this->calculateDiscount($program, $subtotal);
        }
        $finalPrice = max(0, $subtotal - $discountAmount);
        $total = $finalPrice;

        return view('UserPage.Cart', compact('cartDetails','subtotal','total','programs','discountAmount'));
    }


    public function addToCart(Request $request)
    {
        $request->validate([
            'productID' => 'required|exists:products,productID',
            'quantity' => 'required|integer|min:1',
        ]);

        $size = $request->size;
        $color = $request->color;

        $sizeRow = DB::selectOne('SELECT sizeId FROM sizes WHERE sizeName = ?', [$size]);
        $colorRow = DB::selectOne('SELECT colorId FROM colors WHERE colorName = ?', [$color]);
        if (!$sizeRow || !$colorRow) {
            return back()->with('error', 'Size hoặc màu không hợp lệ.');
        }

        $productDetailRow = DB::selectOne(
            'SELECT id FROM product_details WHERE prdID = ? AND sizeId = ? AND colorId = ?',
            [$request->productID, $sizeRow->sizeId, $colorRow->colorId]
        );
        if (!$productDetailRow) {
            return back()->with('error', 'Biến thể sản phẩm không tồn tại.');
        }

        $userId = Auth::id();
        $cartRow = DB::selectOne('SELECT * FROM cart WHERE userID = ?', [$userId]);
        if (!$cartRow) {
            DB::insert(
                'INSERT INTO cart (userID, created_at, updated_at) VALUES (?, ?, ?)',
                [$userId, now(), now()]
            );
            $cartRow = DB::selectOne('SELECT * FROM cart WHERE userID = ?', [$userId]);
        }

        $existingDetail = DB::selectOne(
            'SELECT id, quantity FROM cart_details WHERE cartID = ? AND productDetailID = ?',
            [$cartRow->cartID, $productDetailRow->id]
        );

        $cartDetailId = null;
        $quantity = $request->quantity;

        if ($existingDetail) {
            DB::update(
                'UPDATE cart_details SET quantity = quantity + ?, updated_at = ? WHERE id = ?',
                [$quantity, now(), $existingDetail->id]
            );
            $cartDetailId = $existingDetail->id;
            $quantity = $existingDetail->quantity + $quantity;
        } else {
            DB::insert(
                'INSERT INTO cart_details (cartID, productDetailID, quantity, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$cartRow->cartID, $productDetailRow->id, $quantity, now(), now()]
            );
            $cartDetailId = DB::getPdo()->lastInsertId();
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'cart_detail_id' => $cartDetailId,
                'quantity' => $quantity
            ]);
        }
        return redirect()->route('cart.index')->with('success', 'Đã thêm vào giỏ hàng');
    }

    public function removeItem($id)
    {
        DB::delete('DELETE FROM cart_details WHERE id = ?', [$id]);
        return redirect()->back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
    }

    public function getDiscount($id)
    {
        $program = DB::selectOne(
            "SELECT * FROM discount_programs 
             WHERE id = ? 
               AND (start_date IS NULL OR start_date <= ?) 
               AND (end_date IS NULL OR end_date >= ?)",
            [$id, Carbon::now(), Carbon::now()]
        );

        if (!$program) {
            return response()->json(['error' => 'Chương trình không hợp lệ hoặc đã hết hạn'], 400);
        }

        $originalPrice = (float) request()->query('total', 0);
        $discount = $this->calculateDiscount($program, $originalPrice);

        return response()->json([
            'discount' => $discount
        ]);
    }

    private function calculateDiscount($program, $amount)
    {
        $discount = 0;
        if (($program->discount_type ?? '') === 'percent') {
            $discount = $amount * (($program->discount_value ?? 0) / 100);
        } else {
            $discount = (float) ($program->discount_value ?? 0);
        }

        if (!empty($program->max_discount)) {
            $discount = min($discount, (float) $program->max_discount);
        }

        return $discount;
    }
}
