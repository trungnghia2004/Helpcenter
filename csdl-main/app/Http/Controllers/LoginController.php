<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function viewLoginAndRegister()
    {
        return view('loginAndRes');
    }

    public function login(Request $request)
    {
        $username = $request->get('username');
        $password = $request->get('password');

        // Lấy user bằng SQL thuần
        $user = DB::selectOne(
            "SELECT * FROM users WHERE username = ? LIMIT 1",
            [$username]
        );

        if ($user && Hash::check($password, $user->password)) {
            Auth::loginUsingId($user->id);
            switch ($user->role) {
                case 'admin':
                    return redirect()->route('adminDashboard')->with('success', 'Đăng nhập thành công');
                case 'customer':
                default:
                    if ($user->isDeleted == 1) {
                        Auth::logout();
                        return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa hành vi mua bán không trong sạch');
                    }
                    return redirect()->route('customerPage')->with('success', 'Đăng nhập thành công');
            }
        }

        return redirect()->route('login')->with('error', 'Sai tên tài khoản hoặc mật khẩu, vui lòng nhập lại.');
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|unique:users,username',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $now = now();
            DB::insert(
                "INSERT INTO users (username, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $request->input('username'),
                    $request->input('email'),
                    Hash::make($request->input('password')),
                    'customer',
                    $now,
                    $now,
                ]
            );

            $newUserId = DB::getPdo()->lastInsertId();
            Auth::loginUsingId($newUserId);

            return redirect()->route('customerPage')->with('success', 'Tạo tài khoản thành công');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors();
            if ($errors->has('username')) {
                return redirect()->route('login')->with('error', 'Username đã tồn tại');
            }
            if ($errors->has('email')) {
                return redirect()->route('login')->with('error', 'Email đã tồn tại');
            }

            return redirect()->route('login')->with('error', 'Dữ liệu không hợp lệ');
        } catch (\Exception $e) {
            Log::error('Đăng ký thất bại: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Đã xảy ra lỗi: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        Auth::logout();
        return view('loginAndRes');
    }
}
