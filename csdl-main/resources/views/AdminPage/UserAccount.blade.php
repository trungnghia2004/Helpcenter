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
    <style>
        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }

        .action-group {
            display: flex;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-restore {
            background-color: #28a745;
            color: white;
        }

        .btn-restore:hover {
            background-color: #218838;
        }

        .btn-ban {
            background-color: #dc3545;
            color: white;
        }

        .btn-ban:hover {
            background-color: #c82333;
        }

        .btn-disabled {
            background-color: #e0e0e0;
            color: #6c757d;
            cursor: not-allowed;
        }

    </style>
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
            <a href="{{route('products.index')}}" class="sidebar-link " data-section="products">
                <i class="fa-solid fa-box"></i>
                <span>Sản Phẩm</span>
            </a>
            <a href="{{route('customer.index')}}" class="sidebar-link active" data-section="customers">
                <i class="fa-regular fa-user"></i>
                <span>Khách Hàng</span>
            </a>
            <a href="{{route('discount_programs.index')}}" class="sidebar-link " data-section="settings">
                <i class="fa-solid fa-tags"></i>
                <span>Chương Trình Giảm Giá</span>
            </a>
            <a href="{{route('order-manage.index')}}" class="sidebar-link " data-section="orders">
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
                    <h1 class="section-title">Tài Khoản Khách Hàng</h1>
                    <p class="section-description">Quản lý tất cả tài khoản khách hàng.</p>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <div class="search-box">
                        <form method="GET" action="{{ route('customer.index') }}" style="margin-bottom: 16px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm khách hàng..." id="customer-search" />
                            <button type="submit" style="display: none"></button>
                        </form>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="products-table">
                        <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Số điện thoại</th>
                            <th>Mail</th>
                            <th>Địa chỉ</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody id="products-tbody">
                        @forelse ($customerAccounts as $customerAccount)
                            <tr >
                                <td>{{ $customerAccount->name }}</td>
                                <td>{{ preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', $customerAccount->phone) }}</td>
                                <td>{{ $customerAccount->email }}</td>
                                <td>{{ $customerAccount->city ?? 'Chưa cập nhật' }}</td>
                                <td style="padding-left: 0">
                                    <div class="action-buttons">
                                        <div class="action-group">
                                            <form action="{{ route('customer.update', $customerAccount->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-restore">
                                                    Gỡ cấm
                                                </button>
                                            </form>
                                        </div>
                                        <div class="action-group">
                                            @if($customerAccount->isDeleted == 1)
                                                <button class="btn btn-disabled" disabled>
                                                    Đã cấm
                                                </button>
                                            @elseif($customerAccount->isDeleted == 0)
                                                <form method="POST" class="ban-customer-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="button"
                                                        class="btn btn-ban ban-customer-btn"
                                                        data-url="{{ route('customer.destroy', $customerAccount->id) }}">
                                                        Cấm
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; font-style: italic;">Không có danh mục nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div style="padding: 5px 10px" class="custom-pagination">
                        <div class="page-info">Page {{ $customerAccounts->currentPage() }} of {{ $customerAccounts->lastPage() }}</div>
                        <div class="page-links">
                            @if($customerAccounts->currentPage() > 1)
                                <a href="{{ $customerAccounts->previousPageUrl() }}" class="custom-pagination-link">&laquo; Previous</a>
                            @endif

                            @for($i = 1; $i <= $customerAccounts->lastPage(); $i++)
                                @if($i == $customerAccounts->currentPage())
                                    <span class="custom-pagination-link current-page">{{ $i }}</span>
                                @else
                                    <a href="{{ $customerAccounts->url($i) }}" class="custom-pagination-link">{{ $i }}</a>
                                @endif
                            @endfor

                            @if($customerAccounts->hasMorePages())
                                <a href="{{ $customerAccounts->nextPageUrl() }}" class="custom-pagination-link">Next &raquo;</a>
                            @endif
                        </div>
                    </div>
                </div>
        </div>
        </section>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.ban-customer-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;

                Swal.fire({
                    title: 'Cấm người dùng này?',
                    text: 'Hành động này sẽ vô hiệu hóa tài khoản và không thể hoàn tác.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Xác nhận cấm',
                    cancelButtonText: 'Hủy',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';

                        const method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';

                        form.appendChild(csrf);
                        form.appendChild(method);
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
