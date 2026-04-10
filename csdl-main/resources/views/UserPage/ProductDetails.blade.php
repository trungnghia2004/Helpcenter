<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Sản Phẩm - TrendyTeen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{asset('css/customer/customerPage.css')}}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    <!-- Breadcrumb -->
    <div class="flex items-center mb-6 text-gray-600">
        <a href="/" class="hover:text-orange-500">Trang chủ</a>
        <i class="fas fa-chevron-right h-4 w-4 mx-2"></i>
        <a href="/showProduct" class="hover:text-orange-500">Sản phẩm</a>
        <i class="fas fa-chevron-right h-4 w-4 mx-2"></i>
        <span class="text-gray-900 font-medium">{{ $product->productName }}</span>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="w-full md:w-1/2">
                <div class="mb-4">
                    <div class="relative aspect-square overflow-hidden rounded-lg border">
                        <img
                            id="main-image"
                            src="{{ asset('storage/' . $product->images[0]->imageLink) }}"
                            alt="{{ $product->productName }}"
                            class="w-full h-full object-cover"
                        >
                        <button id="prev-btn" class="absolute top-1/2 left-2 transform -translate-y-1/2 bg-white bg-opacity-70 rounded-full p-2 hover:bg-orange-100">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>

                        <button id="next-btn" class="absolute top-1/2 right-2 transform -translate-y-1/2 bg-white bg-opacity-70 rounded-full p-2 hover:bg-orange-100">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <div class="absolute top-2 right-2 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded">
                            -20%
                        </div>
                    </div>
                </div>

                <!-- Thumbnails -->
                <div class="grid grid-cols-5 gap-2">
                    @foreach($product->images as $index => $image)
                        <button
                            class="thumbnail border rounded-md overflow-hidden {{ $index === 0 ? 'border-orange-500' : 'border-gray-300 hover:border-orange-400' }}"
                            data-index="{{ $index }}"
                        >
                            <div class="relative aspect-square">
                                <img
                                    src="{{ asset('storage/' . $image->imageLink) }}"
                                    alt="{{ $product->productName }}"
                                    class="w-full h-full object-cover"
                                    style="width:150px; height:auto; margin:5px;"
                                >
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Details Section -->
            <div class="w-full md:w-1/2">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">{{ $product->productName }}</h1>

                <!-- Ratings -->
                <div class="flex text-amber-400 mb-1">
                    @for ($i = 0; $i < number_format($product->average_star) ; $i++)
                        <i class="fas fa-star text-warning"></i>
                    @endfor
                    @for ($i = number_format($product->average_star); $i < 5; $i++)
                        <i class="far fa-star text-muted"></i>
                    @endfor
                    <span class="text-gray-500 text-sm ml-2">({{$product->quantityComment}} đánh giá)</span>

                </div>

                <!-- Price -->
                <div class="mb-6">
                    <div class="flex items-center gap-2">
                        <span class="text-3xl font-bold text-orange-500">{{number_format($product->productSellPrice, 0, ',', '.')}}đ</span>
{{--                        <span class="text-lg text-gray-500 line-through">375.000₫</span>--}}
                    </div>
{{--                    <p class="text-green-600 text-sm mt-1">--}}
{{--                        Tiết kiệm: 76.000₫--}}
{{--                    </p>--}}
                </div>

                <!-- Short Description -->
                <p class="text-gray-600 mb-6">
                    {{$product->productDesc}}
                </p>

                <!-- Color Selection -->
                <form method="POST" action="{{ route('cart.add') }}" id="add-to-cart-form">
                    @csrf
                    <input type="hidden" name="productID" value="{{ $product->productID }}">
                    <input type="hidden" name="size" id="selectedSize">
                    <input type="hidden" name="color" id="selectedColor">
                    <input type="hidden" name="quantity" id="selectedQuantity" value="1">

                    <!-- Size Selection -->
                    <div class="mb-6">
                        <h3 class="text-gray-800 font-medium mb-2">Kích thước</h3>
                        <div class="flex flex-wrap gap-2">
                            @php $displayedSizes = []; @endphp
                            @foreach($productDetails as $details)
                                @if($details->productQuantity > 0 && !in_array($details->size->sizeName, $displayedSizes))
                                    <button type="button"
                                            data-size="{{ $details->size->sizeName }}"
                                            data-prdid="{{ $details->prdID }}"
                                            data-sizeid="{{ $details->size->sizeId }}"
                                            class="size-btn w-12 h-12 flex items-center justify-center border rounded-md border-gray-300 hover:border-orange-500">
                                        {{ $details->size->sizeName }}
                                    </button>
                                    @php $displayedSizes[] = $details->size->sizeName; @endphp
                                @endif
                            @endforeach
                        </div>
                    </div>


                    <!-- Color Selection -->
                    <div class="mb-6">
                        <h3 class="text-gray-800 font-medium mb-2">Màu sắc</h3>
                        <div id="color-options" class="flex flex-wrap gap-2">
                        </div>
                    </div>


                    <!-- Quantity -->
                    <div class="mb-6">
                        <h3 class="text-gray-800 font-medium mb-2">Số lượng</h3>
                        <div class="flex items-center">
                            <button type="button" id="decrease-quantity" class="w-10 h-10 rounded-l-md border border-gray-300 flex items-center justify-center hover:bg-gray-100">
                                <i class="fas fa-minus text-sm"></i>
                            </button>
                            <div id="quantity-display" class="w-14 h-10 border-t border-b border-gray-300 flex items-center justify-center">
                                1
                            </div>
                            <button type="button" id="increase-quantity" class="w-10 h-10 rounded-r-md border border-gray-300 flex items-center justify-center hover:bg-gray-100">
                                <i class="fas fa-plus text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mb-6">
                        <button type="submit"
                                id="addToCartBtn"
                                class="bg-orange-500 text-white px-6 py-3 rounded-md hover:bg-orange-600 transition duration-200">
                            Thêm vào giỏ hàng
                        </button>
                    </div>
                </form>
                <br>

            </div>
        </div>
    </div>

    <!-- Product Details Tabs -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-8">
        <div class="border-b border-gray-200 mb-6">
            <div class="flex flex-wrap -mb-px">
                <button class="tab-btn active inline-block py-4 px-6 border-b-2 border-orange-500 text-orange-500 font-medium" data-tab="description">
                    Mô tả sản phẩm
                </button>
            </div>
        </div>
        <div id="description-tab" class="tab-content prose max-w-none">
            <p>{{$product->productDesc}}</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 mt-8">
        <div id="reviews-tab" class="tab-content">
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Đánh giá từ khách hàng</h3>
                @forelse($comments as $comment)
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-user text-gray-500"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-900">{{$comment->user->name}}</h4>
                        <div class="flex text-amber-400 mt-1">
                            @for ($i = 0; $i < $comment->rate; $i++)
                                <i class="fas fa-star text-warning"></i>
                            @endfor
                            @for ($i = $comment->rate; $i < 5; $i++)
                                <i class="far fa-star text-muted"></i>
                            @endfor
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{$comment->contentComment}}
                        </p>
                    </div>
                </div>
                @empty
                    Sản phẩm hiện tại chưa có bình luận nào
                @endforelse
            </div>
        </div>
    </div>
    @if($hasPurchased)
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">
                        {{ $userComment ? 'Chỉnh sửa đánh giá của bạn' : 'Viết đánh giá của bạn' }}
                    </h3>

                    <form class="space-y-6" action="{{ $userComment ? route('comments.update', $userComment->idComment) : route('comments.store') }}" method="POST">
                        @csrf
                        @if($userComment)
                            @method('PUT')
                        @endif

                        <!-- Star Rating -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Đánh giá của bạn <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center space-x-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button"
                                            class="star-btn text-2xl {{ $userComment && $userComment->rate >= $i ? 'text-yellow-400' : 'text-gray-300' }} hover:text-yellow-400 transition"
                                            data-rating="{{ $i }}">
                                        <i class="fa-solid fa-star"></i>
                                    </button>
                                @endfor
                                <span class="ml-3 text-sm text-gray-500" id="rating-text">Chọn số sao</span>
                            </div>
                            <input type="hidden" id="rating-value" name="rate" value="{{ $userComment->rate ?? 0 }}">
                        </div>

                        <!-- Nội dung đánh giá -->
                        <div>
                            <label for="review-comment" class="block text-sm font-medium text-gray-700 mb-2">
                                Nội dung đánh giá <span class="text-red-500">*</span>
                            </label>
                            <textarea id="review-comment"
                                      name="contentComment"
                                      rows="5"
                                      class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                      required>{{ $userComment->contentComment ?? '' }}</textarea>
                            <input type="hidden" name="productID" value="{{ $product->productID }}">
                            <div class="mt-2 text-right">
                                <span class="text-xs text-gray-500" id="char-count">0/500 ký tự</span>
                            </div>
                        </div>

                        <!-- Nút gửi -->
                        <div class="flex items-center justify-between pt-4">
                            <div class="text-sm text-gray-500">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Đánh giá của bạn sẽ được kiểm duyệt trước khi hiển thị
                            </div>
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg transition disabled:opacity-50">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>{{ $userComment ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif




    <!-- Related Products -->
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Sản phẩm liên quan</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($products as $prd)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="relative">
                    <img
                        src="{{asset('storage/' . $prd->firstImage->imageLink)}}"
                        alt="{{$prd->productName}}"
                        class="w-full h-64 object-cover"
                    >
                </div>
                <div class="p-4">
                    <div class="flex text-amber-400 mb-1">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    <span class="text-gray-500 ml-1 text-sm">{{$prd->productQuantity}}</span>
                    </div>
                    <h3 class="text-gray-800 font-medium mb-2">
                        <a href="{{ route('productDetails', ['product' => $prd->productID]) }}" class="hover:text-orange-500">
                            {{$prd->productName}}
                        </a>
                    </h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-orange-500 font-bold">{{number_format($product->productSellPrice, 0, ',', '.')}}đ</span>
                        </div>
                        <button class="bg-orange-500 hover:bg-orange-600 text-white rounded-full w-10 h-10 flex items-center justify-center transition-colors duration-300">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
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
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('add-to-cart-form');
        const addToCartBtn = document.getElementById('addToCartBtn');
        const selectedSize = document.getElementById('selectedSize');
        const selectedColor = document.getElementById('selectedColor');

        form.addEventListener('submit', function (e) {
            if (!selectedSize.value || !selectedColor.value) {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu thông tin',
                    text: 'Vui lòng chọn kích thước và màu cho sản phẩm.',
                    confirmButtonColor: '#f97316', // orange
                });
            }
        });
    });
</script>

<script>
    // const productImages = @json($product->images->pluck('imageLink')->map(fn($img) => asset('storage/' . $img)));
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('main-image');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    let currentIndex = 0;

    function updateMainImage(index) {
        currentIndex = index;
        mainImage.src = productImages[currentIndex];

        thumbnails.forEach((thumb, i) => {
            thumb.classList.remove('border-orange-500');
            thumb.classList.add('border-gray-300', 'hover:border-orange-400');
            if (i === currentIndex) {
                thumb.classList.remove('border-gray-300', 'hover:border-orange-400');
                thumb.classList.add('border-orange-500');
            }
        });
    }

    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', function () {
            updateMainImage(index);
        });
    });

    prevBtn.addEventListener('click', function () {
        const newIndex = (currentIndex - 1 + productImages.length) % productImages.length;
        updateMainImage(newIndex);
    });

    nextBtn.addEventListener('click', function () {
        const newIndex = (currentIndex + 1) % productImages.length;
        updateMainImage(newIndex);
    });

    //Color collection
    const colorButtons = document.querySelectorAll('.color-btn');

    colorButtons.forEach(button => {
        button.addEventListener('click', function () {
            const color = this.getAttribute('data-color');
            selectedColor = color;

            // Xóa trạng thái active ở tất cả nút
            colorButtons.forEach(btn => {
                btn.classList.remove('bg-orange-500', 'text-white', 'border-orange-500');
                btn.classList.add('border-gray-300', 'hover:border-orange-500');
            });

            // Thêm trạng thái active cho nút được chọn
            this.classList.remove('border-gray-300', 'hover:border-orange-500');
            this.classList.add('bg-orange-500', 'text-white', 'border-orange-500');
        });
    });

    // Size Selection
    const sizeButtons = document.querySelectorAll('.size-btn');

    sizeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const size = this.getAttribute('data-size');
            selectedSize = size;

            // Update active size button
            sizeButtons.forEach(btn => {
                btn.classList.remove('bg-orange-500', 'text-white', 'border-orange-500');
                btn.classList.add('border-gray-300', 'hover:border-orange-500');
            });
            this.classList.remove('border-gray-300', 'hover:border-orange-500');
            this.classList.add('bg-orange-500', 'text-white', 'border-orange-500');
        });
    });

    // Quantity Control
    const decreaseBtn = document.getElementById('decrease-quantity');
    const increaseBtn = document.getElementById('increase-quantity');
    const quantityDisplay = document.getElementById('quantity');
    let quantity = 1;

    decreaseBtn.addEventListener('click', function() {
        if (quantity > 1) {
            quantity--;
            quantityDisplay.textContent = quantity;
        }
    });

    increaseBtn.addEventListener('click', function() {
        quantity++;
        quantityDisplay.textContent = quantity;
    });


    document.querySelectorAll('.size-btn').forEach(button => {
        button.addEventListener('click', function () {
            const selectedSize = this.getAttribute('data-size');
            const productId = this.dataset.prdid;
            const selectedSizeInput = document.getElementById('selectedSize');
            const selectedColorInput = document.getElementById('selectedColor');

            fetch('/get-colors-by-size', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ size: selectedSize,prdID: productId })
            })
                .then(response => response.json())
                .then(data => {
                    const colorOptions = document.getElementById('color-options');
                    colorOptions.innerHTML = '';

                    data.colors.forEach(color => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'color-btn w-12 h-12 flex items-center justify-center border rounded-md border-gray-300 hover:border-orange-500';
                        button.setAttribute('data-color', color);
                        button.textContent = color;

                        button.addEventListener('click', () => {
                            document.querySelectorAll('.color-btn').forEach(btn => {
                                btn.classList.remove('bg-orange-500', 'text-white', 'border-orange-500');
                                selectedColorInput.value = color;

                            });

                            button.classList.add('bg-orange-500', 'text-white', 'border-orange-500');
                        });

                        colorOptions.appendChild(button);
                    });
                    selectedSizeInput.value = selectedSize;


                });
        });
    });
</script>
<script src="{{asset('js/Customer/Sidebar.js')}}"></script>
<script src="{{asset('js/Customer/commentAndRate.js')}}"></script>

</body>
</html>
