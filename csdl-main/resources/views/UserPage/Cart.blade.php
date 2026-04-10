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
        <span class="text-gray-900 font-medium">Giỏ hàng</span>
    </div>

    <h1 class="text-3xl font-bold mb-8 text-gray-800">Giỏ hàng của bạn</h1>

    <div class="flex flex-col lg:flex-row gap-8">
        <div class="w-full lg:w-2/3">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="hidden md:flex border-b pb-4 mb-4 text-gray-600 font-medium">
                        <div class="w-2/5">Sản phẩm</div>
                        <div class="w-1/5 text-center">Giá</div>
                        <div class="w-1/5 text-center">Số lượng</div>
                        <div class="w-1/5 text-right mr-12">Tổng</div>
                    </div>


                    <!-- Cart Item 1 -->
                    @foreach($cartDetails as $detail)
                        @php
                            $product = $detail->productDetail?->product;
                            $image = $product?->firstImage?->imageLink ?? 'default.jpg';
                            $size = $detail->productDetail?->size?->sizeName ?? 'N/A';
                            $color = $detail->productDetail?->color?->colorName ?? 'N/A';
                        @endphp
                        <div class="flex flex-col md:flex-row items-center py-4 border-b">
                            <div class="w-full md:w-2/5 flex items-center mb-4 md:mb-0">
                                <input type="checkbox" class="mr-2 cart-item-checkbox" data-item="{{ $detail->id }}" data-price="{{ ($product->productSellPrice ?? 0) * $detail->quantity }}" checked>


                                <div class="relative">
                                    <img  src="{{ asset('storage/' . $image) }}" alt="{{ $product->productName }}"  class="w-20 h-20 min-w-[80px] object-cover rounded">
                                </div>
                                <div class="ml-4">
                                    <h3 style="max-width: 150px" class="text-gray-800 font-medium">{{$product->productName}}</h3>
                                    <p class="text-gray-500 text-sm">Màu: {{ $color }} | Size: {{ $size }}</p>
                                </div>
                            </div>
                            <div class="w-full md:w-1/5 text-center mb-4 md:mb-0">
                                <span class="text-gray-800">{{ number_format($product->productSellPrice ?? 0, 0, ',', '.') }}₫</span>
                            </div>
                            <div class="w-full md:w-1/5 flex justify-center mb-4 md:mb-0">
                                <div class="flex items-center border rounded-md">
                                    <button class="px-3 py-1 text-gray-600 hover:text-orange-500 decrease-quantity" data-item="{{$detail->id}}">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" min="1" value="{{$detail->quantity}}" class="w-12 text-center border-x py-1 focus:outline-none quantity-input" data-item="{{$detail->id}}" data-price="{{$product->productSellPrice}}">
                                    <button class="px-3 py-1 text-gray-600 hover:text-orange-500 increase-quantity" data-item="{{$detail->id}}">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="w-full md:w-1/5 text-right">
                                <span class="text-orange-500 font-bold item-total" data-item="{{$detail->id}}">{{number_format(($product->productSellPrice ?? 0) * $detail->quantity, 0, ',', '.')}}đ</span>
                            </div>
                            <div class="w-full md:w-auto flex justify-center md:justify-end md:ml-4">
                                <form
                                    action="{{ route('cart.remove', $detail->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm ra khỏi giỏ hàng không?');"
                                    class="delete-form">
                                    @csrf
                                    <button
                                        type="button"
                                        class="bg-gray-200 hover:bg-red-100 hover:text-red-600 text-gray-600 rounded-full w-8 h-8 flex items-center justify-center text-sm transition-colors duration-200 delete-item"
                                        data-url="{{ route('cart.remove', $detail->id) }}">
                                        &times;
                                    </button>

                                </form>
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    document.querySelectorAll('.delete-item').forEach(button => {
                                        button.addEventListener('click', () => {
                                            const formUrl = button.dataset.url;

                                            Swal.fire({
                                                title: 'Bạn có chắc chắn?',
                                                text: "Sản phẩm sẽ bị xoá khỏi giỏ hàng!",
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#d33',
                                                cancelButtonColor: '#3085d6',
                                                confirmButtonText: 'Xoá',
                                                cancelButtonText: 'Huỷ',
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    // Tạo và submit form ẩn
                                                    const form = document.createElement('form');
                                                    form.method = 'POST';
                                                    form.action = formUrl;

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

                        </div>


                    @endforeach

                </div>
            </div>

            <div class="flex justify-between mt-6">
                <a href="/showProduct" class="flex items-center text-gray-700 hover:text-orange-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="w-full lg:w-1/3">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-orange-400 to-amber-300 px-6 py-4">
                    <h2 class="text-white font-semibold text-lg">Tổng giỏ hàng</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between pb-4 border-b">
                            <span class="text-gray-600">Tạm tính</span>
                            <span class="text-gray-800 font-medium" id="subtotal">{{ number_format($total, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between pb-4 border-b">
                            <span class="text-gray-600">Giảm giá</span>
                            <span class="text-gray-800 font-medium" id="discount">0đ</span>
                        </div>

                        <!-- Select mã giảm giá -->
                        <div class="pt-4">
                            <div class="relative">
                                <select id="discount-select" class="custom-select w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent appearance-none">
                                    <option value="">Chọn mã giảm giá</option>
                                    @forelse ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                    @empty
                                        <option disabled value="empty">Hiện tại không có mã giảm giá nào</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-800 font-bold">Tổng cộng</span>
                            <span class="text-orange-500 font-bold text-xl" id="total">{{ number_format($total, 0, ',', '.') }}đ</span>
                        </div>

                        <!-- Form thanh toán -->
                        <form id="checkout-form" action="{{ route('checkout.index') }}" method="GET">
                            @csrf
                            <input type="hidden" name="cart_data" id="checkout-items">
                            <input type="hidden" name="discount_code" id="selected-discount">
                            <button id="checkout-button" class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-md flex items-center justify-center">
                                <i class="fas fa-credit-card mr-2"></i>
                                Tiến hành thanh toán
                            </button>
                        </form>

                        <div class="flex items-center justify-center text-gray-500 text-sm mt-4">
                            <i class="fas fa-lock mr-2"></i>
                            Thanh toán an toàn & bảo mật
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript -->
<script>
    // Định dạng giá theo VND
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    }

    // Chuyển đổi chuỗi định dạng tiền về số
    function parsePrice(priceString) {
        return parseInt(priceString.replace(/\D/g, ''));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const select = document.querySelector('.custom-select');
        const subtotalElement = document.getElementById('subtotal');
        const discountElement = document.getElementById('discount');
        const totalElement = document.getElementById('total');

        select.addEventListener('change', function () {
            const programId = this.value;

            if (!programId) return;

            const subtotalText = subtotalElement.textContent.replace(/[^\d]/g, '');
            const subtotal = parseInt(subtotalText);

            fetch(`/discount/${programId}?total=${subtotal}`)
                .then(res => res.json())
                .then(data => {
                    if (data.discount !== undefined) {
                        const discount = Math.round(data.discount);
                        const newTotal = subtotal - discount;

                        discountElement.textContent = discount.toLocaleString('vi-VN') + 'đ';
                        totalElement.textContent = newTotal.toLocaleString('vi-VN') + 'đ';
                    } else {
                        alert(data.error || 'Không thể áp dụng mã giảm giá.');
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert('Đã xảy ra lỗi khi áp dụng mã giảm giá.');
                });
        });
    });
    // Cập nhật tổng giá của từng sản phẩm
    function updateItemTotal(itemId) {
        const quantityInput = document.querySelector(`.quantity-input[data-item="${itemId}"]`);
        const quantity = parseInt(quantityInput.value);
        const price = parseInt(quantityInput.getAttribute('data-price'));
        const total = price * quantity;

        document.querySelector(`.item-total[data-item="${itemId}"]`).textContent = formatPrice(total);

        updateCartTotals();
    }


    // Cập nhật tổng giỏ hàng, chỉ tính những sản phẩm đã được chọn (checkbox)
    function updateCartTotals() {
        let subtotal = 0;

        document.querySelectorAll('.quantity-input').forEach(input => {
            const itemId = input.getAttribute('data-item');
            const checkbox = document.querySelector(`.cart-item-checkbox[data-item="${itemId}"]`);

            // Chỉ tính nếu checkbox được chọn (nếu có)
            if (!checkbox || checkbox.checked) {
                const quantity = parseInt(input.value);
                const price = parseInt(input.getAttribute('data-price'));
                subtotal += price * quantity;
            }
        });

        document.getElementById('subtotal').textContent = formatPrice(subtotal);

            const discount = parsePrice(document.getElementById('discount').textContent);
        const total = subtotal - discount;

        document.getElementById('total').textContent = formatPrice(total);
    }

    // Khởi tạo các sự kiện khi DOM đã tải
    document.addEventListener('DOMContentLoaded', function () {
        // Nút giảm số lượng
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', function () {
                const itemId = this.getAttribute('data-item');
                const input = document.querySelector(`.quantity-input[data-item="${itemId}"]`);
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                    updateItemTotal(itemId);
                }
            });
        });

        // Nút tăng số lượng
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', function () {
                const itemId = this.getAttribute('data-item');
                const input = document.querySelector(`.quantity-input[data-item="${itemId}"]`);
                let value = parseInt(input.value);
                input.value = value + 1;
                updateItemTotal(itemId);
            });
        });

        // Khi thay đổi giá trị số lượng bằng tay
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function () {
                const itemId = this.getAttribute('data-item');
                let value = parseInt(this.value);

                if (value < 1 || isNaN(value)) {
                    this.value = 1;
                    value = 1;
                }

                updateItemTotal(itemId);
            });
        });

        // Khi thay đổi trạng thái checkbox
        document.querySelectorAll('.cart-item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                updateCartTotals();
            });
        });

        // Nút xóa sản phẩm
        document.querySelectorAll('[id^="remove-item-"]').forEach(button => {
            button.addEventListener('click', function () {
                const itemId = this.id.split('-').pop();
                const itemElement = this.closest('.flex.flex-col.md\\:flex-row');
                if (itemElement) itemElement.style.display = 'none';
                updateCartTotals();
            });
        });


        // Khởi tạo tính tổng ban đầu
        updateCartTotals();
    });

    document.getElementById('checkout-form').addEventListener('submit', function (e) {
        // Ngăn submit form mặc định để xử lý dữ liệu
        e.preventDefault();

        const selectedItems = [];

        // Lặp qua các checkbox đã chọn
        document.querySelectorAll('.cart-item-checkbox:checked').forEach(checkbox => {
            const itemId = checkbox.dataset.item;
            const quantityInput = document.querySelector(`.quantity-input[data-item="${itemId}"]`);
            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

            selectedItems.push({
                id: itemId,
                quantity: quantity
            });
        });

        if (selectedItems.length === 0) {
            alert('Vui lòng chọn ít nhất một sản phẩm để thanh toán.');
            return;
        }

        const discountSelect = document.getElementById("discount-select");
        const selectedDiscountId = discountSelect.value;
        document.getElementById("selected-discount").value = selectedDiscountId;

        // Gán dữ liệu vào input ẩn dưới dạng JSON
        document.getElementById('checkout-items').value = JSON.stringify(selectedItems);

        // Gửi form
        e.target.submit();
    });
</script>

<script src="{{asset('js/Customer/Sidebar.js')}}"></script>

</body>
</html>
