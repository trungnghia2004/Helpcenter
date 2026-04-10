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
            <a href="{{route('categories.index')}}" class="sidebar-link" data-section="products">
                <i class="fa-regular fa-rectangle-list"></i>
                <span>Danh Mục Sản Phẩm</span>
            </a>
            <a href="{{route('products.index')}}" class="sidebar-link active" data-section="products">
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
                    <h1 class="section-title">Sản Phẩm</h1>
                    <b class="section-description">Tổng số lượng sản phẩm của cửa hàng: {{$total}}</b>
                </div>
                <button onclick="openForm()" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Thêm sản phẩm
                </button>
                <div id="formOverlay" >
                    <div class="form-container" style="top: -10%;">
                        <span onclick="closeForm()" class="close-btn">×</span>
                        <form  id="productForm" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="productName">Tên sản phẩm</label>
                                <input type="text" id="productName" name="productName" placeholder="Tên sản phẩm" required>
                            </div>
                            <div class="form-group">
                                <label for="productSellPrice">Giá bán</label>
                                <input type="number" id="productSellPrice" name="productSellPrice" placeholder="Giá bán" required>
                            </div>
                            <div class="form-group">
                                <label for="productBuyPrice">Giá nhập</label>
                                <input type="number" id="productBuyPrice" name="productBuyPrice" placeholder="Giá mua" required>
                            </div>
                            <div class="form-group">
                                <label for="productForGender">Chọn giới tính</label>
                                <select name="productForGender" id="productForGender" class="styled-select">
                                    <option value="0">Nam</option>
                                    <option value="1">Nữ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="cateID">Chọn danh mục sản phẩm</label>
                                <select name="cateID" id="cateID" class="styled-select">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->categoryID }}">{{ $category->categoryName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="productDesc">Mô tả sản phẩm</label>
                                <textarea id="productDesc" name="productDesc" placeholder="Description"></textarea>
                            </div>

                            <button type="submit" class="submit-btn">Tạo</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <div class="search-box">
                        <form method="GET" action="{{ route('products.index') }}" style="margin-bottom: 16px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm..." id="product-search" />
                            <button type="submit" style="display: none"></button>
                        </form>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="products-table">
                        <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Giá</th>
                            <th>Giới tính</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody id="products-tbody">

                        @forelse ($products as $product)
                        <div id="editFormOverlay-{{ $product->productID }}" class="form-overlay">
                                <div class="form-container"  style="top: -10%;">
                                    <span onclick="closeEditForm({{ $product->productID }})" class="close-btn">×</span>
                                    <form action="{{ route('products.update', $product->productID) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-group">
                                            <label for="editProductName">Tên sản phẩm</label>
                                            <input type="text" id="editProductName" name="productName" value="{{ $product->productName }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editProductSellPrice">Giá bán</label>
                                            <input type="number" id="editProductSellPrice" name="productSellPrice" value="{{ $product->productSellPrice }}" />
                                        </div>
                                        <div class="form-group">
                                            <label for="editProductBuyPrice">Giá nhập</label>
                                            <input type="number" id="editProductBuyPrice" name="productBuyPrice" value="{{ $product->productBuyPrice }}" />
                                        </div>
                                        <div class="form-group">
                                            <label for="editProductForGender">Chọn giới tính</label>
                                            <select name="productForGender" id="editProductForGender" class="styled-select">
                                                <option value="0" {{ $product->productForGender == 0 ? 'selected' : '' }}>Nam</option>
                                                <option value="1" {{ $product->productForGender == 1 ? 'selected' : '' }}>Nữ</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="editCateID">Chọn danh mục sản phẩm</label>
                                            <select name="cateID" id="editCateID" class="styled-select">
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->categoryID }}"
                                                        {{ $category->categoryID == $product->cateID ? 'selected' : '' }}>
                                                        {{ $category->categoryName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="productDesc">Mô tả sản phẩm</label>
                                            <textarea id="productDesc" name="productDesc" placeholder="Description">{{$product->productDesc}}</textarea>
                                        </div>

                                        <button type="submit" class="submit-btn edit-submit-btn">Cập nhật</button>
                                    </form>
                                </div>
                            </div>
                        <div class="form-overlay image-upload-overlay" id="imageUploadFormOverlay-{{ $product->productID }}" style="display: none;">
                            <div class="form-container" style="height: 200px">
                                <h2>Thêm hình ảnh sản phẩm</h2>
                                <form action="{{ route('product-images.upload', $product->productID) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <input type="file" name="productImages[]" multiple accept="image/*" required>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; gap: 10px; margin-top: 15px;">
                                        <button type="submit" class="submit-btn">Tải lên</button>
                                        <button type="button" class="cancel-btn submit-btn" onclick="closeImageUploadForm({{ $product->productID }})" style="background-color: #dc3545;">Hủy</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div id="formDetailsOverlay-{{ $product->productID }}" class="form-overlay">
                            <div class="form-container"  style="top: -10%;">
                                <span onclick="closeDetailsForm({{ $product->productID }})" class="close-btn">×</span>
                                <form action="{{ route('product-details.store' , $product->productID) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="number" id="prdID" name="prdID" value="{{$product->productID}}" hidden/>
                                    <div class="form-group">
                                        <label for="sizeId">Chọn kích thước sản phẩm</label>
                                        <select name="sizeId" id="sizeId" class="styled-select">
                                            @foreach($sizes as $size)
                                                <option value="{{ $size->sizeId }}">
                                                    {{$size->sizeName}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="colorId">Chọn màu sản phẩm </label>
                                        <select name="colorId" id="colorId" class="styled-select">
                                            @foreach($colors as $color)
                                                <option value="{{ $color->colorId }}">
                                                    {{$color->colorName}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="productQuantity">Số lượng sản phẩm</label>
                                        <input type="number" id="productQuantity" name="productQuantity" placeholder="Nhập số lượng sản phẩm" />
                                    </div>
                                    <button type="submit" class="submit-btn edit-submit-btn">Tạo</button>
                                </form>
                            </div>
                        </div>
                        <div id="formExcelDetailsOverlay-{{ $product->productID }}" class="form-overlay">
                            <div class="form-container"  style="top: -10%;">
                                <span onclick="closeExcelDetailsForm({{ $product->productID }})" class="close-btn">×</span>
                                <form action="{{ route('product-details.store', $product->productID) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="excel_file" accept=".xls,.xlsx">
                            <br>
                            <br>
                            <button class="submit-btn edit-submit-btn" type="submit">Tạo</button>
                        </form>
                            </div>
                        </div>
                            <tr>
                                <td>
                                    <div class="cell-product">
                                        @if($product->firstImage == null)
                                            <img src="" alt="Vui lòng bổ sung hình ảnh sản phẩm" class="w-full h-64 object-cover">
                                        @else
                                            <img src="{{ asset('storage/' . $product->firstImage->imageLink) }}" alt="" class="w-full h-64 object-cover">
                                        @endif
                                            <h4 style="max-width: 200px"  >
                                                <a style="text-decoration: none; color: black" onmouseover="this.style.color='blue';"  onmouseout="this.style.color='black';"  href="{{ route('product-details.index', ['product' => $product->productID]) }}">
                                                    {{$product->productName}}
                                                </a>
                                            </h4>
                                    </div>
                                </td>
                                <td>{{number_format($product->productSellPrice, 0, ',', '.')}}đ</td>
                                <td>
                                    @if($product->productForGender == 0)
                                        <span>Nam</span>
                                    @else
                                        <span>Nữ</span>
                                    @endif
                                </td>
                                <td style="text-align: left;">
                                    <button class="action-btn" onclick="showActionMenu(event)">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>
                                    <div class="dropdown-menu" id="action-dropdown">
                                        <button class="dropdown-item" onclick="openDetailsForm({{ $product->productID }})" style="border: none; background: white; width: 100% ;text-align: left">Thêm chi tiết </button>
                                        <button class="dropdown-item" onclick="openExcelDetailsForm({{ $product->productID }})" style="border: none; background: white; width: 100% ;text-align: left">Nhập chi tiết từ Excel</button>
                                        <button class="dropdown-item" onclick="openImageUploadForm({{ $product->productID }})" style="border: none; background: white; width: 100% ;text-align: left">Thêm hình ảnh</button>
                                        <button class="dropdown-item" onclick="openEditForm({{ $product->productID }})" style="border: none; background: white; width: 100% ;text-align: left">
                                            <span>Chỉnh sửa</span>
                                        </button>
                                        <hr>
                                        <form
                                            action="{{ route('products.destroy', $product->productID) }}"
                                            method="POST"
                                            class="delete-product-form">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="dropdown-item delete-product-btn"
                                                data-url="{{ route('products.destroy', $product->productID) }}"
                                                style="border: none; background: white; width: 100%; text-align: left"
                                                title="Delete product">
                                                <span style="color: red">Xóa</span>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center;">Không có sản phẩm nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div style="padding: 0 10px" class="custom-pagination">
                        <div class="page-info">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</div>
                        <div class="page-links">
                            @if($products->currentPage() > 1)
                                <a href="{{ $products->previousPageUrl() }}" class="custom-pagination-link">&laquo; Previous</a>
                            @endif

                            @for($i = 1; $i <= $products->lastPage(); $i++)
                                @if($i == $products->currentPage())
                                    <span class="custom-pagination-link current-page">{{ $i }}</span>
                                @else
                                    <a href="{{ $products->url($i) }}" class="custom-pagination-link">{{ $i }}</a>
                                @endif
                            @endfor

                            @if($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" class="custom-pagination-link">Next &raquo;</a>
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
        document.querySelectorAll('.delete-product-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;

                Swal.fire({
                    title: 'Xóa sản phẩm này?',
                    text: 'Hành động này sẽ không thể hoàn tác.',
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
