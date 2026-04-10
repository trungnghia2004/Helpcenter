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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            <a href="{{route('categories.index')}}" class="sidebar-link active" data-section="products">
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
            <a href="{{route('discount_programs.index')}}" class="sidebar-link" data-section="settings">
                <i class="fa-solid fa-tags"></i>
                <span>Chương Trình Giảm Giá</span>
            </a>
            <a href="{{route('order-manage.index')}}" class="sidebar-link" data-section="orders">
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
                    <h1 class="section-title">Danh Mục Sản Phẩm</h1>
                    <b class="section-description">Tổng số lượng danh mục sản phẩm của cửa hàng: {{$total}}</b>
                </div>
                <button onclick="openForm()" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Thêm danh mục sản phẩm
                </button>
            </div>
            <div id="formOverlay">
                <div class="form-container">
                    <span onclick="closeForm()" class="close-btn">×</span>

                    <form id="categoryForm" action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="categoryName">Tên danh mục</label>
                            <input type="text" id="categoryName" name="categoryName" placeholder="Tên danh mục" required>
                        </div>

                        <div class="form-group">
                            <label for="categoryImage">Hình ảnh danh mục</label>
                            <input type="file" id="categoryImage" name="categoryImage">
                        </div>

                        <div class="form-group">
                            <label for="categoryDesc">Mô tả</label>
                            <textarea id="categoryDesc" name="categoryDesc" placeholder="Mô tả"></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Tạo</button>
                    </form>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <div class="search-box">
                        <form method="GET" action="{{ route('categories.index') }}">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm danh mục..." />
                            <button type="submit" style="display: none">Tìm</button>
                        </form>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="category-table">
                        <thead>
                        <tr>
                            <th>Danh mục</th>
                            <th>Hình ảnh</th>
                            <th>Mô tả</th>
                            <th>Hành động</th>
                        </tr>
                        </thead>
                        <tbody id="products-tbody">
                        @forelse ($categories as $category)
                            <div id="editFormOverlay-{{ $category->categoryID }}" class="form-overlay">
                                <div class="form-container">
                                    <span onclick="closeEditForm('{{ $category->categoryID }}')" class="close-btn">×</span>
                                    <h2 class="form-title">Chỉnh sửa danh mục</h2>
                                    <form id="editCategoryForm" action="{{ route('categories.update', ['category' => $category->categoryID]) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" id="{{$category->categoryID}}">
                                        <div class="form-group">
                                            <label for="editCategoryName">Tên danh mục</label>
                                            <input type="text" id="editCategoryName" name="categoryName" placeholder="Category Name" value="{{$category->categoryName}}" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="editCategoryImage">Hình ảnh danh mục</label>
                                            <input type="file" id="editCategoryImage" name="categoryImage">
                                            <div id="currentImageContainer" style="margin-top: 10px;">
                                                <p style="font-size: 12px; color: #666;">Hình ảnh hiện tại:</p>
                                                <img id="currentImage" src="{{ asset('storage/' . $category->categoryImage) }}" alt="Current Image" style="max-width: 100px; max-height: 100px; margin-top: 5px;">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="editCategoryDesc">Mô tả</label>
                                            <textarea id="editCategoryDesc" name="categoryDesc" placeholder="Description">{{$category->categoryDesc}}</textarea>
                                        </div>

                                        <button type="submit" class="submit-btn edit-submit-btn">Cập nhật</button>
                                    </form>
                                </div>
                            </div>

                            <tr>
                                <td>
                                    <h4 style="max-width: 100px">{{$category->categoryName}}</h4>
                                </td>
                                <td>
                                    <img width="100px" height="100px" src="{{ asset('storage/' . $category->categoryImage) }}" alt="">
                                </td>
                                <td>{{$category->categoryDesc}}</td>
                                <td style="text-align: left;">
                                    <button class="action-btn" onclick="showActionMenu(event)">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>
                                    <div class="dropdown-menu" id="action-dropdown">
                                        <button style="border: none; background: white; width: 100%; text-align: left" class="dropdown-item" onclick="openEditForm('{{ $category->categoryID }}')">Chỉnh sửa</button>
                                        <hr>
                                        <form action="{{ route('categories.destroy', $category->categoryID) }}" method="POST" class="delete-category-form">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="dropdown-item danger delete-category-btn"
                                                data-url="{{ route('categories.destroy', $category->categoryID) }}"
                                                style="border: none; background: white; width: 100%; text-align: left">
                                                Xóa
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center">Không danh mục sản phẩm nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div style="padding: 0 10px" class="custom-pagination">
                        <div class="page-info">Page {{ $categories->currentPage() }} of {{ $categories->lastPage() }}</div>
                        <div class="page-links">
                            @if($categories->currentPage() > 1)
                                <a href="{{ $categories->previousPageUrl() }}" class="custom-pagination-link">&laquo; Previous</a>
                            @endif

                            @for($i = 1; $i <= $categories->lastPage(); $i++)
                                @if($i == $categories->currentPage())
                                    <span class="custom-pagination-link current-page">{{ $i }}</span>
                                @else
                                    <a href="{{ $categories->url($i) }}" class="custom-pagination-link">{{ $i }}</a>
                                @endif
                            @endfor

                            @if($categories->hasMorePages())
                                <a href="{{ $categories->nextPageUrl() }}" class="custom-pagination-link">Next &raquo;</a>
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
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.delete-category-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;

                Swal.fire({
                    title: 'Xóa danh mục này?',
                    text: 'Hành động này sẽ không thể khôi phục.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Xác nhận xóa',
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
</body>
</html>
