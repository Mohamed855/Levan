/**
 * Master Entry Point for LOT Site Web
 */
import { initScrollReveal } from './_scroll-reveal.js';
import { initNavbar } from './_navbar.js';
import { initTestimonialSwiper } from './_swiper.js';
import { initContactForms } from './_contact.js';

document.addEventListener('DOMContentLoaded', () => {
  initScrollReveal();
  initNavbar();
  initTestimonialSwiper();
  initContactForms();

  // Dismiss page loading overlay
  const pageLoading = document.querySelector('.page-loading');
  if (pageLoading) {
    const hideLoader = () => {
      pageLoading.classList.add('is-hidden');
      setTimeout(() => pageLoading.remove(), 400);
    };

    if (document.readyState === 'complete') {
      hideLoader();
    } else {
      window.addEventListener('load', hideLoader);
      setTimeout(hideLoader, 2500); // Safety fallback
    }
  }
});
