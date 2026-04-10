<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('UserPage.Profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'phone'   => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::update(
            'UPDATE users
             SET name = ?, email = ?, phone = ?, city = ?, district = ?, ward = ?, street_address = ?, updated_at = ?
             WHERE id = ?',
            [
                $request->name,
                $request->email,
                $request->phone,
                $request->city,
                $request->district,
                $request->ward,
                $request->street_address,
                now(),
                $user->id,
            ]
        );

        return redirect()->route('profile.edit')->with('success', 'Cập nhật thông tin thành công!');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (Hash::check($request->currentPassword, $user->password)) {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            DB::update(
                'UPDATE users SET password = ?, updated_at = ? WHERE id = ?',
                [
                    Hash::make($request->password),
                    now(),
                    $user->id,
                ]
            );

            return redirect()->route('profile.edit')->with('success', 'Cập nhật thông tin thành công!');
        } else {
            return redirect()->route('profile.edit')->with('error', 'Mật khẩu hiện tại của bạn nhập chưa chính xác!');
        }


    }
}
