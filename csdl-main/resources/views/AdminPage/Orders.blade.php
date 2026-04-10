<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendyTeen Admin - Quản Lý Cửa Hàng</title>
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
        <section id="products-section" class="content-section active">
            <div class="section-header">
                <div>
                    <h1 class="section-title">Đơn Hàng</h1>
                    <b class="section-description">Tổng số lượng đơn hàng của cửa hàng: {{$total}}</b>
                </div>
                <button onclick="openForm()" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Tạo đơn hàng mới
                </button>
                <div id="formOverlay" >
                    <div class="form-container" style="top: -10%;">
                        <span onclick="closeForm()" class="close-btn">×</span>
                        <form id="productForm" action="{{route('order-manage.store')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="productName">Khách hàng:</label>
                                <input type="text" name="nameCus" placeholder="Tên khách hàng" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Số điện thoại</label>
                                <input type="number" id="phoneCus" name="phone" placeholder="Số điện thoại" required>
                            </div>
                            <div class="form-group">
                                <label for="payID">Chọn phương thức thanh toán</label>
                                <select name="payID" id="payID" class="styled-select">
                                    @foreach($payments as $payment)
                                        <option value="{{ $payment->paymentID }}">{{ $payment->payMethod }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="submit-btn">Tạo</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="filter-tabs">
                <h3>Lọc theo trạng thái đơn hàng</h3>
                <div class="filter-buttons">
                    <form id="filterForm" method="GET">
                        <button class="filter-btn {{ request('statusID') == null ? 'active' : '' }}" name="statusID" value="">
                            Tất cả
                            <span class="filter-count" id="count-all">{{ $totalOrders }}</span>
                        </button>

                        <button class="filter-btn {{ request('statusID') == '1' ? 'active' : '' }}" name="statusID" value="1">
                            Đang chờ duyệt
                            <span class="filter-count">{{ $statusCounts[1] ?? 0 }}</span>
                        </button>

                        <button class="filter-btn {{ request('statusID') == '2' ? 'active' : '' }}" name="statusID" value="2">
                            Đã duyệt
                            <span class="filter-count">{{ $statusCounts[2] ?? 0 }}</span>
                        </button>

                        <button class="filter-btn {{ request('statusID') == '3' ? 'active' : '' }}" name="statusID" value="3">
                            Đang giao hàng
                            <span class="filter-count">{{ $statusCounts[3] ?? 0 }}</span>
                        </button>

                        <button class="filter-btn {{ request('statusID') == '4' ? 'active' : '' }}" name="statusID" value="4">
                            Đã giao
                            <span class="filter-count">{{ $statusCounts[4] ?? 0 }}</span>
                        </button>

                        <button class="filter-btn {{ request('statusID') == '5' ? 'active' : '' }}" name="statusID" value="5">
                            Đã hủy
                            <span class="filter-count">{{ $statusCounts[5] ?? 0 }}</span>
                        </button>
                    </form>

                </div>
            </div>


            <div class="table-container">
                <div class="table-header">
                    <div class="search-box">
                        <form method="GET" action="{{ route('order-manage.index') }}" style="margin-bottom: 16px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm theo mã đơn hàng..." id="product-search" />
                            <button type="submit" style="display: none"></button>
                        </form>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="products-table">
                        <thead>
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Tên khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Tổng giá trị</th>
                            <th>Ngày tạo đơn</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                        </thead>
                        <tbody id="products-tbody">

                        @forelse ($orders as $order)
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


                            <tr>
                                <td>
                                    <div class="cell-product">
                                        <div style="position: relative;">
                                            <h4 style="max-width: 200px; display: inline-block;">
                                                <a style="  text-decoration: none; color: black"
                                                   onmouseover="this.style.color='blue';"
                                                   onmouseout="this.style.color='black';"
                                                   href="orders/{{$order->orderID}}">
                                                    #ORD{{$order->orderID}}
                                                </a>
                                            </h4>
                                            <button onclick="toggleDropdown('dropdown-{{$order->orderID}}')" style="border: none; background: transparent; cursor: pointer; margin-left: 5px;">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>

                                            <div id="dropdown-{{$order->orderID}}" class="dropdown-detail" style="display: none; position: absolute; z-index: 1000 !important; background: white; border: 1px solid #ccc; padding: 10px; width: 300px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                                                @if($order->orderDetails->count() == 0)
                                                    <p>
                                                        Đơn hàng này hiện không có sản phẩm nào
                                                    </p>
                                                @endif
                                                @foreach($order->orderDetails as $detail)
                                                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                                        <img src="{{ asset('storage/' . $detail->productDetail->product->firstImage->imageLink) }}" alt="Ảnh sản phẩm" style="width: 60px; height: 60px; object-fit: cover; margin-right: 10px;">
                                                        <div>
                                                            <strong>{{ $detail->productDetail->product->productName }}</strong><br>
                                                            SL: {{ $detail->orderQuantity}}<br>
                                                            Giá: {{ number_format($detail->productDetail->product->productSellPrice, 0, ',', '.') }}đ
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                    </div>
                                </td>

                                <td>{{ $order->customer->name }}</td>
                                <td>{{ preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', $order->orderPhoneNumber) }}</td>
                                <td>{{number_format($order->totalPrice, 0, ',', '.')}}đ</td>
                                <td>{{$order->created_at->format('d/m/Y H:i')}}</td>

                                <td>
                                    <span class="status-badge status-{{ Str::slug($status) }}">{{ $status }}</span>
                                </td>
                                <td class="text-center space-x-2">
                                    @if($status == 'Đang chờ duyệt' && $order->orderDetails->count() == 0)
                                        <div style="display: flex" class="">
                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.approve', $order->orderID) }}">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button disabled" title="Duyệt đơn">
                                                    <i class="fas fa-circle-check"></i>
                                                </button>
                                            </form>

                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.deliver', $order->orderID) }}">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button deliver disabled" title="Giao đơn">
                                                    <i class="fas fa-truck-fast"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('orders.cancel', $order->orderID) }}" class="cancel-order-form">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button type="button"
                                                        class="circle-button cancel cancel-order-btn"
                                                        title="Hủy đơn"
                                                        data-url="{{ route('orders.cancel', $order->orderID) }}"
                                                        data-cusid="{{ $order->cusID }}">
                                                    <i class="fas fa-circle-xmark"></i>
                                                </button>
                                            </form>

                                        </div>
                                    @elseif($status == 'Đang chờ duyệt')
                                        <div style="display: flex" class="">
                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.approve', $order->orderID) }}">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button approve" title="Duyệt đơn">
                                                    <i class="fas fa-circle-check"></i>
                                                </button>
                                            </form>

                                            <button  class="circle-button deliver disabled" title="Giao đơn">
                                                <i class="fas fa-truck-fast"></i>
                                            </button>

                                            <form method="POST" action="{{ route('orders.cancel', $order->orderID) }}" class="cancel-order-form">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button type="button"
                                                        class="circle-button cancel cancel-order-btn"
                                                        title="Hủy đơn"
                                                        data-url="{{ route('orders.cancel', $order->orderID) }}"
                                                        data-cusid="{{ $order->cusID }}">
                                                    <i class="fas fa-circle-xmark"></i>
                                                </button>
                                            </form>

                                        </div>
                                    @elseif($status == 'Đã duyệt')
                                        <div style="display: flex" class="">
                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.approve', $order->orderID) }}">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button disabled" title="Duyệt đơn">
                                                    <i class="fas fa-circle-check"></i>
                                                </button>
                                            </form>

                                            <button onclick="openImageUploadForm({{ $order->orderID }})" class="circle-button deliver" title="Giao đơn">
                                                <i class="fas fa-truck-fast"></i>
                                            </button>
                                            <div class="form-overlay image-upload-overlay" id="imageUploadFormOverlay-{{ $order->orderID }}" style="display: none;">
                                                <div class="form-container" style="height: 200px">
                                                    <h2>Thêm mã vận đơn</h2>
                                                    <form action="{{ route('orders.deliver', $order->orderID) }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        <label for="cusID">Mã vận đơn</label>
                                                        <input type="hidden" name="cusID" placeholder="Nhập mã vận đơn" value="{{ $order->cusID }}">

                                                        <div class="form-group">
                                                            <input type="text" name="shipping_code" required>
                                                        </div>
                                                        <div style="display: flex; justify-content: space-between; gap: 10px; margin-top: 15px;">
                                                            <button type="submit" class="submit-btn">Tải lên</button>
                                                            <button type="button" class="cancel-btn submit-btn" onclick="closeImageUploadForm({{ $order->orderID }})" style="background-color: #dc3545;">Hủy</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.cancel', $order->orderID) }}" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?')">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button cancel" title="Hủy đơn">
                                                    <i class="fas fa-circle-xmark"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($status == 'Đang giao hàng')
                                        <div style="display: flex" class="">
                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.approve', $order->orderID) }}">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button disabled" title="Duyệt đơn">
                                                    <i class="fas fa-circle-check"></i>
                                                </button>
                                            </form>

                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.deliver', $order->orderID) }}">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button disabled" title="Giao đơn">
                                                    <i class="fas fa-truck-fast"></i>
                                                </button>
                                            </form>

                                            <form style="margin-left: 5px;margin-right: 5px" method="POST" action="{{ route('orders.cancel', $order->orderID) }}" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?')">
                                                @csrf
                                                <input type="hidden" name="cusID" value="{{ $order->cusID }}">
                                                <button class="circle-button disabled" title="Hủy đơn">
                                                    <i class="fas fa-circle-xmark"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>


                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center;">Không có sản phẩm nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div style="padding: 0 10px" class="custom-pagination">
                        <div class="page-info">Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</div>
                        <div class="page-links">
                            @if($orders->currentPage() > 1)
                                <a href="{{ $orders->previousPageUrl() }}" class="custom-pagination-link">&laquo; Previous</a>
                            @endif

                            @for($i = 1; $i <= $orders->lastPage(); $i++)
                                @if($i == $orders->currentPage())
                                    <span class="custom-pagination-link current-page">{{ $i }}</span>
                                @else
                                    <a href="{{ $orders->url($i) }}" class="custom-pagination-link">{{ $i }}</a>
                                @endif
                            @endfor

                            @if($orders->hasMorePages())
                                <a href="{{ $orders->nextPageUrl() }}" class="custom-pagination-link">Next &raquo;</a>
                            @endif
                        </div>
                    </div>
                    <br>
                </div>
            </div>
        </section>
    </div>
</div>
<script>
    function toggleDropdown(id) {
        var dropdown = document.getElementById(id);
        if (dropdown.style.display === "none" || dropdown.style.display === "") {
            dropdown.style.display = "block";
        } else {
            dropdown.style.display = "none";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cancelButtons = document.querySelectorAll('.cancel-order-btn');

        cancelButtons.forEach(button => {
            button.addEventListener('click', function () {
                const url = this.dataset.url;
                const cusID = this.dataset.cusid;

                Swal.fire({
                    title: 'Hủy đơn hàng?',
                    text: "Bạn có chắc chắn muốn hủy đơn này không?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Hủy đơn',
                    cancelButtonText: 'Không',
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

                        const cusIdInput = document.createElement('input');
                        cusIdInput.type = 'hidden';
                        cusIdInput.name = 'cusID';
                        cusIdInput.value = cusID;

                        form.appendChild(csrf);
                        form.appendChild(cusIdInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
</script>

<script src="{{asset('js/Admin/jsPopup.js')}}"></script>
<script src="{{asset('js/Admin/script.js')}}"></script>
</body>
</html>
