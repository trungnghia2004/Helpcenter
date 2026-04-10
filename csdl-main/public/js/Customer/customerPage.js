const slider = document.querySelector('.new-arrival-slider');
const prevBtn = document.querySelector('.new-arrival-prev');
const nextBtn = document.querySelector('.new-arrival-next');
let currentIndex = 0;
const slides = document.querySelectorAll('.new-arrival-slider > div');
const slideCount = slides.length;

function updateSlider() {
    const slideWidth = slides[0].offsetWidth;
    slider.style.transform = `translateX(-${currentIndex * slideWidth}px)`;
}

nextBtn.addEventListener('click', () => {
    if (currentIndex < slideCount - 1) {
        currentIndex++;
        updateSlider();
    }
});

prevBtn.addEventListener('click', () => {
    if (currentIndex > 0) {
        currentIndex--;
        updateSlider();
    }
});

window.addEventListener('resize', updateSlider);

const mobileMenuBtn = document.querySelector('.md\\:hidden');
mobileMenuBtn.addEventListener('click', () => {
    alert('Mobile menu would open here in a full implementation');
});


