<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = 10;
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $whereSql = 'isDeleted = 0';
        $bindings = [];

        if ($search) {
            $whereSql .= ' AND categoryName LIKE ?';
            $bindings[] = '%' . $search . '%';
        }

        $totalRow = DB::selectOne("SELECT COUNT(*) AS aggregate FROM categories WHERE $whereSql", $bindings);
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $rows = DB::select(
            "SELECT * FROM categories WHERE $whereSql ORDER BY categoryID DESC LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );

        $categories = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('AdminPage.Categories', compact('categories', 'search','total'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'categoryName' => 'required',
            'categoryImage' => 'nullable|image',
            'categoryDesc' => 'nullable',
        ]);

        $data = $request->only('categoryName', 'categoryDesc');
        if ($request->hasFile('categoryImage')) {
            $data['categoryImage'] = $request->file('categoryImage')->store('categories', 'public');
        }

        DB::insert(
            'INSERT INTO categories (categoryName, categoryDesc, categoryImage, created_at, updated_at, isDeleted)
             VALUES (?, ?, ?, ?, ?, 0)',
            [
                $data['categoryName'],
                $data['categoryDesc'] ?? null,
                $data['categoryImage'] ?? null,
                now(),
                now(),
            ]
        );

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function edit($id)
    {
        $category = DB::selectOne('SELECT * FROM categories WHERE categoryID = ?', [$id]);
        if (!$category) {
            abort(404);
        }
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'categoryName' => 'required',
            'categoryImage' => 'nullable|image',
            'categoryDesc' => 'nullable',
        ]);

        $data = $request->only('categoryName', 'categoryDesc');
        if ($request->hasFile('categoryImage')) {
            $data['categoryImage'] = $request->file('categoryImage')->store('categories', 'public');
        }

        DB::update(
            'UPDATE categories 
             SET categoryName = ?, categoryDesc = ?, categoryImage = ?, updated_at = ?
             WHERE categoryID = ?',
            [
                $data['categoryName'],
                $data['categoryDesc'] ?? null,
                $data['categoryImage'] ?? null,
                now(),
                $id,
            ]
        );

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        DB::update(
            'UPDATE categories SET isDeleted = 1, updated_at = ? WHERE categoryID = ?',
            [now(), $id]
        );
        return redirect()->route('categories.index')->with('success', 'Category deleted (soft) successfully.');
    }
}
