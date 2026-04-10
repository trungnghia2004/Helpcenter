<?php

namespace App\Http\Controllers;

use App\Models\DiscountProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountProgramController extends Controller
{
    public function index()
    {
        // Lấy danh sách chương trình giảm giá bằng SQL thuần (MySQL)
        $programs = collect(DB::select(
            'SELECT * FROM discount_programs ORDER BY created_at DESC'
        ));

        return view('AdminPage.DiscountProgram', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $now = now();
        DB::insert(
            'INSERT INTO discount_programs (name, description, discount_type, discount_value, max_discount, start_date, end_date, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $validated['name'],
                $validated['description'] ?? null,
                $validated['discount_type'],
                $validated['discount_value'],
                $validated['max_discount'] ?? null,
                $validated['start_date'],
                $validated['end_date'],
                $now,
                $now,
            ]
        );

        return redirect()->route('discount_programs.index')->with('success', 'Them chuong trinh thanh cong!');
    }

    public function update(Request $request, DiscountProgram $discount_program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        DB::update(
            'UPDATE discount_programs
             SET name = ?, description = ?, discount_type = ?, discount_value = ?, max_discount = ?, start_date = ?, end_date = ?, updated_at = ?
             WHERE id = ?',
            [
                $validated['name'],
                $validated['description'] ?? null,
                $validated['discount_type'],
                $validated['discount_value'],
                $validated['max_discount'] ?? null,
                $validated['start_date'],
                $validated['end_date'],
                now(),
                $discount_program->id,
            ]
        );

        return redirect()->route('discount_programs.index')->with('success', 'Cap nhat thanh cong!');
    }

    public function destroy(DiscountProgram $discount_program)
    {
        DB::delete(
            'DELETE FROM discount_programs WHERE id = ?',
            [$discount_program->id]
        );

        return redirect()->route('discount_programs.index')->with('success', 'Xoa thanh cong!');
    }
}
