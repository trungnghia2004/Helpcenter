<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerAccountController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = 10;
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $whereSql = 'role = ?';
        $bindings = ['customer'];

        if (!empty($search)) {
            $whereSql .= ' AND (name LIKE ? OR email LIKE ?)';
            $like = '%' . $search . '%';
            $bindings[] = $like;
            $bindings[] = $like;
        }

        $totalRow = DB::selectOne("SELECT COUNT(*) AS aggregate FROM users WHERE $whereSql", $bindings);
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $rows = DB::select(
            "SELECT * FROM users WHERE $whereSql ORDER BY id DESC LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );

        $customerAccounts = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('AdminPage.UserAccount', compact('customerAccounts', 'search'));
    }

    public function update($customerId)
    {
        DB::update(
            "UPDATE users SET isDeleted = 0, updated_at = NOW() WHERE id = ?",
            [$customerId]
        );

        return redirect()->route('customer.index')->with('success', 'Go cam nguoi dung thanh cong.');
    }

    public function destroy($customerId)
    {
        DB::update(
            "UPDATE users SET isDeleted = 1, updated_at = NOW() WHERE id = ?",
            [$customerId]
        );

        return redirect()->route('customer.index')->with('success', 'Cam nguoi dung thanh cong.');
    }
}
