<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendyTeen Admin - Quản Lý Cửa Hàng</title>
    <link rel="stylesheet" href="{{asset('css/admin/styles.css')}}">
    <link rel="stylesheet" href="{{asset('css/admin/stylePopup.css')}}">
    <link rel="stylesheet" href="{{asset('css/admin/additional-styles.css')}}">
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
                    <h1 class="section-title">Chi Tiết Sản Phẩm</h1>
                    <p class="section-description">Quản lý thông tin chi tiết sản phẩm và biến thể</p>
                </div>
            </div>

            <!-- Product Information Card -->
            <div class="product-info-card">
                <div class="card-header" style="display: flex; justify-content: space-between">
                    <h2 class="card-title">Thông Tin Sản Phẩm</h2>
                    <button class="dropdown-item"
                            onclick="openImageUploadForm({{ $infoProduct->productID }})"
                            style="background-color: #4CAF50; color: white; border: none;
               border-radius: 6px; padding: 8px 16px; cursor: pointer; text-align: center;">
                        Thêm hình ảnh
                    </button>
                </div>
                <div class="card-content">
                    <div class="product-detail-grid">
                        <!-- Product Images -->
                        <div class="product-images">
                            <div class="main-image">
                                @php 
                                    $firstImageLink = null;
                                    if (is_array($infoProduct->images)) {
                                        $firstImageLink = $infoProduct->images[0]->imageLink ?? null;
                                    } elseif (is_object($infoProduct->images) && method_exists($infoProduct->images, 'first')) {
                                        $firstImage = $infoProduct->images->first();
                                        $firstImageLink = $firstImage->imageLink ?? null;
                                    }
                                @endphp
                                @if($firstImageLink)
                                    <img src="{{ asset('storage/' . $firstImageLink) }}" alt="Anh san pham">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 8px; color: #6b7280;">
                                        Chua co anh
                                    </div>
                                @endif
                            </div>
                            <button id="prev-btn" class="left-2">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <button id="next-btn" class="right-2">
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <div class="thumbnail-grid">
                                @foreach($infoProduct->images as $index => $image)
                                    <div class="thumbnail-wrapper" style="position: relative; display: inline-block;">
                                        <img height="100px" width="100px" src="{{ asset('storage/' . $image->imageLink) }}" alt="Thumbnail {{ $index + 1 }}" class="thumbnail" style="border-radius: 4px;">

                                        <form method="POST" action="{{ route('product-images.destroy', ['image' => $image->imageID]) }}"
                                              class="delete-image-form"
                                              style="position: absolute; top: -10px; right: 20px;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="delete-image-btn"
                                                    style="background: rgba(128,128,128,0.8); border: none; color: white; font-weight: bold;
                   border-radius: 50%; width: 20px; height: 20px; cursor: pointer;
                   font-size: 14px; line-height: 18px; text-align: center; padding: 0;">
                                                &times;
                                            </button>
                                        </form>

                                    </div>
                                @endforeach
                            </div>


                        </div>

                        <!-- Product Details -->
                        <div class="product-details">
                            <h2 class="product-title">{{$infoProduct -> productName}}</h2>
                            <p class="product-description">
                                {{$infoProduct -> productDesc}}
                            </p>

                            <div class="product-meta-grid">
                                <div class="meta-item">
                                    <label>Mã Sản Phẩm</label>
                                    <span> {{$infoProduct -> productCode}}</span>
                                </div>
                                <div class="meta-item">
                                    <label>Danh Mục</label>
                                    <span> {{$infoProduct->category->categoryName ?? ($infoProduct->categoryName ?? 'N/A')}}</span>
                                </div>
                                <div class="meta-item">
                                    <label>Giá Bán</label>
                                    <span> {{number_format($infoProduct->productSellPrice, 0, ',', '.')}}đ</span>
                                </div>
                                <div class="meta-item">
                                    <label>Trạng Thái</label>
                                    <span class="status-badge in-stock">Đang Bán</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-overlay image-upload-overlay" id="imageUploadFormOverlay-{{ $infoProduct->productID }}" style="display: none;">
                <div class="form-container" style="height: 200px">
                    <h2>Thêm hình ảnh sản phẩm</h2>
                    <form action="{{ route('product-images.upload', $infoProduct->productID) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <input type="file" name="productImages[]" multiple accept="image/*" required>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 10px; margin-top: 15px;">
                            <button type="submit" class="submit-btn">Tải lên</button>
                            <button type="button" class="cancel-btn submit-btn" onclick="closeImageUploadForm({{ $infoProduct->productID }})" style="background-color: #dc3545;">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        <div class="table-container">
            <div class="table-wrapper">
                    <table class="data-table" id="products-table">
                        <thead>
                        <tr>
                            <th>Kích thước</th>
                            <th>Màu sắc</th>
                            <th>Số lượng</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody id="products-tbody">

                        @forelse ($productDetails as $productDetail)
                            <div id="formDetailsOverlay-{{ $productDetail->id }}" class="form-overlay">
                                <div class="form-container"  style="top: -10%;">
                                    <span onclick="closeDetailsForm({{ $productDetail->id }})" class="close-btn">×</span>
                                    <form action="{{ route('product-details.update' , $productDetail->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" id="prdID" name="prdID" value="{{$productDetail->prdID}}" hidden/>
                                        <div class="form-group">
                                            <label for="sizeId">Chọn kích thước sản phẩm</label>
                                            <select name="sizeId" id="sizeId" class="styled-select">
                                                @foreach($sizes as $size)
                                                    <option value="{{ $size->sizeId }}"
                                                        {{ $size->sizeId == $productDetail->sizeId ? 'selected' : '' }}>
                                                        {{ $size->sizeName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="colorId">Chọn màu sản phẩm </label>
                                            <select name="colorId" id="colorId" class="styled-select">
                                                @foreach($colors as $color)
                                                    <option value="{{ $color->colorId }}"
                                                        {{ $color->colorId == $productDetail->colorId ? 'selected' : '' }}>
                                                        {{ $color->colorName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="productQuantity">Số lượng sản phẩm</label>
                                            <input type="number" id="productQuantity" name="productQuantity" value="{{$productDetail->productQuantity}}" />
                                        </div>
                                        <button type="submit" class="submit-btn edit-submit-btn">Tạo</button>
                                    </form>
                                </div>
                            </div>
                            <tr>
                                <td>
                                    {{$productDetail->size->sizeName ?? 'N/A'}}
                                </td>
                                <td>{{$productDetail->color->colorName ?? 'N/A'}}</td>
                                <td>
                                    {{$productDetail->productQuantity ?? 'N/A'}}
                                </td>
                                <td style="text-align: left;">
                                    <button class="action-btn" onclick="showActionMenu(event)">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>
                                    <div class="dropdown-menu" id="action-dropdown">
                                        <button class="dropdown-item" onclick="openDetailsForm({{ $productDetail->id }})" style="border: none; background: white; width: 100% ;text-align: left">
                                            <span>Chỉnh sửa</span>
                                        </button>
                                        <hr>
                                        <form
                                            method="POST"
                                            class="delete-product-detail-form">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="dropdown-item delete-product-detail-btn"
                                                data-url="{{ route('product-details.destroy', $productDetail->id) }}"
                                                style="border: none; background: white; width: 100%; text-align: left"
                                                title="Delete product detail">
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
                    <div class="page-info">Page {{ $productDetails->currentPage() }} of {{ $productDetails->lastPage() }}</div>
                    <div class="page-links">
                        @if($productDetails->currentPage() > 1)
                            <a href="{{ $productDetails->previousPageUrl() }}" class="custom-pagination-link">&laquo; Previous</a>
                        @endif

                        @for($i = 1; $i <= $productDetails->lastPage(); $i++)
                            @if($i == $productDetails->currentPage())
                                <span class="custom-pagination-link current-page">{{ $i }}</span>
                            @else
                                <a href="{{ $productDetails->url($i) }}" class="custom-pagination-link">{{ $i }}</a>
                            @endif
                        @endfor

                        @if($productDetails->hasMorePages())
                            <a href="{{ $productDetails->nextPageUrl() }}" class="custom-pagination-link">Next &raquo;</a>
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
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.querySelector('.main-image img');

    let currentIndex = 0;
    const productImages = Array.from(thumbnails).map(thumb => thumb.src.replace('w=100&h=100', 'w=400&h=400'));

    // Click thumbnail
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', function () {
            currentIndex = index;
            updateMainImage(currentIndex);
        });
    });

    function updateMainImage(index) {
        mainImage.src = productImages[index];

        // Update active thumbnail
        thumbnails.forEach(t => t.classList.remove('active'));
        thumbnails[index].classList.add('active');
    }

    // Prev/Next
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    prevBtn.addEventListener('click', function () {
        currentIndex = (currentIndex - 1 + productImages.length) % productImages.length;
        updateMainImage(currentIndex);
    });

    nextBtn.addEventListener('click', function () {
        currentIndex = (currentIndex + 1) % productImages.length;
        updateMainImage(currentIndex);
    });

    // Khởi tạo ảnh đầu tiên là active
    updateMainImage(currentIndex);

</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.delete-product-detail-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;

                Swal.fire({
                    title: 'Xóa chi tiết sản phẩm?',
                    text: 'Thao tác này sẽ không thể hoàn tác.',
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
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.delete-image-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const form = button.closest('form');

                Swal.fire({
                    title: 'Xóa hình ảnh?',
                    text: 'Hành động này không thể hoàn tác!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Xác nhận xóa',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
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

