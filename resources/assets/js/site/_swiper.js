/**
 * Testimonial Carousel Initialization using npm Swiper
 */
import Swiper from 'swiper/bundle';
import 'swiper/css/bundle';

export function initTestimonialSwiper() {
  const container = document.querySelector('.testimonial-carousel');
  if (!container) return;

  new Swiper(container, {
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true,
    },
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    breakpoints: {
      640: {
        slidesPerView: 2,
        spaceBetween: 24,
      },
      1024: {
        slidesPerView: 3,
        spaceBetween: 28,
      },
      1280: {
        slidesPerView: 3,
        spaceBetween: 32,
      },
    },
  });
}
