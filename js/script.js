
// Navbar Scroll Effect
const navbar = document.getElementById('navbar');

window.onscroll = () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
};

// Carousel Scrolling Logic
function scrollCarousel(carouselId, distance) {
    const container = document.getElementById(carouselId);
    container.scrollBy({
        left: distance,
        behavior: 'smooth'
    });
}

// Modal Logic
const modal = document.getElementById('video-modal');
const btnAssistir = document.getElementById('btn-assistir');
const closeModal = document.querySelector('.close-modal');

if (btnAssistir && modal && closeModal) {
    btnAssistir.addEventListener('click', () => {
        modal.classList.add('active');
        // Stop body scrolling when modal is open
        document.body.style.overflow = 'hidden';
    });

    closeModal.addEventListener('click', () => {
        closeVideoModal();
    });

    // Close on click outside modal content
    window.addEventListener('click', (e) => {
        if (e.target == modal) {
            closeVideoModal();
        }
    });

    function closeVideoModal() {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';

        // Stop video playback on close (reset iframe src)
        const iframe = modal.querySelector('iframe');
        if (iframe) {
            const src = iframe.src;
            iframe.src = src;
        }
    }
}

// Mobile Menu Logic
const hamburger = document.getElementById('hamburger-menu');
const navLinks = document.getElementById('nav-links');

if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });
}

console.log('Netflix Cursos Script Loaded');
