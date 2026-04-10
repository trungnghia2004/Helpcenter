<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fashion Admin - Quản Lý Cửa Hàng</title>
    <link rel="stylesheet" href="{{asset('css/admin/styles.css')}}">
    <link rel="stylesheet" href="{{asset('css/admin/stylePopup.css')}}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <title>Product Images | TrendyTeen</title>
    <style>
        .mainImage {
            background-color: #ffffff;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        /* Card Container */
        .image-card {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s ease;
        }

        .image-card:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        }

        /* Image Container */
        .image-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 100%; /* 1:1 Aspect Ratio */
            overflow: hidden;
        }

        .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Button Container */
        .button-container {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background-color: #f9f9f9;
        }

        /* Buttons */
        .btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-edit {
            background-color: #f0f0f0;
            color: #555;
        }

        .btn-edit:hover {
            background-color: #e0e0e0;
        }

        .btn-delete {
            background-color: #f0f0f0;
            color: #d32f2f;
        }

        .btn-delete:hover {
            background-color: #e0e0e0;
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
                <span class="logo-text">Fashion Admin</span>
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
                <a href="#orders" class="sidebar-link" data-section="orders">
                    <i class="fa-solid fa-palette"></i>
                    <span>Màu Sản Phẩm</span>
                </a>
                <a href="{{route('customer.index')}}" class="sidebar-link" data-section="customers">
                    <i class="fa-regular fa-user"></i>
                    <span>Khách Hàng</span>
                </a>
                <a href="#settings" class="sidebar-link" data-section="settings">
                    <i class="fa-solid fa-tags"></i>
                    <span>Chương Trình Giảm Giá</span>
                </a>
                <a href="#settings" class="sidebar-link" data-section="settings">
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
            <div style="display: flex; justify-content: space-between">
                <h2>Product Image</h2>
                <a href="{{route('products.index')}}" class="action-btn" style="display: block; margin: 0; "><i class="fa-solid fa-arrow-left"></i> Back to Products</a>
            </div>
            <br>
            <div class="mainImage">

                @foreach($productImage as $image)
                    <div class="image-card">
                        <div class="image-container">
                            <img src="{{ asset('storage/' . $image->imageLink) }}" alt="">
                        </div>
                        <div class="button-container">
                            <button  class="btn btn-edit"  onclick="openImageUploadForm({{ $image->imageID }})">
                                <i class="fas fa-edit"></i>
                                Sửa
                            </button>
                            <div class="form-overlay image-upload-overlay" id="imageUploadFormOverlay-{{ $image->imageID }}" style="display: none;">
                                <div class="form-container" style="height: 200px">
                                    <h2>Edit Product Images</h2>
                                    <form action="{{ route('product-images.update', $image->imageID) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <input type="file" name="newImage" required>
                                        <div style="display: flex; justify-content: space-between; gap: 10px; margin-top: 15px;">
                                            <button type="submit" class="submit-btn">Upload</button>
                                            <button type="button" class="cancel-btn submit-btn" onclick="closeImageUploadForm({{ $image->imageID }})" style="background-color: #dc3545;">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <form
                                action="{{ route('product-images.destroy', $image->imageID) }}"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this product?');"
                                class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-delete">
                                    <i class="fas fa-trash"></i>
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

    </div>
    {{-- End Main Content--}}
    <div class="right-section">
        <div class="nav">
            <button id="menu-btn"><span class="material-icons-sharp">menu</span></button>
            <div class="dark-mode">
                <span class="material-icons-sharp active">light_mode</span>
                <span class="material-icons-sharp">dark_mode</span>
            </div>
            <div class="profile">
                <div class="info">
                    <p>Hey, <b>{{\Illuminate\Support\Facades\Auth::user()->username}}</b></p>
                    <small class="text-muted">Admin</small>
                </div>
                <div class="profile-photo">
                    <img src="images/profile-1.jpg">
                </div>
            </div>
        </div>
        <!-- End of Nav -->
    </div>
    <!-- End of Right Section -->
</div>
<script src="{{asset('js/Admin/jsPopup.js')}}"></script>


</body>

</html>
