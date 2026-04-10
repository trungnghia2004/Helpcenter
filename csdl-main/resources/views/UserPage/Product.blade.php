<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - TrendyTeen</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
<!-- Main Content -->
<main class="container mx-auto px-4 py-8">
    <div class="flex items-center mb-6 text-gray-600">
        <a href="/" class="hover:text-orange-500">Trang chủ</a>
        <i class="fas fa-chevron-right h-4 w-4 mx-2"></i>
        <span class="text-gray-900 font-medium">Sản phẩm</span>
    </div>

    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Filters -->
        <div class="w-full md:w-1/4 lg:w-1/5">
            <div class="bg-white rounded-lg shadow-md p-5 mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Danh mục</h3>
                <ul class="space-y-2">


                    @foreach($categories as $category)
                        <li>
                            <a href="{{ route('products.byCategory', ['cateID' => $category->categoryID]) }}" class="text-gray-700 hover:text-orange-500 flex items-center">
                                <span class="mr-2">{{ $category->categoryName }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <form method="GET" action="{{ route('products.findByPrice') }}">
                <div class="bg-white rounded-lg shadow-md p-5 mb-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Lọc theo giá</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-600">Giá thấp nhất</span>
                                <span class="text-sm text-gray-600" id="minPriceValue">0đ</span>
                            </div>
                            <input type="range" min="0" max="2000000" value="{{ request('min_price', 0) }}" step="50000" name="min_price" id="minPrice"
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        </div>
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-600">Giá cao nhất</span>
                                <span class="text-sm text-gray-600" id="maxPriceValue">2.000.000đ</span>
                            </div>
                            <input type="range" min="0" max="2000000" value="{{ request('max_price', 2000000) }}" step="50000" name="max_price" id="maxPrice"
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        </div>
                        <button type="submit" class="w-full py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-md mt-4">
                            Áp dụng
                        </button>
                    </div>
                </div>
            </form>


        </div>

        <!-- Product Grid -->
        <div class="w-full md:w-3/4 lg:w-4/5">
            <div class="bg-white rounded-lg shadow-md p-5 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2 sm:mb-0">Sản phẩm</h2>

                </div>
                <p class="text-gray-600 mb-4">Hiển thị 1-8 của 38 sản phẩm</p>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($products as $product)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <div class="relative">
                            <a href="{{ route('productDetails', ['product' => $product->productID]) }}">
                                <img src="{{ asset('storage/' . $product->firstImage->imageLink) }}" alt="" class="w-full h-64 object-cover">
                            </a>
{{--                            <div class="absolute top-2 right-2 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded">-20%</div>--}}
                        </div>
                        <div class="p-4">
                            <div class="flex text-amber-400 mb-1">
                                @for ($i = 0; $i < number_format($product->average_star) ; $i++)
                                    <i class="fas fa-star text-warning"></i>
                                @endfor
                                @for ($i = number_format($product->average_star); $i < 5; $i++)
                                    <i class="far fa-star text-muted"></i>
                                @endfor
                                <span class="text-gray-500 text-sm ml-2">({{$product->quantityComment}} đánh giá)</span>

                            </div>

                            <h3 class="text-gray-800 font-medium mb-2">
                                <a href="{{ route('productDetails', ['product' => $product->productID]) }}" class="hover:text-orange-500">{{$product->productName}}</a>
                            </h3>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-orange-500 font-bold">{{number_format($product->productSellPrice, 0, ',', '.')}}đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="flex flex-col md:flex-row justify-between items-center mt-8 gap-4">
                    <div class="text-sm text-gray-600">
                        Trang {{ $products->currentPage() }} / {{ $products->lastPage() }}
                    </div>

                    <nav class="inline-flex rounded-md shadow-sm">
                        {{-- Nút "Previous" --}}
                        @if($products->onFirstPage())
                            <span class="px-3 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </span>
                        @else
                            <a href="{{ $products->previousPageUrl() }}" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        @endif

                        @for($i = 1; $i <= $products->lastPage(); $i++)
                            @if($i == $products->currentPage())
                                <span class="px-3 py-2 border-t border-b border-gray-300 bg-orange-500 text-white">{{ $i }}</span>
                            @else
                                <a href="{{ $products->url($i) }}" class="px-3 py-2 border-t border-b border-gray-300 bg-white text-gray-700 hover:bg-gray-50">{{ $i }}</a>
                            @endif
                        @endfor

                        @if($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="px-3 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </span>
                        @endif
                    </nav>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- Footer -->
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
<script>
    const minInput = document.getElementById('minPrice');
    const maxInput = document.getElementById('maxPrice');
    const minDisplay = document.getElementById('minPriceValue');
    const maxDisplay = document.getElementById('maxPriceValue');

    function formatCurrency(value) {
        return parseInt(value).toLocaleString('vi-VN') + 'đ';
    }

    minInput.addEventListener('input', () => {
        minDisplay.textContent = formatCurrency(minInput.value);
    });

    maxInput.addEventListener('input', () => {
        maxDisplay.textContent = formatCurrency(maxInput.value);
    });

    // Khởi tạo lại khi load
    minDisplay.textContent = formatCurrency(minInput.value);
    maxDisplay.textContent = formatCurrency(maxInput.value);
</script>

<script>
    const minPriceSlider = document.getElementById('minPrice');
    const maxPriceSlider = document.getElementById('maxPrice');
    const minPriceValue = document.getElementById('minPriceValue');
    const maxPriceValue = document.getElementById('maxPriceValue');

    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    }

    minPriceSlider.addEventListener('input', function() {
        minPriceValue.textContent = formatPrice(this.value);
        // Ensure min price doesn't exceed max price
        if (parseInt(this.value) > parseInt(maxPriceSlider.value)) {
            maxPriceSlider.value = this.value;
            maxPriceValue.textContent = formatPrice(this.value);
        }
    });

    maxPriceSlider.addEventListener('input', function() {
        maxPriceValue.textContent = formatPrice(this.value);
        // Ensure max price doesn't go below min price
        if (parseInt(this.value) < parseInt(minPriceSlider.value)) {
            minPriceSlider.value = this.value;
            minPriceValue.textContent = formatPrice(this.value);
        }
    });


</script>
<script src="{{asset('js/Customer/Sidebar.js')}}"></script>

</body>
</html>
