<div class="form-group">
    <label>Chọn sản phẩm</label>
    <div class="custom-select-search-wrapper">
        <input type="text" name="products[][search]" class="styled-input searchProduct" placeholder="Tìm kiếm sản phẩm theo mã..." autocomplete=off>
        <input type="hidden" name="products[][prdID]" class="prdID">
        <div class="suggestions-list"></div>
    </div>
</div>
<div class="form-group">
    <label>Chọn size</label>
    <select name="products[][sizeId]" class="styled-select sizeId">
        <option value="">-- Vui lòng chọn sản phẩm trước --</option>
    </select>
</div>
<div class="form-group">
    <label>Chọn màu</label>
    <select name="products[][colorId]" class="styled-select colorId">
        <option value="">-- Vui lòng chọn kích thước trước --</option>
    </select>
</div>
<div class="form-group">
    <label>Số lượng</label>
    <input type="number" name="products[][quantity]" placeholder="Số lượng" required>
</div>
