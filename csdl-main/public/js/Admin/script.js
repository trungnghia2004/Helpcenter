function showActionMenu(event) {
    event.stopPropagation();

    document.querySelectorAll('.dropdown-menu.show').forEach(el => {
    el.classList.remove('show');
});

    const dropdown = event.currentTarget.nextElementSibling;
    dropdown.classList.toggle('show');
}

    // Tự động đóng dropdown khi click ra ngoài
    document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu.show').forEach(el => {
        el.classList.remove('show');
    });
});
