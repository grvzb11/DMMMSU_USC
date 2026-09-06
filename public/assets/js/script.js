/**
 * Developed by: George Rexy Vincent Z. Bacani
 * College: College of Information Technology
 * Role: System Developer / Front-End Developer
 * Development Year: 2026–2027
 * Institution: Don Mariano Marcos Memorial State University
 * Version: v1.0
 * Email: rexygeorge11@gmail.com
 * Copyright: © 2026–2027. All rights reserved.
 */

document.addEventListener("DOMContentLoaded", () => {

    // Professional homepage newsroom
    const professionalNewsItems = [...document.querySelectorAll(".professional-news-item")];
    const professionalSearch = document.getElementById("newsSearch");
    const professionalFilters = [...document.querySelectorAll(".professional-filters .news-filter")];
    const professionalPrev = document.getElementById("newsPrev");
    const professionalNext = document.getElementById("newsNext");
    const professionalPages = document.getElementById("newsPageNumbers");
    const professionalEmpty = document.getElementById("newsEmpty");

    const professionalPerPage = 5;
    let professionalPage = 0;
    let professionalFilter = "all";
    let professionalQuery = "";

    function getProfessionalFilteredItems() {
        return professionalNewsItems.filter(item => {
            const categoryMatch = professionalFilter === "all" || item.dataset.category === professionalFilter;
            const textMatch = !professionalQuery || item.textContent.toLowerCase().includes(professionalQuery);
            return categoryMatch && textMatch;
        });
    }

    function renderProfessionalNews(page = 0) {
        if (!professionalNewsItems.length) return;
        const filtered = getProfessionalFilteredItems();
        const totalPages = Math.ceil(filtered.length / professionalPerPage);

        professionalPage = totalPages ? Math.max(0, Math.min(page, totalPages - 1)) : 0;
        const startIndex = professionalPage * professionalPerPage;
        const visible = new Set(filtered.slice(startIndex, startIndex + professionalPerPage));

        professionalNewsItems.forEach(item => {
            item.hidden = !visible.has(item);
        });

        if (professionalEmpty) professionalEmpty.hidden = filtered.length !== 0;

        if (professionalPrev) {
            professionalPrev.hidden = professionalPage === 0 || totalPages <= 1;
        }

        if (professionalNext) {
            professionalNext.hidden = professionalPage >= totalPages - 1 || totalPages <= 1;
        }

        if (professionalPages) {
            professionalPages.innerHTML = "";
            if (totalPages > 1) {
                for (let i = 0; i < totalPages; i++) {
                    const pageButton = document.createElement("button");
                    pageButton.type = "button";
                    pageButton.className = "pagination-page" + (i === professionalPage ? " active" : "");
                    pageButton.textContent = String(i + 1);
                    pageButton.setAttribute("aria-label", `Go to page ${i + 1}`);
                    if (i === professionalPage) pageButton.setAttribute("aria-current", "page");
                    pageButton.addEventListener("click", () => renderProfessionalNews(i));
                    professionalPages.appendChild(pageButton);
                }
            }
        }

        const pagination = document.getElementById("newsPagination");
        if (pagination) pagination.hidden = totalPages <= 1;
    }

    professionalFilters.forEach(button => {
        button.addEventListener("click", () => {
            professionalFilter = button.dataset.filter;
            professionalFilters.forEach(btn => btn.classList.toggle("active", btn === button));
            renderProfessionalNews(0);
        });
    });

    professionalSearch?.addEventListener("input", () => {
        professionalQuery = professionalSearch.value.trim().toLowerCase();
        renderProfessionalNews(0);
    });

    professionalPrev?.addEventListener("click", () => renderProfessionalNews(professionalPage - 1));
    professionalNext?.addEventListener("click", () => renderProfessionalNews(professionalPage + 1));

    if (professionalNewsItems.length) {
        renderProfessionalNews(0);
    }


    // Navbar dropdowns: click/touch support
    document.querySelectorAll(".nav-dropdown > .nav-drop-btn").forEach(button => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            const parent = button.closest(".nav-dropdown");

            document.querySelectorAll(".nav-dropdown.open").forEach(drop => {
                if (drop !== parent) drop.classList.remove("open");
            });

            parent.classList.toggle("open");
        });
    });

    document.addEventListener("click", (event) => {
        if (!event.target.closest(".nav-dropdown")) {
            document.querySelectorAll(".nav-dropdown.open").forEach(drop => drop.classList.remove("open"));
        }
    });


    // USC newsroom search and campus filters
    const newsSearch = document.getElementById("newsSearch");
    const newsFilterButtons = [...document.querySelectorAll("[data-news-filter]")];
    const newsItems = [...document.querySelectorAll(".news-item")];
    const newsEmpty = document.getElementById("newsEmpty");
    let newsCampus = "all";

    function applyNewsFilters() {
        const term = (newsSearch?.value || "").trim().toLowerCase();
        let visible = 0;

        newsItems.forEach(item => {
            const campusMatch = newsCampus === "all" || item.dataset.newsCampus === newsCampus;
            const textMatch = !term || item.textContent.toLowerCase().includes(term);
            const show = campusMatch && textMatch;
            item.hidden = !show;
            if (show) visible++;
        });

        if (newsEmpty) newsEmpty.hidden = visible !== 0;
    }

    newsFilterButtons.forEach(button => {
        button.addEventListener("click", () => {
            newsFilterButtons.forEach(b => b.classList.remove("active"));
            button.classList.add("active");
            newsCampus = button.dataset.newsFilter;
            applyNewsFilters();
        });
    });

    newsSearch?.addEventListener("input", applyNewsFilters);

    // Mobile navigation: stable right-side drawer.
    const navToggle = document.getElementById("navToggle");
    const mainNav = document.getElementById("mainNav");

    if (navToggle && mainNav && mainNav.dataset.mobileDrawerReady !== "1") {
        mainNav.dataset.mobileDrawerReady = "1";

        let drawerHead = mainNav.querySelector(".mobile-nav-head");
        if (!drawerHead) {
            drawerHead = document.createElement("div");
            drawerHead.className = "mobile-nav-head";
            drawerHead.innerHTML = '<strong>Menu</strong><button class="mobile-nav-close" type="button" aria-label="Close menu">×</button>';
            mainNav.prepend(drawerHead);
        }

        // Keep the desktop CTA where it is, but mirror it inside the mobile drawer.
        const desktopAction = document.querySelector(".site-header .submit-cta, .site-header .header-cta");
        let mobileAction = mainNav.querySelector(".mobile-nav-action");
        if (!mobileAction && desktopAction) {
            mobileAction = document.createElement("a");
            mobileAction.className = "mobile-nav-action";
            mobileAction.href = desktopAction.getAttribute("href") || "#";
            mobileAction.textContent = (desktopAction.textContent || "Open").trim();
            mainNav.append(mobileAction);
        }

        let backdrop = document.getElementById("mobileNavBackdrop");
        if (!backdrop) {
            backdrop = document.createElement("button");
            backdrop.id = "mobileNavBackdrop";
            backdrop.className = "mobile-nav-backdrop";
            backdrop.type = "button";
            backdrop.tabIndex = -1;
            backdrop.setAttribute("aria-label", "Close navigation menu");
            const navLayerHost = mainNav.closest(".site-header") || document.body;
            navLayerHost.append(backdrop);
        }

        let isOpen = false;
        const setMobileNav = (open, restoreFocus = false) => {
            isOpen = Boolean(open);
            mainNav.classList.toggle("open", isOpen);
            backdrop.classList.toggle("open", isOpen);
            document.body.classList.toggle("mobile-nav-open", isOpen);
            navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
            navToggle.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");

            if (!isOpen) {
                mainNav.querySelectorAll(".nav-dropdown.open").forEach(item => item.classList.remove("open"));
                if (restoreFocus) window.setTimeout(() => navToggle.focus({preventScroll:true}), 30);
            } else {
                window.setTimeout(() => mainNav.querySelector(".mobile-nav-close")?.focus({preventScroll:true}), 120);
            }
        };

        navToggle.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();
            setMobileNav(!isOpen);
        });

        drawerHead.querySelector(".mobile-nav-close")?.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();
            setMobileNav(false, true);
        });

        backdrop.addEventListener("click", event => {
            event.preventDefault();
            setMobileNav(false, true);
        });

        mainNav.querySelectorAll("a").forEach(link => link.addEventListener("click", () => setMobileNav(false)));

        document.addEventListener("keydown", event => {
            if (event.key === "Escape" && isOpen) setMobileNav(false, true);
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth > 900 && isOpen) setMobileNav(false);
        });
    }

    // Automatic hero slideshow
    const slides = [...document.querySelectorAll(".slide")];
    const dots = [...document.querySelectorAll(".dot")];
    const prev = document.getElementById("prevSlide");
    const next = document.getElementById("nextSlide");
    const progress = document.getElementById("autoplayProgress");

    let current = 0;
    let timer = null;
    const duration = 5500;

    function animateProgress() {
        if (!progress) return;
        progress.style.transition = "none";
        progress.style.width = "0%";
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                progress.style.transition = `width ${duration}ms linear`;
                progress.style.width = "100%";
            });
        });
    }

    function showSlide(index, restart = true) {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle("active", i === current));
        dots.forEach((dot, i) => dot.classList.toggle("active", i === current));
        if (restart) startAuto();
    }

    function startAuto() {
        clearInterval(timer);
        animateProgress();
        timer = setInterval(() => {
            showSlide(current + 1, false);
            animateProgress();
        }, duration);
    }

    prev?.addEventListener("click", () => showSlide(current - 1));
    next?.addEventListener("click", () => showSlide(current + 1));
    dots.forEach((dot, i) => dot.addEventListener("click", () => showSlide(i)));

    const hero = document.querySelector(".hero-slider");
    hero?.addEventListener("mouseenter", () => {
        clearInterval(timer);
        if (progress) progress.style.animationPlayState = "paused";
    });
    hero?.addEventListener("mouseleave", startAuto);

    let touchStartX = 0;
    hero?.addEventListener("touchstart", e => touchStartX = e.changedTouches[0].screenX, { passive: true });
    hero?.addEventListener("touchend", e => {
        const dx = e.changedTouches[0].screenX - touchStartX;
        if (Math.abs(dx) > 45) showSlide(current + (dx < 0 ? 1 : -1));
    }, { passive: true });

    startAuto();

    // E-Sumbong demo form
    const message = document.getElementById("message");
    const charCount = document.getElementById("charCount");
    message?.addEventListener("input", () => {
        if (message.value.length > 1000) message.value = message.value.slice(0, 1000);
        charCount.textContent = message.value.length;
    });

    const successModal = document.getElementById("successModal");
    const docModal = document.getElementById("docModal");
    const concernForm = document.getElementById("concernForm");
    const referenceCode = document.getElementById("referenceCode");
    const trackingCode = document.getElementById("trackingCode");

    function openModal(m) { m.classList.add("open"); m.setAttribute("aria-hidden", "false"); document.body.classList.add("modal-open") }
    function closeModal(m) { m.classList.remove("open"); m.setAttribute("aria-hidden", "true"); document.body.classList.remove("modal-open") }
    function makeCode() { return `ES-${new Date().getFullYear()}-${Math.floor(10000 + Math.random() * 90000)}` }

    concernForm?.addEventListener("submit", e => {
        e.preventDefault();
        const code = makeCode();
        referenceCode.textContent = code;
        trackingCode.value = code;
        openModal(successModal);
        concernForm.reset();
        charCount.textContent = "0";
    });

    document.querySelectorAll("[data-close-modal]").forEach(b => b.addEventListener("click", () => closeModal(successModal)));
    document.querySelectorAll("[data-close-doc]").forEach(b => b.addEventListener("click", () => closeModal(docModal)));

    // Tracker
    const trackBtn = document.getElementById("trackBtn");
    const trackResult = document.getElementById("trackResult");
    trackBtn?.addEventListener("click", () => {
        const code = trackingCode.value.trim();
        trackResult.hidden = false;
        if (!code) {
            trackResult.innerHTML = "<strong>Enter a reference number.</strong><p>Example: ES-2026-00124</p>";
            return;
        }
        trackResult.innerHTML = `<strong>${code}</strong><p>Status: <b>In Review</b> — Your concern has been received and is currently being evaluated.</p>`;
    });

    document.addEventListener("keydown", e => {
        if (e.key === "Escape") {
            if (successModal?.classList.contains("open")) closeModal(successModal);
            if (docModal?.classList.contains("open")) closeModal(docModal);
        }
    });
});
