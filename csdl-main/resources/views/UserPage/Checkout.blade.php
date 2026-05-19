<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - TrendyTeen</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="{{asset('css/customer/customerPage.css')}}">
    <script src="https://cdn.tailwindcss.com"></script>

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
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-r from-amber-50 to-orange-50">
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
<!-- Header -->
<header class="sticky top-0 z-50 bg-white shadow-sm">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="flex items-center">
            <a href="{{route('customerPage')}}" class="text-2xl font-bold text-orange-500">Trendy<span class="text-gray-800">Teen</span></a>
        </div>

        <nav class="hidden md:flex space-x-8">
            <a href="/" class="nav-link text-gray-700 hover:text-orange-500 font-medium">Trang chủ</a>
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
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-12">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Thanh toán đơn hàng</h1>

    <form action="{{ route('orders.storeFromCustomer') }}" method="POST" class="w-full">
        @csrf
        {{-- Ẩn danh sách sản phẩm trong giỏ --}}
        @foreach($cartDetails as $index => $detail)
            <input type="hidden" name="productDetails[{{ $index }}][productDetailID]" value="{{ $detail->productDetailID }}">
            <input type="hidden" name="productDetails[{{ $index }}][quantity]" value="{{ $detail->quantity }}">
            <input type="hidden" name="productDetails[{{ $index }}][unitPrice]" value="{{ $detail->productDetail?->product->productSellPrice }}">
            <input type="hidden" name="discountCode" value="{{ $discountCode }}">
        @endforeach

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Cột trái (Thông tin giao hàng, vận chuyển, thanh toán) --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Thông tin giao hàng --}}
                <div class="bg-white p-6 rounded-lg border shadow-sm">
                    <h2 class="text-xl font-semibold flex items-center gap-2 mb-6">
                        <i class="fas fa-map-marker-alt text-orange-500"></i>
                        Thông tin giao hàng
                    </h2>

                    {{-- Địa chỉ đã đăng nhập --}}


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Họ và tên</label>
                            <input type="text" name="name" value="{{ Auth::user()->name }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="Nhập họ và tên">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email *</label>
                            <input type="email" name="email" value="{{ Auth::user()->email }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="email@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Số điện thoại *</label>
                            <input type="tel" name="phone" value="{{ Auth::user()->phone }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="0123 456 789" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Địa chỉ *</label>
                            <input type="text" name="street_address" value="{{ Auth::user()->street_address }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" placeholder="Số nhà, tên đường" required>
                        </div>
                    </div>
                    <br>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố *</label>
                            <input type="text" name="city" value="{{ Auth::user()->city }}" placeholder="Nhập tỉnh/thành phố"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quận/Huyện *</label>
                            <input type="text" name="district" value="{{ Auth::user()->district }}" placeholder="Nhập quận/huyện"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phường/Xã *</label>
                            <input type="text" name="ward" value="{{ Auth::user()->ward }}" placeholder="Nhập phường/xã"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                </div>


                {{-- Phương thức thanh toán --}}
                <div class="bg-white p-6 rounded-lg border shadow-sm">
                    <h2 class="text-xl font-semibold flex items-center gap-2 mb-6">
                        <i class="fas fa-credit-card text-orange-500"></i>
                        Phương thức thanh toán
                    </h2>
                    <div class="space-y-3">
                        @foreach($payments as $payment)
                            <label class="flex items-center gap-3 border p-4 rounded-lg hover:border-orange-500 cursor-pointer transition-colors">
                                <input type="radio" name="payment" value="{{ $payment->paymentID }}" class="text-orange-500 focus:ring-orange-500" {{ $loop->first ? 'checked' : '' }}>
                                <span class="font-medium">{{ $payment->payMethod }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Cột phải (Hiển thị đơn hàng + nút đặt hàng) --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg border shadow-sm sticky top-4">
                    <div class="bg-orange-500 text-white p-6 rounded-t-lg">
                        <h2 class="text-xl font-semibold">Đơn hàng của bạn</h2>
                    </div>
                    <div class="p-6 space-y-4">

                        @foreach($cartDetails as $detail)
                            @php
                                $product = $detail->productDetail?->product;
                                $image = $product?->firstImage?->imageLink ?? 'default.jpg';
                                $size = $detail->productDetail?->size?->sizeName ?? 'N/A';
                                $color = $detail->productDetail?->color?->colorName ?? 'N/A';
                            @endphp
                            <div class="flex gap-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                    <img src="{{ asset('storage/' . $image) }}" alt="{{$product->productName}}" class="object-cover w-full h-full">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-sm truncate">{{ $product->productName }}</h4>
                                    <p class="text-xs text-gray-500">Màu: {{ $color }} | Size: {{ $size }}</p>
                                    <p class="text-sm">SL: {{ $detail->quantity }}</p>
                                </div>
                                <div class="text-orange-500 font-medium text-sm flex-shrink-0">
                                    {{ number_format($product->productSellPrice ?? 0, 0, ',', '.') }}đ
                                </div>

                            </div>
                        @endforeach

                        <hr class="border-gray-200">

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Tạm tính</span>
                                <span>{{ number_format($subtotal ?? 0, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Giảm giá</span>
                                <span>{{ number_format($discountValue ?? 0, 0, ',', '.') }}đ</span>
                            </div>
                            <hr class="border-gray-200">
                            <div class="flex justify-between font-bold text-orange-500 text-lg">
                                <span>Tổng cộng</span>
                                <span>{{ number_format(($total ?? 0) , 0, ',', '.') }}đ</span>
                                <input type="hidden" name="total" value="{{$total}}">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors">
                            <i class="fas fa-credit-card"></i>
                            <span>Đặt hàng</span>
                        </button>

                        <div class="flex justify-center items-center text-sm text-gray-500 gap-2">
                            <i class="fas fa-shield-alt"></i>
                            <span>Thanh toán an toàn & bảo mật</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
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

<script src="{{asset('js/Customer/Sidebar.js')}}"></script>
<script src="{{asset('js/Customer/customerPage.js')}}"></script>

</body>
</html>
