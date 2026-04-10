<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng - TrendyTeen</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{asset('css/customer/customerPage.css')}}">

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
    <style>
        .order-progress-bar {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 30px;
        }

        .order-progress-bar::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: #e5e7eb;
            z-index: 1;
        }

        .progress-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 20%;
        }

        .step-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background-color: white;
            border: 4px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            color: #9ca3af;
            font-size: 14px;
        }

        .step-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-align: center;
        }

        .progress-step.active .step-icon {
            background-color: #f97316;
            border-color: #f97316;
            color: white;
        }

        .progress-step.active .step-label {
            color: #f97316;
            font-weight: 600;
        }

        .progress-step.completed .step-icon {
            background-color: #10b981;
            border-color: #10b981;
            color: white;
        }

        .progress-step.completed .step-label {
            color: #10b981;
        }

        .progress-fill {
            position: absolute;
            top: 15px;
            left: 0;
            height: 4px;
            background-color: #10b981;
            z-index: 1;
            transition: width 0.5s ease;
        }
         .order-card {
             transition: all 0.3s ease;
         }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-confirmed {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .status-processing {
            background-color: #e0e7ff;
            color: #7c3aed;
        }

        .status-shipping {
            background-color: #fef3c7;
            color: #f59e0b;
        }

        .status-delivered {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .filter-tab {
            transition: all 0.3s ease;
        }

        .filter-tab.active {
            background-color: #f97316;
            color: white;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 40px;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-r from-amber-50 to-orange-50">
<!-- Header -->
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-12">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Chi tiết đơn hàng</h1>
        <a href="/order-list" class="text-orange-500 hover:text-orange-600 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            Quay lại đơn hàng của tôi
        </a>
    </div>

    <!-- Order Status Banner -->
    <div class="bg-white p-6 rounded-lg border shadow-sm mb-8">

        @php
            $steps = ['Đặt hàng', 'Đang chờ duyệt', 'Đã duyệt', 'Đang giao hàng', 'Đã giao hàng'];
            $cancel = ['Đã hủy'];

            $currentStatus = $order->status->statusValue ?? 'Đang chờ duyệt';
            $isCancelled = in_array($currentStatus, $cancel);

            $currentStepIndex = array_search($currentStatus, $steps);
            $currentStepIndex = $currentStepIndex !== false ? $currentStepIndex : 0;
            $progressPercent = ($currentStepIndex / (count($steps) - 1)) * 100;
        @endphp

            <!-- Order Progress Bar -->
        <div class="relative flex items-center justify-between mb-6">
            <!-- Progress line -->
            <div class="absolute top-1/2 left-0 w-full h-1 {{ $isCancelled ? 'bg-gray-400' : 'bg-gray-200' }} transform -translate-y-1/2 z-0 rounded-full"></div>

            <div class="absolute top-1/2 left-0 h-1 z-10 rounded-full
        {{ $isCancelled ? 'bg-gray-500' : 'bg-green-500' }}"
                 style="width: {{ $isCancelled ? '100%' : $progressPercent . '%' }}; transform: translateY(-50%);">
            </div>

            @foreach ($steps as $index => $step)
                @php
                    $isCompleted = $index < $currentStepIndex;
                    $isCurrent = $index === $currentStepIndex;
                @endphp

                <div class="relative z-20 flex flex-col items-center w-1/5 text-center">
                    <div class="w-8 h-8 flex items-center justify-center rounded-full
                @if($isCancelled)
                    bg-gray-400 text-white
                @elseif($isCompleted)
                    bg-green-500 text-white
                @elseif($isCurrent)
                    bg-blue-500 text-white
                @else
                    bg-gray-300 text-gray-600
                @endif">
                        @if($isCancelled)
                            <i class="fas fa-times text-sm"></i>
                        @elseif($isCompleted)
                            <i class="fas fa-check text-sm"></i>
                        @elseif($isCurrent)
                            <i class="fas fa-spinner fa-spin text-sm"></i>
                        @else
                            <i class="fas fa-circle text-xs"></i>
                        @endif
                    </div>
                    <div class="mt-2 text-xs {{ $isCancelled ? 'text-gray-500' : 'text-gray-700' }}">{{ $step }}</div>
                </div>
            @endforeach
        </div>



        <div class="text-sm text-gray-500 text-center">
            @if($order->status->statusValue == 'Đã hủy')
                Đơn hàng của bạn đã hủy
            @else
                Đơn hàng của bạn đang được chuẩn bị và sẽ được giao trong 3-5 ngày tới
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cột trái (Thông tin đơn hàng) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Sản phẩm đã đặt -->
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h2 class="text-xl font-semibold flex items-center gap-2 mb-6">
                    <i class="fas fa-shopping-bag text-orange-500"></i>
                    Sản phẩm đã đặt
                </h2>

                <div class="space-y-6">
                    @foreach ($order->orderDetails as $detail)
                        @php
                            $product = $detail->productDetail->product ?? null;
                            $color = $detail->productDetail->color->colorName ?? '';
                            $size = $detail->productDetail->size->sizeName ?? '';
                            $image = $product?->firstImage?->imageLink ?? 'default.jpg';
                        @endphp

                        <div class="flex gap-4 pb-4 border-b">
                            <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                <img src="{{ asset('storage/' . $image) }}" alt="{{ $product->productName }}" class="object-cover w-full h-full">
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium">{{ $product->productName }}</h3>
                                <div class="text-sm text-gray-500 mt-1">Màu: {{ $color }} | Size: {{ $size }}</div>
                                <div class="flex justify-between items-center mt-2">
                                    <div class="text-sm">SL: {{ $detail->orderQuantity }}</div>
                                    <div class="font-medium text-orange-500">{{ number_format($detail->unitPrice, 0, ',', '.') }}đ</div>
                                </div>
                            </div>
                        </div>
                    @endforeach


                </div>
            </div>

            <!-- Thông tin giao hàng -->
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h2 class="text-xl font-semibold flex items-center gap-2 mb-6">
                    <i class="fas fa-map-marker-alt text-orange-500"></i>
                    Thông tin giao hàng
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium mb-2">Địa chỉ giao hàng</h3>
                        <div class="text-gray-700">
                            <p class="font-medium">{{ $order->customer->name }}</p>
                            <p>{{ $order->shipping_street }}</p>
                            <p>{{ $order->shipping_ward }}, {{ $order->shipping_district }}</p>
                            <p>{{ $order->shipping_city }}</p>
                            <p class="mt-2">Điện thoại: {{ $order->orderPhoneNumber }}</p>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-medium mb-2">Phương thức vận chuyển</h3>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-truck text-orange-500"></i>
                            <div>
                                <p class="font-medium">Giao hàng tiêu chuẩn</p>
                                <p class="text-sm text-gray-500">Dự kiến giao: 18/06/2023 - 20/06/2023</p>
                            </div>
                        </div>
                        @if($order->shipping_code != null)
                            <div class="flex items-center gap-3 p-3 rounded-lg">
                                <div>
                                    <p class="font-medium">Mã vận đơn</p>
                                    <a href="https://spx.vn/track?{{$order->shipping_code}}">{{$order->shipping_code}}</a>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải (Tóm tắt đơn hàng) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border shadow-sm sticky top-20">
                <div class="bg-orange-500 text-white p-6 rounded-t-lg">
                    <h2 class="text-xl font-semibold">Tóm tắt đơn hàng</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-3 text-sm">
                        @php
                            $subtotal = $order->orderDetails->sum(fn($item) => $item->orderQuantity * $item->unitPrice);

                            $discountAmount = 0;
                            if ($order->discount) {
                                $discountAmount = $order->discount->calculateDiscount($subtotal);
                            }

                            $finalTotal = $subtotal - $discountAmount;
                        @endphp
                        <div class="flex justify-between">
                            <span>Tạm tính ({{ $order->orderDetails->sum('orderQuantity') }} sản phẩm)</span>
                            <span>{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Giảm giá</span>
                            <span >
                                {{ number_format($discountAmount, 0, ',', '.') }}đ
                            </span>

                        </div>
                        <hr class="border-gray-200">
                        <div class="flex justify-between font-bold text-orange-500 text-lg">
                            <span>Tổng cộng</span>
                            <span >
                                {{ number_format($finalTotal, 0, ',', '.') }}đ
                            </span>
                        </div>
                    </div>

                    <div class="pt-4 border-t">
                        <h3 class="font-medium mb-2">Phương thức thanh toán</h3>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-money-bill-wave text-green-500"></i>
                            <div>
                                <p class="font-medium">{{ $order->payment->payMethod ?? 'Chưa xác định' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 border-t">
                        <h3 class="font-medium mb-2">Trạng thái thanh toán</h3>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium">
                                    @if($order->isPayment)
                                        <span class="status-badge status-delivered">Đã thanh toán</span>
                                    @else
                                        <span class="status-badge status-confirmed">Chưa thanh toán</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 space-y-3">
                        <a href="#" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors">
                            <i class="fas fa-headset"></i>
                            <span>Liên hệ hỗ trợ</span>
                        </a>

                        <a href="#" class="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors">
                            <i class="fas fa-print"></i>
                            <span>In đơn hàng</span>
                        </a>
                    </div>

                    <div class="flex justify-center items-center text-sm text-gray-500 gap-2 mt-4">
                        <i class="fas fa-shield-alt"></i>
                        <span>Đơn hàng được bảo vệ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-gray-900 text-white pt-12 pb-6">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
            <!-- Column 1 -->
            <div>
                <h3 class="text-xl font-bold mb-4">TrendyTeen</h3>
                <p class="text-gray-400 mb-4">Thương hiệu thời trang trẻ trung, năng động dành cho giới trẻ Việt Nam.</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-tiktok"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-youtube"></i></a>
                </div>
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
