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

    document.querySelectorAll(".nav-drop-btn").forEach(button => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            const dropdown = button.closest(".nav-dropdown");
            document.querySelectorAll(".nav-dropdown.open").forEach(item => {
                if (item !== dropdown) item.classList.remove("open");
            });
            dropdown.classList.toggle("open");
        });
    });

    document.addEventListener("click", event => {
        if (!event.target.closest(".nav-dropdown")) {
            document.querySelectorAll(".nav-dropdown.open").forEach(item => item.classList.remove("open"));
        }
    });

    // Slideshow
    const slides = [...document.querySelectorAll(".hero-slide")];
    const dots = [...document.querySelectorAll("#heroDots button")];
    let currentSlide = Math.max(0, slides.findIndex(slide => slide.classList.contains("active")));
    let slideTimer;

    function showSlide(index) {
        if (!slides.length) return;
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle("active", i === currentSlide));
        dots.forEach((dot, i) => dot.classList.toggle("active", i === currentSlide));
    }

    function restartSlides() {
        clearInterval(slideTimer);
        if (slides.length <= 1) return;
        slideTimer = setInterval(() => showSlide(currentSlide + 1), 5500);
    }

    document.getElementById("prevSlide")?.addEventListener("click", () => {
        showSlide(currentSlide - 1);
        restartSlides();
    });

    document.getElementById("nextSlide")?.addEventListener("click", () => {
        showSlide(currentSlide + 1);
        restartSlides();
    });

    dots.forEach((dot, index) => {
        dot.addEventListener("click", () => {
            showSlide(index);
            restartSlides();
        });
    });

    showSlide(currentSlide);
    restartSlides();

    // Latest updates: newest filtered post becomes the lead card.
    const allNews = [...document.querySelectorAll("#newsFeed .news-item")];
    const searchInput = document.getElementById("newsSearch");
    const filterButtons = [...document.querySelectorAll("#newsFilters button")];
    const prevButton = document.getElementById("newsPrev");
    const nextButton = document.getElementById("newsNext");
    const pageNumbers = document.getElementById("newsPageNumbers");
    const emptyState = document.getElementById("newsEmpty");
    const pagination = document.getElementById("newsPagination");
    const leadSlot = document.getElementById("latestLeadSlot");
    const latestSection = document.getElementById("latest");

    const perPage = 4;
    let activeFilter = window.CAMPUS_HOME_FILTER || "all";
    let query = "";
    let currentPage = 0;

    function filteredNews() {
        const requested = String(activeFilter || "all").trim().toLowerCase();
        return allNews.filter(item => {
            const itemCategory = String(item.dataset.category || "usc").trim().toLowerCase();
            const categoryMatch = requested === "all" || itemCategory === requested;
            const textMatch = !query || item.textContent.toLowerCase().includes(query);
            return categoryMatch && textMatch;
        });
    }

    function makeLeadCard(item) {
        if (!leadSlot || !item) return;

        const copy = item.querySelector(".news-copy")?.cloneNode(true);
        const category = item.dataset.category || "usc";
        const coverUrl = String(item.dataset.coverUrl || "").trim();
        const videoUrl = String(item.dataset.videoUrl || "").trim();
        const postUrl = String(item.dataset.postUrl || item.querySelector(".news-title-link")?.getAttribute("href") || "#");
        const postTitle = String(item.dataset.postTitle || item.querySelector(".news-title-link")?.textContent || "News update").trim();
        const photoCount = Math.max(0, Number.parseInt(item.dataset.photoCount || "0", 10) || 0);

        const card = document.createElement("article");
        card.className = "news-lead-card latest-dynamic-lead";

        const mediaWrap = document.createElement("div");
        mediaWrap.className = `lead-media ${category}`;

        // Do not clone the hidden feed thumbnail here. Hidden/lazy media can be
        // cloned before the browser has actually loaded a frame, which leaves
        // the large Latest Update card blank. Render fresh media from the
        // canonical URLs carried by the post row instead.
        if (coverUrl) {
            mediaWrap.classList.add("has-cover");
            const image = document.createElement("img");
            image.src = coverUrl;
            image.alt = `${postTitle} cover photo`;
            image.loading = "eager";
            image.decoding = "async";
            mediaWrap.appendChild(image);
        } else if (videoUrl) {
            mediaWrap.classList.add("has-video");
            const preview = document.createElement("a");
            preview.className = "home-video-preview";
            preview.href = postUrl;
            preview.dataset.videoFrame = "";
            preview.setAttribute("aria-label", `Open video story: ${postTitle}`);

            const video = document.createElement("video");
            video.muted = true;
            video.playsInline = true;
            video.preload = "metadata";
            video.dataset.videoPreview = "";
            video.src = videoUrl;

            const play = document.createElement("span");
            play.className = "home-video-play";
            play.setAttribute("aria-hidden", "true");
            play.textContent = "▶";

            const chip = document.createElement("span");
            chip.className = "home-video-chip";
            chip.textContent = "Video";

            preview.append(video, play, chip);
            mediaWrap.appendChild(preview);
        } else {
            const mark = document.createElement("div");
            mark.className = "lead-placeholder";
            mark.textContent = category.toUpperCase();
            mediaWrap.appendChild(mark);
        }

        if (photoCount > 1) {
            const count = document.createElement("b");
            count.className = "home-photo-count small";
            count.textContent = `◫ ${photoCount} photos`;
            mediaWrap.appendChild(count);
        }

        const badge = document.createElement("span");
        badge.className = "lead-badge latest-badge";
        badge.textContent = "LATEST UPDATE";
        mediaWrap.appendChild(badge);

        const copyWrap = document.createElement("div");
        copyWrap.className = "lead-copy";
        if (copy) {
            while (copy.firstChild) copyWrap.appendChild(copy.firstChild);
        }

        card.append(mediaWrap, copyWrap);
        leadSlot.replaceChildren(card);
        window.USCVideoPreview?.refresh?.(card);
    }

    function scrollToLatestUpdates() {
        if (!latestSection) return;
        const header = document.querySelector(".site-header");
        const headerHeight = header ? header.getBoundingClientRect().height : 0;
        const targetTop = latestSection.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
        const reduceMotion = window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;
        window.scrollTo({
            top: Math.max(0, targetTop),
            behavior: reduceMotion ? "auto" : "smooth"
        });
    }

    function goToNewsPage(page) {
        renderNews(page);
        window.requestAnimationFrame(scrollToLatestUpdates);
    }

    function renderNews(page = 0) {
        const items = filteredNews();
        const leadItem = items[0] || null;
        const remaining = leadItem ? items.slice(1) : [];
        const pageCount = Math.ceil(remaining.length / perPage);
        currentPage = pageCount ? Math.max(0, Math.min(page, pageCount - 1)) : 0;

        allNews.forEach(item => item.hidden = true);

        // The large "Latest Update" lead card belongs to page 1 only.
        // On page 2 and later, keep the section compact and show only the
        // paginated update cards.
        if (currentPage === 0 && leadItem) makeLeadCard(leadItem);
        else if (leadSlot) leadSlot.replaceChildren();

        const start = currentPage * perPage;
        remaining.slice(start, start + perPage).forEach(item => item.hidden = false);

        emptyState.hidden = items.length !== 0;
        pagination.hidden = pageCount <= 1;

        prevButton.hidden = currentPage === 0 || pageCount <= 1;
        nextButton.hidden = currentPage >= pageCount - 1 || pageCount <= 1;

        pageNumbers.innerHTML = "";
        if (pageCount > 1) {
            for (let i = 0; i < pageCount; i++) {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "page-number" + (i === currentPage ? " active" : "");
                button.textContent = String(i + 1);
                if (i === currentPage) button.setAttribute("aria-current", "page");
                button.addEventListener("click", () => goToNewsPage(i));
                pageNumbers.appendChild(button);
            }
        }
    }

    if (window.CAMPUS_HOME_FILTER) {
        filterButtons.forEach(button => button.hidden = true);
    }

    filterButtons.forEach(button => {
        button.addEventListener("click", () => {
            activeFilter = button.dataset.filter;
            filterButtons.forEach(item => item.classList.toggle("active", item === button));
            renderNews(0);
        });
    });

    searchInput?.addEventListener("input", () => {
        query = searchInput.value.trim().toLowerCase();
        renderNews(0);
    });

    prevButton?.addEventListener("click", () => goToNewsPage(currentPage - 1));
    nextButton?.addEventListener("click", () => goToNewsPage(currentPage + 1));

    renderNews(0);
});
