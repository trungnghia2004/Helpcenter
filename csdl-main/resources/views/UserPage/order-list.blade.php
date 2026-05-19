<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - TrendyTeen</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{asset('css/customer/customerPage.css')}}">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

    <style>
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

        .status-approved {
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
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Đơn hàng của tôi</h1>
        <p class="text-gray-600">Quản lý và theo dõi tất cả đơn hàng của bạn</p>
    </div>

    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3 text-gray-800">Lọc theo trạng thái đơn hàng</h3>

        <div class="flex flex-wrap gap-3">
            <form id="filterForm" method="GET" class="flex flex-wrap gap-3">
                <button type="submit"
                        class="px-4 py-2 rounded-lg border text-sm transition-all
                    {{ request('statusID') == null ? 'bg-orange-500 text-white border-orange-500 font-semibold' : 'bg-white text-gray-700 border-gray-300 hover:bg-orange-100 hover:text-orange-600' }}"
                        name="statusID" value="">
                    Tất cả
                    <span class="ml-2 bg-gray-200 text-xs text-gray-700 px-2 py-0.5 rounded-full">
                    {{ $totalOrders }}
                </span>
                </button>

                @foreach([1 => 'Đang chờ duyệt', 2 => 'Đã duyệt', 3 => 'Đang giao hàng', 4 => 'Đã giao', 5 => 'Đã hủy'] as $id => $label)
                    <button type="submit"
                            class="px-4 py-2 rounded-lg border text-sm transition-all
                        {{ request('statusID') == (string) $id ? 'bg-orange-500 text-white border-orange-500 font-semibold' : 'bg-white text-gray-700 border-gray-300 hover:bg-orange-100 hover:text-orange-600' }}"
                            name="statusID" value="{{ $id }}">
                        {{ $label }}
                        <span class="ml-2 bg-gray-200 text-xs text-gray-700 px-2 py-0.5 rounded-full">
                        {{ $statusCounts[$id] ?? 0 }}
                    </span>
                    </button>
                @endforeach
            </form>
        </div>
    </div>


    <!-- Orders List -->
    <div class="space-y-6">
        <!-- Order Card 1 -->
        @forelse ($results as $order)
            <div class="order-card bg-white rounded-lg shadow-sm border hover:shadow-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                        <div class="flex items-center gap-4 mb-2 md:mb-0">
                            <div>
                                <h3 class="font-semibold text-lg">#ORD{{ $order['orderID'] }}</h3>
                                <p class="text-sm text-gray-500">Đặt ngày: {{ $order['orderDate'] }}</p>
                            </div>
                            <span class="status-badge {{ $order['statusClass'] }}">{{ $order['status'] }}</span>
                            @if($order['payStatus'])
                                <span class="status-badge status-delivered">Đã thanh toán</span>
                            @else
                                <span class="status-badge status-pending">Chưa thanh toán</span>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-orange-500">{{ $order['totalPrice'] }}</p>
                            <p class="text-sm text-gray-500">{{ $order['productCount'] }} sản phẩm</p>
                        </div>
                    </div>

                    <div class="flex gap-4 mb-4 overflow-x-auto">
                        @foreach ($order['productImages'] as $index => $image)
                            @if ($index < 3)
                                <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                                    <img src="{{ asset('storage/' . $image) }}" alt="Product" class="w-full h-full object-cover">
                                </div>
                            @endif
                        @endforeach
                        @if (count($order['productImages']) > 3)
                            <div class="flex-shrink-0 w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                <span class="text-gray-500 text-sm">+{{ count($order['productImages']) - 3 }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center pt-4 border-t">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-truck mr-2"></i>
                            Dự kiến giao: {{ $order['expectedDelivery'] }}
                        </div>
                        <div class="flex gap-2">

                            <button class=" text-orange-500 border border-orange-500 rounded-lg hover:bg-orange-50 text-sm font-medium">
                                <a style="display:block" class="px-4 py-2" href="/order-details{{$order['orderID']}}">
                                    Xem chi tiết
                                </a>
                            </button>
                            @if($order['status'] == 'Đang giao hàng')
                                <form action="{{route('orders.delivered', $order['orderID'])}}" method="POST">
                                    @csrf
                                    <button class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-medium">
                                        Đã nhận hàng
                                    </button>
                                </form>
                            @elseif($order['status'] == 'Đang chờ duyệt' || $order['status'] == 'Đã duyệt')
                                <form method="POST" action="{{ route('orderCus.cancel', $order['orderID']) }}" class="cancel-order-form">
                                    @csrf
                                    <button
                                        type="button"
                                        class="cancel-order-btn px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-medium"
                                        data-url="{{ route('orderCus.cancel', $order['orderID']) }}">
                                        Hủy đơn hàng
                                    </button>
                                </form>

                            @elseif($order['status'] == 'Đã giao hàng')
                                <form>
                                @csrf
                                <button class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-medium">
                                    <a href="{{ route('productDetails', ['product' => $order['product']->productID]) }}">
                                        Bình luận và đánh giá
                                    </a>
                                </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p>Không có đơn hàng nào cho "{{ $keyword }}"</p>
        @endforelse

    </div>

    <!-- Pagination -->
    <div class="flex justify-center items-center mt-12 space-x-2">
        @php
            $currentPage = $orders->currentPage();
            $lastPage = $orders->lastPage();
        @endphp

        <div class="flex items-center justify-center space-x-2 mt-6">
            {{-- Previous Page --}}
            @if ($currentPage > 1)
                <a href="{{ $orders->previousPageUrl() }}" class="px-3 py-2 text-gray-500 hover:text-orange-500">
                    <i class="fas fa-chevron-left"></i>
                </a>
            @else
                <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
            <i class="fas fa-chevron-left"></i>
        </span>
            @endif

            {{-- Page Numbers --}}
            @for ($i = 1; $i <= $lastPage; $i++)
                @if ($i == $currentPage)
                    <span class="px-4 py-2 bg-orange-500 text-white rounded-lg">{{ $i }}</span>
                @elseif ($i == 1 || $i == $lastPage || ($i >= $currentPage - 1 && $i <= $currentPage + 1))
                    <a href="{{ $orders->url($i) }}" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">{{ $i }}</a>
                @elseif ($i == $currentPage - 2 || $i == $currentPage + 2)
                    <span class="px-2 text-gray-500">...</span>
                @endif
            @endfor

            {{-- Next Page --}}
            @if ($currentPage < $lastPage)
                <a href="{{ $orders->nextPageUrl() }}" class="px-3 py-2 text-gray-500 hover:text-orange-500">
                    <i class="fas fa-chevron-right"></i>
                </a>
            @else
                <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
            <i class="fas fa-chevron-right"></i>
        </span>
            @endif
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
                        <span>123 Đường ABC, Quận 1, TP.HCM</span>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.cancel-order-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;

                Swal.fire({
                    title: 'Xác nhận huỷ đơn hàng?',
                    text: "Hành động này không thể hoàn tác.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e53e3e',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Xác nhận huỷ',
                    cancelButtonText: 'Thoát',
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Tạo và submit form ẩn
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';

                        form.appendChild(csrf);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
</script>

<script src="{{asset('js/Customer/Sidebar.js')}}"></script>
<script src="{{asset('js/Customer/customerPage.js')}}"></script>
</body>
</html>
