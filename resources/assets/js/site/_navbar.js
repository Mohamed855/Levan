/**
 * Navbar, Header Sticky, Back-to-Top & Mobile Navigation Drawer
 */

export function initNavbar() {
  const header = document.querySelector('.site-header');
  const backToTop = document.querySelector('.back-to-top');
  const hamburger = document.querySelector('.site-hamburger');
  const drawer = document.querySelector('.site-mobile-drawer');
  const drawerClose = document.querySelector('.site-mobile-close');
  const drawerLinks = document.querySelectorAll('.site-mobile-nav a');

  // Sticky header & Back to top visibility
  function handleScroll() {
    const scrollY = window.scrollY;

    if (header) {
      if (scrollY > 50) {
        header.classList.add('is-sticky');
      } else {
        header.classList.remove('is-sticky');
      }
    }

    if (backToTop) {
      if (scrollY > 400) {
        backToTop.classList.add('is-visible');
      } else {
        backToTop.classList.remove('is-visible');
      }
    }
  }

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  // Back to top click
  if (backToTop) {
    backToTop.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // Mobile Drawer Toggle
  if (hamburger && drawer) {
    hamburger.addEventListener('click', () => {
      drawer.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    });

    const closeDrawer = () => {
      drawer.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    drawerClose?.addEventListener('click', closeDrawer);

    drawer.addEventListener('click', (e) => {
      if (e.target === drawer) closeDrawer();
    });

    drawerLinks.forEach((link) => {
      link.addEventListener('click', closeDrawer);
    });
  }

  // Active link highlighting on scroll
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.site-nav a');

  if (sections.length && navLinks.length) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const id = entry.target.getAttribute('id');
            navLinks.forEach((link) => {
              const href = link.getAttribute('href');
              if (href && (href === `#${id}` || href.endsWith(`#${id}`))) {
                link.classList.add('is-active');
              } else {
                link.classList.remove('is-active');
              }
            });
          }
        });
      },
      { rootMargin: '-20% 0px -70% 0px' }
    );

    sections.forEach((s) => observer.observe(s));
  }
}
