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
            <a href="{{route('discount_programs.index')}}" class="sidebar-link active" data-section="settings">
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

            <section id="discount-programs-section" class="content-section active">
                <div class="section-header">
                    <div>
                        <h1 class="section-title">Chương Trình Giảm Giá</h1>
                        <p class="section-description">Danh sách tất cả chương trình khuyến mãi đang áp dụng.</p>
                    </div>
                    <button onclick="openForm()" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Thêm chương trình
                    </button>
                </div>
                <div id="formOverlay">
                    <div id="form-container">
                        <span onclick="closeForm()" class="close-btn">×</span>
                        <h2>Thêm chương trình giảm giá</h2>
                        <form id="discountForm" action="{{ route('discount_programs.store') }}" method="POST">
                            @csrf

                            <div class="form-group">
                                <label for="name">Tên chương trình</label>
                                <input type="text" id="name" name="name" placeholder="Nhập tên chương trình giảm giá" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Mô tả</label>
                                <textarea id="description" name="description" placeholder="Nhập mô tả chương trình"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="discount_type">Loại giảm giá</label>
                                <select id="discount_type" name="discount_type" required>
                                    <option value="percent">Phần trăm (%)</option>
                                    <option value="fixed">Số tiền cố định</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="discount_value">Giá trị giảm</label>
                                <input type="number" step="0.01" id="discount_value" name="discount_value" placeholder="Ví dụ: 10 hoặc 50000" required>
                            </div>

                            <div class="form-group">
                                <label for="max_discount">Giảm tối đa (nếu có)</label>
                                <input type="number" step="0.01" id="max_discount" name="max_discount" placeholder="Ví dụ: 100000">
                            </div>
                            <div class="form-group">
                                <label for="start_date">Ngày bắt đầu</label>
                                <input type="date" id="start_date" name="start_date" required>
                            </div>

                            <div class="form-group">
                                <label for="end_date">Ngày kết thúc</label>
                                <input type="date" id="end_date" name="end_date" required>
                            </div>
                            <button type="submit" class="submit-btn">Tạo chương trình khuyến mãi</button>
                        </form>

                    </div>
                </div>


                <div class="table-container">

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                            <tr>
                                <th>Tên chương trình</th>
                                <th>Loại giảm</th>
                                <th>Giá trị giảm</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Hành động</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($programs as $program)
                                <div id="editFormOverlay-{{ $program->id }}" class="form-overlay" style="display: none;">
                                    <div class="form-containers">
                                        <span onclick="closeEditForm({{ $program->id }})" class="close-btn">×</span>
                                        <form action="{{ route('discount_programs.update', $program->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')

                                            <div class="form-group">
                                                <label for="name-{{ $program->id }}">Tên chương trình</label>
                                                <input type="text" id="name-{{ $program->id }}" name="name"
                                                       value="{{ $program->name }}" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="description-{{ $program->id }}">Mô tả</label>
                                                <textarea id="description-{{ $program->id }}" name="description">{{ $program->description }}</textarea>
                                            </div>

                                            <div class="form-group">
                                                <label for="discount_type-{{ $program->id }}">Loại giảm giá</label>
                                                <select id="discount_type-{{ $program->id }}" name="discount_type" required>
                                                    <option value="percent" {{ $program->discount_type === 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
                                                    <option value="fixed" {{ $program->discount_type === 'fixed' ? 'selected' : '' }}>Số tiền cố định</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="discount_value-{{ $program->id }}">Giá trị giảm</label>
                                                <input type="number" step="0.01" id="discount_value-{{ $program->id }}" name="discount_value"
                                                       value="{{ $program->discount_value }}" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="max_discount-{{ $program->id }}">Giảm tối đa (nếu có)</label>
                                                <input type="number" step="0.01" id="max_discount-{{ $program->id }}" name="max_discount"
                                                       value="{{ $program->max_discount }}">
                                            </div>

                                            <div class="form-group">
                                                <label for="start_date-{{ $program->id }}">Ngày bắt đầu</label>
                                                <input type="date" id="start_date-{{ $program->id }}" name="start_date"
                                                       value="{{ \Carbon\Carbon::parse($program->start_date)->format('Y-m-d') }}" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="end_date-{{ $program->id }}">Ngày kết thúc</label>
                                                <input type="date" id="end_date-{{ $program->id }}" name="end_date"
                                                       value="{{ \Carbon\Carbon::parse($program->end_date)->format('Y-m-d') }}" required>
                                            </div>

                                            <button type="submit" class="submit-btn edit-submit-btn">Cập nhật</button>
                                        </form>
                                    </div>
                                </div>
                                <tr>
                                    <td>{{ $program->name }}</td>
                                    <td>{{ $program->discount_type === 'percent' ? 'Phần trăm' : 'Cố định' }}</td>
                                    <td>
                                        {{ $program->discount_type === 'percent'
                                            ? number_format($program->discount_value) . '%'
                                            : number_format($program->discount_value, 0, ',', '.') . ' VNĐ' }}
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($program->start_date)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($program->end_date)->format('d/m/Y') }}</td>


                                    <td>
                                        <button class="action-btn" onclick="showActionMenu(event)">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <div class="dropdown-menu" id="action-dropdown">
                                            <button class="dropdown-item" onclick="openEditForm({{ $program->id }})" style="border: none; background: white; width: 100% ;text-align: left">
                                                <span>Chỉnh sửa</span>
                                            </button>
                                            <hr>
                                            <form action="{{ route('discount_programs.destroy', $program->id) }}"
                                                  method="POST"
                                                  class="delete-discount-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        class="dropdown-item delete-discount-btn"
                                                        style="border: none; background: white; width: 100%; text-align: left"
                                                        data-url="{{ route('discount_programs.destroy', $program->id) }}">
                                                    <span style="color: red">Xóa</span>
                                                </button>
                                            </form>


                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @if($programs->isEmpty())
                                <tr>
                                    <td colspan="6" style="text-align: center;">Không có chương trình khuyến mãi nào.</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

    </div>
</div>
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <p>Bạn có chắc chắn muốn xóa chương trình khuyến mãi này?</p>
        <div class="modal-actions">
            <button id="confirmDelete" class="confirm-btn">Xóa</button>
            <button onclick="closeModal()" class="cancel-btn">Hủy</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.delete-discount-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;

                Swal.fire({
                    title: 'Xóa chương trình khuyến mãi?',
                    text: 'Hành động này sẽ không thể hoàn tác!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Xóa',
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
