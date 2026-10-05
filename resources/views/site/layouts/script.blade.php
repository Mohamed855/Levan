<script src="{{ asset('static/site/js/cdn/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('static/site/js/cdn/glightbox.min.js') }}"></script>
<script src="{{ asset('static/site/js/cdn/scrollreveal.min.js') }}"></script>

<script src="{{ asset('static/site/js/main.js') }}?v={{ filemtime(public_path('static/site/js/main.js')) }}"></script>

<script>
    // Scroll Reveal
    const sr = ScrollReveal({
        origin: "bottom",
        distance: "16px",
        duration: 1000,
        reset: false,
    });

    sr.reveal(`.scroll-revealed`, {
        cleanup: true,
    });

    // GLightBox
    GLightbox({
        selector: ".video-popup",
        href: "https://www.youtube.com/watch?v=r44RKWyfcFw&fbclid=IwAR21beSJORalzmzokxDRcGfkZA1AtRTE__l5N4r09HcGS5Y6vOluyouM9EM",
        type: "video",
        source: "youtube",
        width: 900,
        autoplayVideos: true,
    });

    const myGallery3 = GLightbox({
        selector: ".portfolio-box",
        type: "image",
        width: 900,
    });

    // Testimonial
    const testimonialSwiper = new Swiper(".testimonial-carousel", {
        slidesPerView: 1,
        spaceBetween: 30,

        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },

        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 30,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
            1280: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
    });
</script>
