/**
 * Navbar, Header Sticky, Back-to-Top & Mobile Navigation Drawer
 */

export function initNavbar() {
  const header = document.querySelector('.site-header');
  const backToTop = document.querySelector('.back-to-top');
  const hamburger = document.querySelector('.site-hamburger');
  const drawer = document.querySelector('.site-mobile-drawer');
  const drawerClose = document.querySelector('.site-mobile-close');

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
  const closeDrawer = () => {
    if (drawer) {
      drawer.classList.remove('is-open');
      document.body.style.overflow = '';
    }
  };

  if (hamburger && drawer) {
    hamburger.addEventListener('click', () => {
      drawer.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    });

    drawerClose?.addEventListener('click', closeDrawer);

    drawer.addEventListener('click', (e) => {
      if (e.target === drawer) closeDrawer();
    });
  }

  // Smooth scroll helper without modifying URL
  function scrollToSection(targetId) {
    if (!targetId || targetId === 'home') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
      document.documentElement.scrollTo?.({ top: 0, behavior: 'smooth' });
      return true;
    }

    const targetElement = document.getElementById(targetId);
    if (!targetElement) return false;

    const headerHeight = header ? header.getBoundingClientRect().height : 0;
    const offset = headerHeight + 16;
    const currentScroll = window.scrollY || document.documentElement.scrollTop || 0;
    const targetTop = targetElement.getBoundingClientRect().top + currentScroll - offset;

    window.scrollTo({
      top: Math.max(0, targetTop),
      behavior: 'smooth',
    });

    return true;
  }

  // Resolve target section ID for in-page anchors
  function getAnchorTargetId(link) {
    const rawHref = link.getAttribute('href');
    if (!rawHref) return null;

    // Direct hash e.g. href="#home" or href="#pricing"
    if (rawHref.startsWith('#')) {
      const id = rawHref.substring(1);
      return id && id !== '!' ? id : null;
    }

    // Relative path with hash, e.g. "/#home" or "./#home"
    if (rawHref.startsWith('/#') || rawHref.startsWith('./#')) {
      const id = rawHref.split('#')[1];
      if (!id || id === '!') return null;
      const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
      if (currentPath === '/' || document.getElementById(id) || id === 'home') {
        return id;
      }
      return null;
    }

    // Full URL or relative URL containing a hash
    const hashIdx = rawHref.indexOf('#');
    if (hashIdx !== -1) {
      const id = rawHref.substring(hashIdx + 1);
      if (!id || id === '!') return null;

      try {
        const url = new URL(link.href, window.location.href);
        const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
        const linkPath = url.pathname.replace(/\/$/, '') || '/';

        const isSamePath = linkPath === currentPath;
        if (isSamePath || (currentPath === '/' && (document.getElementById(id) || id === 'home'))) {
          return id;
        }
      } catch {
        if (document.getElementById(id) || id === 'home') {
          return id;
        }
      }
    }

    return null;
  }

  // Intercept navigation link clicks in capture phase to prevent URL hash mutation completely
  document.addEventListener(
    'click',
    (e) => {
      const link = e.target.closest('a[href]');
      if (!link) return;

      const targetId = getAnchorTargetId(link);
      if (targetId) {
        const targetElement = targetId === 'home' ? true : document.getElementById(targetId);
        if (targetElement) {
          // Immediately prevent the browser from updating the URL hash
          e.preventDefault();
          e.stopPropagation();

          scrollToSection(targetId);
          closeDrawer();

          // Safety check: ensure hash never remains in address bar
          if (window.location.hash) {
            history.replaceState(null, '', window.location.pathname + window.location.search);
          }
        }
      }
    },
    true // Capture phase: intercepted before any default browser anchor navigation
  );

  // If page was loaded with a hash in URL, strip it immediately from the address bar
  if (window.location.hash) {
    const hashId = window.location.hash.substring(1);
    // Instant synchronous URL cleanup so the hash never flickers
    history.replaceState(null, '', window.location.pathname + window.location.search);
    setTimeout(() => {
      scrollToSection(hashId);
    }, 100);
  }

  // Active link highlighting on scroll
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.site-nav a, .site-mobile-nav a');

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
