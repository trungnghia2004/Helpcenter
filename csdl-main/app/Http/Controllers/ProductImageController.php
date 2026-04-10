<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function index($productId)
    {
        $productImages = DB::select(
            "SELECT * FROM product_images WHERE prdID = ? ORDER BY imageID ASC",
            [$productId]
        );
        return view('AdminPage.ProductImage', [
            'productImage' => $productImages,
            'product' => (object)['productID' => $productId],
        ]);
    }

    // Thêm nhiều ảnh
    public function upload(Request $request, $productId)
    {
        if ($request->hasFile('productImages')) {
            foreach ($request->file('productImages') as $image) {
                $path = $image->store('product_images', 'public');

                DB::insert(
                    "INSERT INTO product_images (prdID, imageLink, created_at, updated_at) VALUES (?, ?, ?, ?)",
                    [$productId, $path, now(), now()]
                );
            }
        }

        return redirect()->route('products.index')->with('success', 'Thêm hình ảnh thành công.');
    }

    // Xóa ảnh
    public function destroy($imageId)
    {
        $image = DB::selectOne("SELECT * FROM product_images WHERE imageID = ?", [$imageId]);
        if (!$image) {
            abort(404);
        }

        if (Storage::disk('public')->exists($image->imageLink)) {
            Storage::disk('public')->delete($image->imageLink);
        }

        DB::delete("DELETE FROM product_images WHERE imageID = ?", [$imageId]);
        return back()->with('success', 'Xóa hình ảnh thành công.');
    }

    // Cập nhật ảnh (thay ảnh mới)
    public function update(Request $request, $imageId)
    {
        $request->validate([
            'newImage' => 'required|image|max:2048',
        ]);

        $image = DB::selectOne("SELECT * FROM product_images WHERE imageID = ?", [$imageId]);
        if (!$image) {
            abort(404);
        }

        if (Storage::disk('public')->exists($image->imageLink)) {
            Storage::disk('public')->delete($image->imageLink);
        }

        $newPath = $request->file('newImage')->store('product_images', 'public');
        DB::update(
            "UPDATE product_images SET imageLink = ?, updated_at = ? WHERE imageID = ?",
            [$newPath, now(), $imageId]
        );

        return back()->with('success', 'Image updated successfully.');
    }
}
