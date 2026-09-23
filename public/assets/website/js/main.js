"use strict";

// Page loading
var pageLoading = document.querySelector(".page-loading");

if (pageLoading) {
    window.addEventListener("load", () => {
        pageLoading.classList.add("hide");

        setTimeout(() => {
            pageLoading.style.display = "none";
        }, 1000);
    });
}

// Navbar
const navbar = document.querySelector(".ic-navbar"),
    navbarToggler = navbar.querySelector("[data-web-toggle=navbar-collapse]"),
    navbarLogo = navbar.querySelector("[data-default-logo][data-sticky-logo]");

function syncThemeLogo(theme) {
    if (!navbarLogo) {
        return;
    }

    const logoSrc =
        theme === "dark"
            ? navbarLogo.dataset.darkLogo
            : navbarLogo.dataset.lightLogo;

    if (logoSrc && navbarLogo.getAttribute("src") !== logoSrc) {
        navbarLogo.setAttribute("src", logoSrc);
    }
}

if (navbarToggler) {
    navbarToggler.addEventListener("click", function () {
        const dataTarget = this.dataset.webTarget,
            targetElement = document.getElementById(dataTarget),
            isExpanded = this.ariaExpanded === "true";

        if (!targetElement) {
            return;
        }

        navbar.classList.toggle("menu-show");
        this.ariaExpanded = !isExpanded;
        navbarToggler.innerHTML = navbar.classList.contains("menu-show")
            ? '<i class="ti ti-close"></i>'
            : '<i class="ti ti-menu"></i>';
    });
}

// Web theme
const webTheme = document.querySelector("[data-web-trigger=web-theme]"),
    html = document.querySelector("html");

window.addEventListener("load", function () {
    var theme = localStorage.getItem("Inazuma_WebTheme");

    if (theme == "light") {
        webTheme.innerHTML = '<i class="ti ti-sun"></i>';
    } else if (theme == "dark") {
        webTheme.innerHTML = '<i class="ti ti-moon"></i>';
    } else {
        theme = "light";
        localStorage.setItem("Inazuma_WebTheme", theme);
        webTheme.innerHTML = '<i class="ti ti-moon"></i>';
    }

    html.dataset.webTheme = theme;
    syncThemeLogo(theme);
});

webTheme.addEventListener("click", function () {
    var theme = localStorage.getItem("Inazuma_WebTheme");

    webTheme.innerHTML =
        theme == "dark"
            ? '<i class="ti ti-sun"></i>'
            : '<i class="ti ti-moon"></i>';
    theme = theme == "dark" ? "light" : "dark";
    localStorage.setItem("Inazuma_WebTheme", theme);
    html.dataset.webTheme = theme;
    syncThemeLogo(theme);
});

// Scrollspy
function scrollspy(event) {
    var links = document.querySelectorAll(".ic-page-scroll"),
        scrollpos =
            window.pageYOffset ||
            document.documentElement.scrollTop ||
            document.body.scrollTop;

    for (let i = 0; i < links.length; i++) {
        var currentLink = links[i],
            href = currentLink.getAttribute("href"),
            hash = href.includes("#") ? "#" + href.split("#")[1] : null, // ← استخرج الـ hash فقط
            targetElement = hash ? document.querySelector(hash) : null,
            topminus = scrollpos + 74;

        if (targetElement) {
            if (
                targetElement.offsetTop <= topminus &&
                targetElement.offsetTop + targetElement.offsetHeight > topminus
            ) {
                document
                    .querySelector(".ic-page-scroll")
                    .classList.remove("active");
                currentLink.classList.add("active");
            } else {
                currentLink.classList.remove("active");
            }
        }
    }
}

window.document.addEventListener("scroll", scrollspy);

// Menu scroll
const pageLink = document.querySelectorAll(".ic-page-scroll");

pageLink.forEach((link) => {
    link.addEventListener("click", function (e) {
        const href = link.getAttribute("href");
        const url = new URL(href, window.location.origin);

        // رابط لصفحة مختلفة — اتركه يعمل بشكل طبيعي
        if (url.pathname !== window.location.pathname) {
            return;
        }

        e.preventDefault();
        const hash = url.hash; // ← استخرج الـ hash فقط مثل "#home"
        const targetElement = hash ? document.querySelector(hash) : null;

        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: "smooth",
                offsetTop: 1 - 74,
            });
        }

        navbar.classList.remove("menu-show");
        navbarToggler.innerHTML = navbar.classList.contains("menu-show")
            ? '<i class="ti ti-close"></i>'
            : '<i class="ti ti-menu"></i>';
    });
});

// Tabs
const tabs = document.querySelectorAll(".tabs");

tabs.forEach((tab) => {
    const links = tab.querySelectorAll(".tabs-nav .tabs-link"),
        contents = tab.querySelectorAll(".tabs-content");

    if (!contents) {
        return;
    }

    window.addEventListener("load", function () {
        for (let i = 0; i < contents.length; i++) {
            contents[i].classList.add("hide");
        }

        for (let i = 0; i < links.length; i++) {
            links[i].classList.remove("active");
            links[i].ariaSelected = false;
        }

        links[0].classList.add("active");
        links[0].ariaSelected = true;

        const dataTarget = links[0].dataset.webTarget,
            targetElement = this.document.getElementById(dataTarget);

        targetElement.classList.remove("hide");
    });

    links.forEach((link) => {
        const dataTarget = link.dataset.webTarget,
            targetElement = document.getElementById(dataTarget);

        if (targetElement) {
            link.addEventListener("click", function () {
                for (let i = 0; i < contents.length; i++) {
                    contents[i].classList.add("hide");
                }

                for (let i = 0; i < links.length; i++) {
                    links[i].classList.remove("active");
                    links[i].ariaSelected = false;
                }

                link.classList.add("active");
                link.ariaSelected = true;
                targetElement.classList.remove("hide");
            });
        } else {
            link.disabled = true;
        }
    });
});

// Portfolio filter
const portfolioFilters = document.querySelectorAll(".portfolio-menu button");

portfolioFilters.forEach((filter) => {
    filter.addEventListener("click", function () {
        let btn = portfolioFilters[0];

        while (btn) {
            if (btn.tagName === "BUTTON") {
                btn.classList.remove("active");
            }

            btn = btn.nextSibling;
        }

        this.classList.add("active");

        let selected = filter.getAttribute("data-filter"),
            itemsToHide = document.querySelectorAll(
                '.portfolio-grid .portfolio :not([data-filter="' +
                    selected +
                    '"])',
            ),
            itemsToShow = document.querySelectorAll(
                '.portfolio-grid .portfolio [data-filter="' + selected + '"]',
            );

        if (selected == "all") {
            itemsToHide = [];
            itemsToShow = document.querySelectorAll(
                ".portfolio-grid .portfolio [data-filter]",
            );
        }

        itemsToHide.forEach((el) => {
            el.parentElement.classList.add("hide");
            el.parentElement.classList.remove("show");
        });

        itemsToShow.forEach((el) => {
            el.parentElement.classList.remove("hide");
            el.parentElement.classList.add("show");
        });
    });
});

// Scroll to top
var st = document.querySelector("[data-web-trigger=scroll-top]");

if (st) {
    window.onscroll = function () {
        if (
            document.body.scrollTop > 50 ||
            document.documentElement.scrollTop > 50
        ) {
            st.classList.remove("is-hided");
        } else {
            st.classList.add("is-hided");
        }
    };

    st.addEventListener("click", function () {
        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    });
}
