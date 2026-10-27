/**
 * Master Entry Point for LOT Site Web
 */
import { initScrollReveal } from './_scroll-reveal.js';
import { initNavbar } from './_navbar.js';
import { initTestimonialSwiper } from './_swiper.js';
import { initContactForms } from './_contact.js';

document.addEventListener('DOMContentLoaded', () => {
  // Dismiss page loading overlay first so it never gets blocked by other scripts
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
      setTimeout(hideLoader, 1500); // Safety fallback
    }
  }

  try { initScrollReveal(); } catch (e) { console.error('Scroll reveal init error:', e); }
  try { initNavbar(); } catch (e) { console.error('Navbar init error:', e); }
  try { initTestimonialSwiper(); } catch (e) { console.error('Swiper init error:', e); }
  try { initContactForms(); } catch (e) { console.error('Contact forms init error:', e); }
});
