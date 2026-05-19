<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendyTeen - Thời trang trẻ trung năng động</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="{{asset('css/customer/customerPage.css')}}">
    <link rel="stylesheet" href="{{ asset('chat-widget.css') }}">
    <script defer src="{{ asset('chat-widget.js') }}"></script>
</head>
<body class="font-sans bg-gray-50">
<!-- Header -->
<div class="left">
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
</div>
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

<!-- Hero Section -->
<section class="gradient-bg text-white">
    <div class="container mx-auto px-4 py-16 md:py-24 flex flex-col md:flex-row items-center">
        <div class="md:w-1/2 mb-10 md:mb-0">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Phong cách của bạn, <span class="text-gray-800">Quy tắc của bạn</span></h1>
            <p class="text-lg mb-8">Khám phá bộ sưu tập mới nhất với những thiết kế trẻ trung, năng động dành cho giới trẻ hiện đại.</p>
            <div class="flex space-x-4">
                <a href="{{route('showProduct')}}" class="block bg-white text-orange-500 px-6 py-3 rounded-full font-medium hover:bg-gray-100 transition">
                    Mua ngay
                </a>
            </div>
        </div>
        <div class="md:w-1/2 flex justify-center">
            <img src="https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="Thời trang trẻ" class="rounded-lg shadow-xl float-animation w-full max-w-md">
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Danh mục sản phẩm</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($categories as $category )
                <a href="/products/category/{{$category->categoryID}}" class="group relative overflow-hidden rounded-xl h-48">
                    <img src="{{asset('storage/' . $category->categoryImage)}}" alt="{{ $category->categoryName }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center group-hover:bg-opacity-40 transition">
                        <h3 class="text-white text-xl font-bold">{{ $category->categoryName }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">Sản phẩm nổi bật</h2>
            <a href="/showProduct" class="text-orange-500 font-medium hover:underline">Xem tất cả</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Product 1 -->
            @foreach($products as $product)
            <div class="bg-white rounded-xl overflow-hidden shadow-md product-card transition duration-300">
                <div class="relative">
                    <a href="{{ route('productDetails', ['product' => $product->productID]) }}">
                        <img src="{{ asset('storage/' . $product->firstImage->imageLink) }}" alt="{{$product->productName}}" class="w-full h-64 object-cover">
                    </a>
                    <div class="absolute top-3 right-3 bg-orange-500 text-white text-xs px-2 py-1 rounded-full">Mới</div>
                </div>
                <div class="p-4">
                    <a href="{{ route('productDetails', ['product' => $product->productID]) }}">
                        <h3 class="font-semibold text-lg mb-1">{{$product->productName}}</h3>
                    </a>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            @for ($i = 0; $i < number_format($product->average_star) ; $i++)
                                <i class="fas fa-star text-warning"></i>
                            @endfor
                            @for ($i = number_format($product->average_star); $i < 5; $i++)
                                <i class="far fa-star text-muted"></i>
                            @endfor
                        </div>
                        <span class="text-gray-500 text-sm ml-2">({{$product->quantityComment}} đánh giá)</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-orange-500 font-bold">{{number_format($product->productSellPrice, 0, ',', '.')}}đ</span>
{{--                        <span class="text-gray-400 text-sm line-through">299.000đ</span>--}}
                    </div>
                </div>
            </div>
            @endforeach
           </div>
    </div>

</section>

<!-- New Arrivals -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">Hàng mới về</h2>
            <div class="flex space-x-2">
                <button class="new-arrival-prev bg-gray-200 p-2 rounded-full hover:bg-gray-300">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="new-arrival-next bg-gray-200 p-2 rounded-full hover:bg-gray-300">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div class="new-arrival-slider flex transition-transform duration-300">
                @foreach($products as $product)
                <!-- Slide 1 -->
                <div class="min-w-full md:min-w-1/2 lg:min-w-1/3 xl:min-w-1/4 px-2">
                    <div class="bg-gray-50 rounded-xl overflow-hidden shadow-sm">
                        <a href="{{ route('productDetails', ['product' => $product->productID]) }}">
                            <img src="{{ asset('storage/' . $product->firstImage->imageLink) }}" alt="Áo hoodie" class="w-full h-64 object-cover">
                        </a>
                        <div class="p-4">
                            <a style="text-decoration: none; color: black" href="{{ route('productDetails', ['product' => $product->productID]) }}">
                                <h3 class="font-semibold mb-2">{{$product->productName}}</h3>
                            </a>
                            <div class="flex justify-between items-center">
                                <span class="text-orange-500 font-bold">{{number_format($product->productSellPrice, 0, ',', '.')}}đ</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Khách hàng nói gì về chúng tôi</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($comments as $comment)
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <div class="flex items-center mb-4">
                            <div class="ml-4">
                                <h4 class="font-semibold">{{$comment->user->name}}</h4>
                                <div class="flex text-yellow-400 text-sm">
                                    @for ($i = 0; $i < number_format($comment->rate) ; $i++)
                                        <i class="fas fa-star text-warning"></i>
                                    @endfor
                                    @for ($i = number_format($comment->rate); $i < 5; $i++)
                                        <i class="far fa-star text-muted"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 ml-4">{{$comment->contentComment}}</p>
                    </div>
                @empty
                @endforelse
            </div>

    </div>
</section>


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

<script src="{{asset('js/Customer/customerPage.js')}}"></script>
<script src="{{asset('js/Customer/Sidebar.js')}}"></script>
</body>
</html>
