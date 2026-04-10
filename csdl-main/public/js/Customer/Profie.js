const profileForm = document.getElementById('profileForm');
const passwordForm = document.getElementById('passwordForm');
const changePasswordBtn = document.getElementById('changePasswordBtn');
const backToProfileBtn = document.getElementById('backToProfileBtn');
const saveProfileBtn = document.getElementById('saveProfileBtn');
const savePasswordBtn = document.getElementById('savePasswordBtn');

changePasswordBtn.addEventListener('click', function() {
    profileForm.classList.add('hidden');
    passwordForm.classList.remove('hidden');
});

backToProfileBtn.addEventListener('click', function() {
    passwordForm.classList.add('hidden');
    profileForm.classList.remove('hidden');
});

saveProfileBtn.addEventListener('click', function() {
    const fullName = document.getElementById('fullName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const address = document.getElementById('address').value;

    alert('Đã lưu thông tin thành công!');
});

// Save password
savePasswordBtn.addEventListener('click', function() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        alert('Vui lòng điền đầy đủ thông tin!');
        return;
    }

    if (newPassword !== confirmPassword) {
        alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
        return;
    }

    alert('Đã đổi mật khẩu thành công!');

    document.getElementById('currentPassword').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    passwordForm.classList.add('hidden');
    profileForm.classList.remove('hidden');
});
