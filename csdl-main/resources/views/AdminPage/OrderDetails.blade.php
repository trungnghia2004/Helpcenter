<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendyTeen Admin - Chi Tiết Đơn Hàng</title>
    <link rel="stylesheet" href="{{asset('css/admin/styles.css')}}">
    <link rel="stylesheet" href="{{asset('css/admin/stylePopup.css')}}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <title>CLOTHES | BKACAD</title>
</head>

<body>
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
<header class="header">
    <div class="header-container">
        <div class="header-left">
            <div class="logo">
                <span class="logo-text">TrendyTeen Admin</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-menu">
                <div class="user-info">
                    <p class="user-name">Admin</p>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="main-layout">
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <a href="{{route('adminDashboard')}}" class="sidebar-link " data-section="dashboard">
                <i class="fa-solid fa-chart-line"></i>
                <span>Tổng Quan</span>
            </a>
            <a href="{{route('categories.index')}}" class="sidebar-link" data-section="products">
                <i class="fa-regular fa-rectangle-list"></i>
                <span>Danh Mục Sản Phẩm</span>
            </a>
            <a href="{{route('products.index')}}" class="sidebar-link" data-section="products">
                <i class="fa-solid fa-box"></i>
                <span>Sản Phẩm</span>
            </a>
            <a href="{{route('customer.index')}}" class="sidebar-link" data-section="customers">
                <i class="fa-regular fa-user"></i>
                <span>Khách Hàng</span>
            </a>
            <a href="{{route('discount_programs.index')}}" class="sidebar-link " data-section="settings">
                <i class="fa-solid fa-tags"></i>
                <span>Chương Trình Giảm Giá</span>
            </a>
            <a href="{{route('order-manage.index')}}" class="sidebar-link active" data-section="orders">
                <i class="fa-solid fa-receipt"></i>
                <span>Đơn Hàng</span>
            </a>
            <form method="post" action="{{route('logout')}}">
                @csrf
                <button style="border: none; background: white; margin-top: 100px" class="sidebar-link">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Đăng Xuất</span>
                </button>
            </form>
        </nav>
    </aside>

    <div class="main-content">
        @php
            $status = $order->status->statusValue ?? 'Không rõ';
            $colorClass = '';

            switch ($status) {
                case 'Đang chờ duyệt':
                    $colorClass = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'Đã duyệt':
                    $colorClass = 'bg-blue-100 text-blue-800';
                    break;
                case 'Đang giao hàng':
                    $colorClass = 'bg-purple-100 text-purple-800';
                    break;
                case 'Đã giao hàng':
                    $colorClass = 'bg-green-100 text-green-800';
                    break;
                case 'Đã hủy':
                    $colorClass = 'bg-red-100 text-red-800';
                    break;
                default:
                    $colorClass = 'bg-gray-100 text-gray-800';
            }
        @endphp
        <section id="order-detail-section" class="content-section active">
            <div class="section-header">
                <div>
                    <h1 class="section-title">Chi Tiết Đơn Hàng </h1>
                    <p class="section-description">Thông tin chi tiết về đơn hàng</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fa-solid fa-arrow-left"></i>
                        Quay lại
                    </button>
                    @if($order->orderDetails->count() == 0 && $order->status->statusValue != 'Đã hủy')
                        <button onclick="openDetailsForm({{ $order->orderID }})" class="btn btn-primary">
                            <i class="fa-solid fa-print"></i>
                            Thêm chi tiết đơn hàng
                        </button>
                    <div id="formDetailsOverlay-{{ $order->orderID }}" class="form-overlay" >
                        <div class="form-container" id="orderDetails-form">
                            <span onclick="closeDetailsForm({{ $order->orderID }})" class="close-btn">×</span>

                            <form action="{{ route('ordersDetails.add', $order->orderID) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div id="productFormsContainer">
                                    <div class="product-form">
                                        @include('partials.product-form')
                                    </div>
                                </div>

                                <button type="button" onclick="addProductForm()" class="add-btn">+ Thêm sản phẩm</button>

                                <button type="submit" class="submit-btn edit-submit-btn">Cập nhật</button>
                            </form>
                        </div>
                    </div>


                    @endif

                </div>
            </div>

            <!-- Thông tin đơn hàng -->
            <div class="table-container" style="margin-bottom: 20px;">
                <div class="table-header">
                    <h2 style="font-size: 18px; margin: 0;">Thông Tin Đơn Hàng</h2>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Mã đơn hàng:</label>
                                <span style="color: #1f2937;">#ORD{{ $order->orderID }}</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Tên khách hàng:</label>
                                <span style="color: #1f2937;">{{ $order->customer->name }}</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Số điện thoại:</label>
                                <span style="color: #1f2937;">{{ $order->orderPhoneNumber }}</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Email:</label>
                                <span style="color: #1f2937;">{{ $order->customer->email ?? 'Không có email' }}</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Giá trị được giảm:</label>
                                @if($order->discount)
                                    @php
                                        $discountAmount = $order->discount->calculateDiscount($order->totalPrice + 0);
                                    @endphp
                                    <span style="color: #dc2626; font-weight: 600; font-size: 18px;">
                                        {{ number_format($discountAmount, 0, ',', '.') }}đ
                                    </span>
                                @else
                                    <span style="color: #9ca3af;">0đ</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Ngày đặt hàng:</label>
                                <span style="color: #1f2937;">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Trạng thái:</label>
                                <span class="status-badge status-{{ Str::slug($status) }}">{{ $order->status->statusValue ?? 'Không rõ' }}</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Phương thức thanh toán:</label>
                                <span style="color: #1f2937;">{{ $order->payment->payMethod ?? 'Không rõ' }}</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Chương trình giảm giá được áp dụng:</label>
                                @if($order->discount)
                                    <span style="color: #1f2937;">{{ $order->discount->name }}</span>
                                @else
                                    <span style="color: #9ca3af;">Không áp dụng</span>
                                @endif
                            </div>

                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Tổng giá trị:</label>
                                <span style="color: #dc2626; font-weight: 600; font-size: 18px;">{{ number_format($order->totalPrice, 0, ',', '.') }}đ</span>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 5px;">Địa chỉ giao hàng:</label>
                        <span style="color: #1f2937;">
                            @if(!empty($order->shipping_street))
                                {{ $order->shipping_street }}, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_city }}
                            @else
                                Đơn hàng này được mua trực tiếp tại cửa hàng
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <h2 style="font-size: 18px; margin: 0;">Chi Tiết Sản Phẩm</h2>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="order-details-table">
                        <thead>
                        <tr>
                            <th style="width: 80px;">Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Size</th>
                            <th>Màu sắc</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($order->orderDetails as $detail)
                            <tr>
                                <td>
                                    <div class="cell-product">
                                        <img src="{{ asset('storage/' . $detail->productDetail->product->firstImage->imageLink) }}" alt="{{ $detail->productDetail->product->productName }}">
                                    </div>
                                </td>
                                <td>
                                    <h4>{{ $detail->productDetail->product->productName }}</h4>
                                </td>
                                <td>{{ $detail->productDetail->size->sizeName }}</td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <span>{{ $detail->productDetail->color->colorName }}</span>
                                    </div>
                                </td>
                                <td>{{ number_format($detail->productDetail->product->productSellPrice, 0, ',', '.') }}đ</td>
                                <td>{{ $detail->orderQuantity }}</td>
                                <td>{{ number_format($detail->productDetail->product->productSellPrice * $detail->orderQuantity, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
<script>
    const allProducts = [
            @foreach($products as $product)
        { productID: "{{ $product->productID }}", productCode: "{{ $product->productCode }}" },
        @endforeach
    ];
    function addProductForm() {
        const container = document.getElementById('productFormsContainer');
        const originalForm = container.querySelector('.product-form');
        const newForm = originalForm.cloneNode(true);

        newForm.querySelectorAll('input, select').forEach(el => {
            el.value = '';
        });

        container.appendChild(newForm);

        setupProductFormEvents(newForm);

        const formContainer = document.querySelector('.form-container');
        const totalForms = container.querySelectorAll('.product-form').length;
        const formWidth = 320;
        const padding = 100;
        formContainer.style.width = (totalForms * formWidth + padding) + 'px';

        container.style.display = 'flex';
        container.style.overflowX = 'auto';
        container.style.gap = '20px';
    }

    function setupProductFormEvents(form) {
        const searchInput = form.querySelector('.searchProduct');
        const prdIDInput = form.querySelector('.prdID');
        const sizeSelect = form.querySelector('.sizeId');
        const colorSelect = form.querySelector('.colorId');
        const suggestionsBox = form.querySelector('.suggestions-list');

        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            suggestionsBox.innerHTML = '';

            if (!searchTerm) return;

            const filteredProducts = allProducts.filter(p => p.productCode.toLowerCase().includes(searchTerm));
            filteredProducts.forEach(product => {
                const item = document.createElement('div');
                item.classList.add('suggestion-item');
                item.textContent = product.productCode;
                item.dataset.productID = product.productID;

                item.addEventListener('click', function () {
                    searchInput.value = product.productCode;
                    prdIDInput.value = product.productID;
                    suggestionsBox.style.display = 'none';
                    loadSizes(product.productID, sizeSelect, colorSelect);
                });

                suggestionsBox.appendChild(item);
            });
            suggestionsBox.style.display = 'block';
        });

        sizeSelect.addEventListener('change', function () {
            const sizeId = this.value;
            const productID = prdIDInput.value;
            loadColors(productID, sizeId, colorSelect);
        });
    }

    function loadSizes(productID, sizeSelect, colorSelect) {
        $(sizeSelect).empty().append('<option value="">-- Đang tải size --</option>');
        $(colorSelect).empty().append('<option value="">-- Chọn size trước --</option>');

        $.ajax({
            url: '/admin/get-sizes/' + productID,
            type: 'GET',
            success: function (data) {
                $(sizeSelect).empty().append('<option value="">-- Chọn size --</option>');
                data.forEach(function (item) {
                    $(sizeSelect).append(`<option value="${item.sizeId}">${item.size}</option>`);
                });
            }
        });
    }

    function loadColors(productID, sizeId, colorSelect) {
        $(colorSelect).empty().append('<option value="">-- Đang tải màu --</option>');
        $.ajax({
            url: '/admin/get-colors/' + productID + '/' + sizeId,
            type: 'GET',
            success: function (data) {
                $(colorSelect).empty().append('<option value="">-- Chọn màu --</option>');
                data.forEach(function (item) {
                    $(colorSelect).append(`<option value="${item.colorId}">${item.color}</option>`);
                });
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        const firstForm = document.querySelector('.product-form');
        if (firstForm) {
            setupProductFormEvents(firstForm);
        }
    });

</script>

<script>


</script>
<script src="{{asset('js/Admin/jsPopup.js')}}"></script>
<script src="{{asset('js/Admin/script.js')}}"></script>
</body>
</html>
