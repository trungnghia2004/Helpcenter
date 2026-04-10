<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentAndRateController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'productID' => 'required|exists:products,productID',
            'contentComment' => 'required|string',
            'rate' => 'required|integer|min:1|max:5',
        ]);

        $user = Auth::user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Chi khach hang moi duoc binh luan.'], 403);
        }

        try {
            DB::insert(
                'INSERT INTO comment_and_rate (cusID, productID, contentComment, rate, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $user->id,
                    $request->productID,
                    $request->contentComment,
                    $request->rate,
                    now(),
                    now(),
                ]
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Bảng comment_and_rates chưa sẵn sàng. Vui lòng migrate cơ sở dữ liệu.');
        }

        return back()->with('success', 'Binh luan da duoc tao.');
    }


    public function update(Request $request, $id)
    {
        try {
            $comment = DB::selectOne(
                'SELECT * FROM comment_and_rate WHERE id = ?',
                [$id]
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['message' => 'Bảng comment_and_rates chưa sẵn sàng. Vui lòng migrate cơ sở dữ liệu.']);
        }
        if (!$comment) {
            abort(404);
        }

        if (Auth::id() !== $comment->cusID) {
            return back()->withErrors(['message' => 'Ban khong co quyen sua binh luan nay.']);
        }

        $request->validate([
            'contentComment' => 'required|string|max:500',
            'rate' => 'required|integer|min:1|max:5',
        ]);

        try {
            DB::update(
                'UPDATE comment_and_rate SET contentComment = ?, rate = ?, updated_at = ? WHERE id = ?',
                [
                    $request->contentComment,
                    $request->rate,
                    now(),
                    $id,
                ]
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['message' => 'Bảng comment_and_rates chưa sẵn sàng. Vui lòng migrate cơ sở dữ liệu.']);
        }

        return back()->with('success', 'Binh luan da duoc cap nhat.');
    }

    public function destroy($id)
    {
        try {
            $comment = DB::selectOne(
                'SELECT * FROM comment_and_rate WHERE id = ?',
                [$id]
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['message' => 'Bảng comment_and_rates chưa sẵn sàng. Vui lòng migrate cơ sở dữ liệu.']);
        }
        if (!$comment) {
            abort(404);
        }

        if (Auth::id() !== $comment->cusID) {
            return back()->withErrors(['message' => 'Ban khong co quyen xoa binh luan nay.']);
        }

        try {
            DB::delete(
                'DELETE FROM comment_and_rate WHERE id = ?',
                [$id]
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['message' => 'Bảng comment_and_rates chưa sẵn sàng. Vui lòng migrate cơ sở dữ liệu.']);
        }

        return back()->with('success', 'Da xoa binh luan.');
    }
}
