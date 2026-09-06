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

    const campus = document.getElementById("campus");
    const college = document.getElementById("college");

    const collegesByCampus = {
        NLUC: [
        "College of Education (CE)",
        "College of Agriculture (CA)",
        "College of Arts and Sciences (CAS)",
        "College of Veterinary Medicine (CVM)",
        "College of Agroforestry and Forestry (CAFF)",
        "Institute of Agribusiness Management (IABM)",
        "Institute of Agricultural and Biosystems Engineering (IABE)",
        "Institute of Environmental Studies (IES)",
        "College of Information Systems (CIS)"
        ],
        OUS: [
        "Bachelor of Elementary Education",
        "Bachelor of Science in Agriculture Major in Horticulture",
        "Bachelor of Science in Business Administration"
        ],
        MLUC: [
        "Institute of Criminal Justice Education (ICJE)",
        "College of Technology (COT)",
        "College of Education (CE)",
        "College of Engineering (COE)",
        "College of Information Technology (CIT)",
        "College of Arts and Sciences (CAS)",
        "College of Management (COM)"
        ],
        SLUC: [
        "College of Education (CE)",
        "College of Arts and Sciences (CAS)",
        "College of Community Health & Allied Medical Sciences (CCHAMS)",
        "College of Computer Science (CCS)",
        "College of Agriculture (CA)",
        "College of Fisheries (CF)"
        ]
    };

    function campusCode(value) {
        const normalized = String(value || "").trim();
        const byName = {
            "North La Union Campus": "NLUC",
            "Mid La Union Campus": "MLUC",
            "South La Union Campus": "SLUC",
            "Open University System": "OUS"
        };
        if (byName[normalized]) return byName[normalized];
        const upper = normalized.toUpperCase();
        return ["NLUC", "MLUC", "SLUC", "OUS"].find(code => upper === code || upper.startsWith(code + " ") || upper.startsWith(code + " —")) || "";
    }

    function updateCollegeOptions() {
        if (!campus || !college) return;
        const code = campusCode(campus.value);
        const options = collegesByCampus[code] || [];
        college.innerHTML = "";
        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = options.length ? "Select college / program" : "Select campus first";
        college.appendChild(placeholder);
        options.forEach(name => {
            const option = document.createElement("option");
            option.value = name;
            option.textContent = name;
            college.appendChild(option);
        });
        college.disabled = !options.length;
    }

    campus?.addEventListener("change", updateCollegeOptions);
    updateCollegeOptions();

    const studentName = document.getElementById("studentName");
    const studentId = document.getElementById("studentId");

    // Format Student ID as 000-0000-0 while typing.
    studentId?.addEventListener("input", () => {
        const digits = studentId.value.replace(/\D/g, "").slice(0, 8);
        let formatted = digits.slice(0, 3);
        if (digits.length > 3) formatted += "-" + digits.slice(3, 7);
        if (digits.length > 7) formatted += "-" + digits.slice(7, 8);
        studentId.value = formatted;
    });

    const message = document.getElementById("message");
    const charCount = document.getElementById("charCount");

    message?.addEventListener("input", () => {
        charCount.textContent = message.value.length;
    });

    const form = document.getElementById("concernForm");
    const modal = document.getElementById("submissionModal");
    const referenceNumber = document.getElementById("referenceNumber");

    let modalReturnFocus = null;
    const modalFocusable = () => modal ? Array.from(modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')) : [];

    function closeModal() {
        if (!modal) return;
        modal.classList.remove("open");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
        if (modalReturnFocus && typeof modalReturnFocus.focus === "function") modalReturnFocus.focus();
    }

    if (window.submittedReference) {
        if (referenceNumber) referenceNumber.textContent = window.submittedReference;
        if (modal) {
            modalReturnFocus = document.activeElement;
            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");
            modal.querySelector(".modal-close")?.focus();
        }
    }

    document.querySelectorAll("[data-close-modal]").forEach(button => {
        button.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", event => {
        if (!modal || !modal.classList.contains("open")) return;
        if (event.key === "Escape") {
            closeModal();
            return;
        }
        if (event.key === "Tab") {
            const focusable = modalFocusable();
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });


    // Preserve only non-sensitive concern details in this browser tab when the network is unstable.
    // Student name, student ID, email, evidence files, and tracking credentials are never written to Web Storage.
    const draftKey = "dmmmsu-esumbong-draft-v1";
    const draftFields = ["campus", "college", "concernType", "subject", "message"];
    function saveDraft() {
        if (!form || window.submittedReference) return;
        const data = {};
        draftFields.forEach(id => { const el = document.getElementById(id); if (el && !el.disabled) data[id] = el.value; });
        try { sessionStorage.setItem(draftKey, JSON.stringify(data)); } catch (_e) { }
    }
    function restoreDraft() {
        if (!form || window.submittedReference) return;
        let data = null; try { data = JSON.parse(sessionStorage.getItem(draftKey) || "null"); } catch (_e) { }
        if (!data) return;
        if (campus && data.campus && !campus.value) campus.value = data.campus;
        updateCollegeOptions();
        draftFields.forEach(id => { if (id === 'campus' || id === 'college') return; const el = document.getElementById(id); if (el && data[id] && !el.value) el.value = data[id]; });
        if (college && data.college && [...college.options].some(o => o.value === data.college)) college.value = data.college;
        if (message && charCount) charCount.textContent = String(message.value.length);
        updateTypePolicy();
        updatePriorityPreview();
    }
    restoreDraft();
    form?.addEventListener("input", saveDraft);
    form?.addEventListener("change", saveDraft);
    if (window.submittedReference) { try { sessionStorage.removeItem(draftKey); } catch (_e) { } }
});
