<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - TrendyTeen</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{asset('css/customer/customerPage.css')}}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                        amber: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-r from-amber-200 to-orange-200">
@if(session()->has('success'))
    <script>
        toastr.options = {
            "progressBar": true,
            "closeButton": true
        }
        toastr.success("{{ session()->get('success') }}","Thành công!", {timeOut:5000});
    </script>
@endif
@if(session()->has('error'))
    <script>
        toastr.options = {
            "progressBar": true,
            "closeButton": true
        }
        toastr.error("{{ session()->get('error')}}","Lỗi!", {timeOut:5000});
    </script>
@endif
@if(session()->has('warn'))
    <script>
        toastr.options = {
            "progressBar": true,
            "closeButton": true
        }
        toastr.warning("{{ session()->get('warn')}}","Thông báo!", {timeOut:5000});
    </script>
@endif
<header class="sticky top-0 z-50 bg-white shadow-sm">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="flex items-center">
            <a href="{{route('customerPage')}}" class="text-2xl font-bold text-orange-500">Trendy<span class="text-gray-800">Teen</span></a>
        </div>

        <nav class="hidden md:flex space-x-8">
            <a href="/" class="nav-link text-gray-700 hover:text-orange-500 font-medium">Trang chủ</a>
            <a href="/products/gender/0" class="nav-link text-gray-700 hover:text-orange-500 font-medium">Nam</a>
            <a href="/products/gender/1" class="nav-link text-gray-700 hover:text-orange-500 font-medium">Nữ</a>
            <a href="/showProduct" class="nav-link text-gray-700 hover:text-orange-500 font-medium">Sản phẩm</a>
        </nav>

        <div class="flex items-center space-x-4">
            <div class="flex search">
                <div class="">
                    <form action="/showProduct">
                        <div class="search-box">
                            <input name="search" placeholder="Search something" class="search_content" type="text">
                            <button style="display: none" type="submit"></button>
                        </div>
                    </form>
                </div>
                <div class="p-2 text-gray-600 hover:text-orange-500 relative">
                    <i class="search_icon fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <button class="p-2 text-gray-600 hover:text-orange-500 relative">
                <a href="{{route('cart.index')}}">
                    <i class="fas fa-shopping-cart text-lg"></i>
                </a>
            </button>
            <button class="md:hidden p-2 text-gray-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="mr-3">
                @if(Auth::check())
                    <div class="flex items-center gap-1 cursor-pointer show_logout">
                        <div class="flex items-center space-x-2">
                            <span class="font-semibold text-blue-600">
                                {{ Auth::user()->username }}
                            </span>
                            <i id="dropDownTeacher" class="fa-solid fa-caret-down text-lg"></i>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Đăng nhập</a>
                @endif

                <!-- Dropdown menu -->
                <div class="absolute right-0 mt-2 min-w-[160px] bg-white shadow-lg rounded-md border z-50 drop_down drop_downActive transition-all duration-200">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-red-100 text-red-600 flex items-center gap-2">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            Đăng xuất
                        </button>
                    </form>
                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                        <a class="text-decoration: none; color: black" href="{{route('profile.edit')}}">
                            <i class="fa-solid fa-user"></i>
                            Trang cá nhân
                        </a>
                    </button>
                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                        <a class="text-decoration: none; color: black" href="{{route('orders.showOrders')}}">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Đơn hàng
                        </a>
                    </button>
                </div>
            </div>




        </div>
    </div>
</header>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center mb-6 text-gray-600">
            <a href="/" class="hover:text-orange-500">Trang chủ</a>
            <i class="fas fa-chevron-right h-4 w-4 mx-2"></i>
            <span class="text-gray-900 font-medium">Thông tin cá nhân</span>
        </div>

        <h1 class="text-3xl font-bold mb-8 text-gray-800">Thông tin cá nhân</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Sidebar -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-400 to-amber-300 px-4 py-3">
                        <h2 class="text-white font-semibold flex items-center">
                            <i class="fas fa-user mr-2"></i>
                            Tài khoản
                        </h2>
                    </div>
                    <nav class="flex flex-col">
                        <a href="/profile" class="px-4 py-3 border-b border-gray-100 text-orange-500 font-medium flex items-center">
                            <i class="fas fa-user mr-2"></i>
                            Thông tin cá nhân
                        </a>
                        <a href="{{route('orders.showOrders')}}" class="px-4 py-3 border-b border-gray-100 text-gray-700 hover:text-orange-500 flex items-center">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Đơn hàng của tôi
                        </a>
                        <a href="/logout" class="px-4 py-3 text-gray-700 hover:text-orange-500 flex items-center">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Đăng xuất
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Form -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-400 to-amber-300 px-4 py-3">
                        <h2 class="text-white font-semibold">Thông tin cá nhân</h2>
                    </div>
                    <div class="p-6">
                        <!-- Profile Form -->
                        <div id="profileForm" class="space-y-4">
                            <form action="{{route('profile.update')}}" method="POST">
                                @csrf

                                <div class="grid grid-cols-1 gap-4">
                                    <div class="space-y-2">
                                        <label for="name" class="block text-sm font-medium text-gray-700">Họ và tên</label>
                                        <input type="text" id="name" name="name" value="{{$user->name}}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    </div>

                                    <div class="space-y-2">
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" id="email" name="email" value="{{$user->email}}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    </div>

                                    <div class="space-y-2">
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                        <input type="text" id="phone" name="phone" value="{{$user->phone}}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    </div>

                                    <div class="space-y-2">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố *</label>
                                                <input type="text" name="city" value="{{ $user->city }}" placeholder="Nhập tỉnh/thành phố"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Quận/Huyện *</label>
                                                <input type="text" name="district" value="{{ $user->district }}" placeholder="Nhập quận/huyện"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Phường/Xã *</label>
                                                <input type="text" name="ward" value="{{ $user->ward }}" placeholder="Nhập phường/xã"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            </div>
                                        </div>

                                        <label for="address" class="block text-sm font-medium text-gray-700">Địa chỉ</label>

                                        <textarea id="address" name="address" rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">{{$user->street_address}}</textarea>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                                    <button type="submit" id="saveProfileBtn" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-md flex items-center justify-center">
                                        <i class="fas fa-save mr-2"></i>
                                        Lưu thông tin
                                    </button>

                                    <button type="button" id="changePasswordBtn" class="px-4 py-2 border border-orange-500 text-orange-500 hover:bg-orange-50 font-medium rounded-md flex items-center justify-center">
                                        <i class="fas fa-lock mr-2"></i>
                                        Đổi mật khẩu
                                    </button>

                                    <a href="/" class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-md flex items-center justify-center">
                                        <i class="fas fa-home mr-2"></i>
                                        Quay lại trang chủ
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Password Form (hidden by default) -->
                        <div id="passwordForm" class="space-y-4 hidden">
                            <form action="{{route('profile.updatePassword')}}" onsubmit="return validatePassword()" method="POST" >
                                @csrf
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="space-y-2">
                                        <label for="currentPassword" class="block text-sm font-medium text-gray-700">Mật khẩu hiện tại</label>
                                        <input type="password" id="currentPassword" name="currentPassword"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    </div>

                                    <div class="space-y-2">
                                        <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu mới</label>
                                        <input type="password" id="password" name="password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    </div>

                                    <div class="space-y-2">
                                        <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Xác nhận mật khẩu mới</label>
                                        <input type="password" id="confirmPassword" name="password_confirmation"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                                    <button type="submit" id="savePasswordBtn" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-md flex items-center justify-center">
                                        <i class="fas fa-save mr-2"></i>
                                        Lưu mật khẩu
                                    </button>

                                    <button type="button" id="backToProfileBtn" class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-md flex items-center justify-center">
                                        Quay lại
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<footer class="bg-gray-900 text-white pt-12 pb-6">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
            <!-- Column 1 -->
            <div>
                <h3 class="text-xl font-bold mb-4">TrendyTeen</h3>
                <p class="text-gray-400 mb-4">Thương hiệu thời trang trẻ trung, năng động dành cho giới trẻ Việt Nam.</p>
            </div>

            <!-- Column 2 -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Mua sắm</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white">Sản phẩm mới</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Bán chạy nhất</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Khuyến mãi</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Bộ sưu tập</a></li>
                </ul>
            </div>

            <!-- Column 3 -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Thông tin</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white">Về chúng tôi</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Chính sách bảo mật</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Điều khoản dịch vụ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Hướng dẫn mua hàng</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Liên hệ</h3>
                <ul class="space-y-2 text-gray-400">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt mt-1 mr-3"></i>
                        <span>123 Đường Láng, Đống Đa, TP.HN</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-phone-alt mr-3"></i>
                        <span>0909 123 456</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-envelope mr-3"></i>
                        <span>support@trendyteen.com</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center">
            <div class="flex space-x-6">

            </div>
        </div>
    </div>
</footer>


<script src="{{asset('js/Customer/Profie.js')}}"></script>
<script src="{{asset('js/Customer/Sidebar.js')}}"></script>
<script>
    function isStrongPassword(password) {
        const minLength = 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        return password.length >= minLength && hasUpperCase && hasSpecialChar;
    }

    function validatePassword() {
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        const messageBox = document.getElementById("message-box");
        const messageText = document.getElementById("message-text");
        const messageIcon = document.getElementById("message-icon");

        if (password.length < 8) {
            messageBox.classList.remove("valid");
            messageText.textContent = "Mật khẩu cần tối thiểu 8 ký tự!";
            messageIcon.className = "bx bx-error-circle";
            return false;
        }

        if (!/[A-Z]/.test(password)) {
            messageBox.classList.remove("valid");
            messageText.textContent = "Mật khẩu phải có ít nhất 1 chữ viết hoa!";
            messageIcon.className = "bx bx-error-circle";
            return false;
        }

        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            messageBox.classList.remove("valid");
            messageText.textContent = "Mật khẩu phải có ít nhất 1 ký tự đặc biệt!";
            messageIcon.className = "bx bx-error-circle";
            return false;
        }

        if (password !== confirmPassword) {
            messageBox.classList.remove("valid");
            messageText.textContent = "Mật khẩu không khớp!";
            messageIcon.className = "bx bx-error-circle";
            return false;
        }

        messageBox.classList.add("valid");
        messageText.textContent = "Mật khẩu hợp lệ.";
        messageIcon.className = "bx bx-check-circle";
        return true;
    }

    document.getElementById("confirmPassword").addEventListener("input", validatePassword);
    document.getElementById("password").addEventListener("input", validatePassword);
</script>
</body>
</html>
