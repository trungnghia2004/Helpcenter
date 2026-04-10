    // Star Rating Functionality
    const starButtons = document.querySelectorAll('.star-btn');
    const ratingValue = document.getElementById('rating-value');
    const ratingText = document.getElementById('rating-text');
    let currentRating = 0;

    const ratingTexts = {
    1: 'Rất tệ',
    2: 'Tệ',
    3: 'Bình thường',
    4: 'Tốt',
    5: 'Rất tốt'
};

    starButtons.forEach((button, index) => {
    button.addEventListener('click', function() {
        currentRating = parseInt(this.dataset.rating);
        ratingValue.value = currentRating;
        updateStars();
        ratingText.textContent = ratingTexts[currentRating];
    });

    button.addEventListener('mouseenter', function() {
    const hoverRating = parseInt(this.dataset.rating);
    highlightStars(hoverRating);
});
});

    document.querySelector('.space-y-6').addEventListener('mouseleave', function() {
    updateStars();
});

    function updateStars() {
    starButtons.forEach((button, index) => {
        const star = button.querySelector('i');
        if (index < currentRating) {
            star.className = 'fa-solid fa-star';
            button.className = 'star-btn text-2xl text-yellow-400 hover:text-yellow-400 transition-colors duration-200';
        } else {
            star.className = 'fa-solid fa-star';
            button.className = 'star-btn text-2xl text-gray-300 hover:text-yellow-400 transition-colors duration-200';
        }
    });
}

    function highlightStars(rating) {
    starButtons.forEach((button, index) => {
        const star = button.querySelector('i');
        if (index < rating) {
            button.className = 'star-btn text-2xl text-yellow-400 hover:text-yellow-400 transition-colors duration-200';
        } else {
            button.className = 'star-btn text-2xl text-gray-300 hover:text-yellow-400 transition-colors duration-200';
        }
    });
}

    // Character Count
    const commentTextarea = document.getElementById('review-comment');
    const charCount = document.getElementById('char-count');
    const maxLength = 500;

    commentTextarea.addEventListener('input', function() {
    const currentLength = this.value.length;
    charCount.textContent = `${currentLength}/${maxLength} ký tự`;

    if (currentLength > maxLength) {
    charCount.className = 'text-xs text-red-500';
    this.value = this.value.substring(0, maxLength);
    charCount.textContent = `${maxLength}/${maxLength} ký tự`;
} else if (currentLength > maxLength * 0.9) {
    charCount.className = 'text-xs text-yellow-600';
} else {
    charCount.className = 'text-xs text-gray-500';
}
});

    // Form Submission
    document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const rating = ratingValue.value;
    const name = document.getElementById('reviewer-name').value;
    const comment = commentTextarea.value;

    // Validation
    if (rating == 0) {
    alert('Vui lòng chọn số sao đánh giá');
    return;
}

    if (!name.trim()) {
    alert('Vui lòng nhập họ và tên');
    return;
}

    if (!comment.trim()) {
    alert('Vui lòng nhập nội dung đánh giá');
    return;
}

    // Simulate form submission
    const submitButton = document.getElementById('submit-review');
    const originalText = submitButton.innerHTML;

    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Đang gửi...</span>';
    submitButton.disabled = true;

    setTimeout(() => {
    // Reset form
    this.reset();
    currentRating = 0;
    ratingValue.value = 0;
    ratingText.textContent = 'Chọn số sao';
    charCount.textContent = '0/500 ký tự';
    updateStars();

    // Show success message
    document.getElementById('success-message').classList.remove('hidden');

    // Reset button
    submitButton.innerHTML = originalText;
    submitButton.disabled = false;

    // Scroll to success message
    document.getElementById('success-message').scrollIntoView({
    behavior: 'smooth',
    block: 'center'
});

    // Hide success message after 5 seconds
    setTimeout(() => {
    document.getElementById('success-message').classList.add('hidden');
}, 5000);

}, 2000);
});

    // Form validation on input
    const requiredFields = ['reviewer-name', 'review-comment'];
    const submitButton = document.getElementById('submit-review');

    function validateForm() {
    const rating = ratingValue.value;
    const name = document.getElementById('reviewer-name').value;
    const comment = commentTextarea.value;

    const isValid = rating > 0 && name.trim() && comment.trim();

    if (isValid) {
    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
    submitButton.disabled = false;
} else {
    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
    submitButton.disabled = true;
}
}

    // Add event listeners for real-time validation
    document.getElementById('reviewer-name').addEventListener('input', validateForm);
    commentTextarea.addEventListener('input', validateForm);
    starButtons.forEach(button => {
    button.addEventListener('click', validateForm);
});

    validateForm();
