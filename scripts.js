"use strict";

/* ---- Scroll progress bar ---- */
const progressBar = document.getElementById("progress-bar");
function updateProgress() {
  const scrolled = window.scrollY;
  const total = document.documentElement.scrollHeight - window.innerHeight;
  progressBar.style.transform = `scaleX(${scrolled / total})`;
}
window.addEventListener("scroll", updateProgress, { passive: true });

/* ---- Navbar scroll state ---- */
const navbar = document.getElementById("navbar");
function updateNav() {
  navbar.classList.toggle("scrolled", window.scrollY > 40);
}
window.addEventListener("scroll", updateNav, { passive: true });
updateNav();

/* ---- Mobile nav toggle ---- */
const navToggle = document.getElementById("navToggle");
const navMobile = document.getElementById("navMobile");

navToggle.addEventListener("click", () => {
  const isOpen = navMobile.classList.toggle("open");
  navToggle.setAttribute("aria-expanded", isOpen);
  document.body.style.overflow = isOpen ? "hidden" : "";
});

// Close on link click
navMobile.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    navMobile.classList.remove("open");
    navToggle.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  });
});

// Close on outside click
document.addEventListener("click", (e) => {
  if (!navbar.contains(e.target) && !navMobile.contains(e.target)) {
    navMobile.classList.remove("open");
    navToggle.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  }
});

/* ---- Intersection Observer — scroll animations ---- */
const io = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("anim-visible");
        io.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.1, rootMargin: "0px 0px -60px 0px" },
);

document.querySelectorAll(".anim-hidden").forEach((el) => io.observe(el));

/* ---- Contact form ---- */
const form = document.getElementById("contactForm");
const formStatus = document.getElementById("formStatus");

form.addEventListener("submit", (e) => {
  e.preventDefault();

  const name = form.name.value.trim();
  const email = form.email.value.trim();
  const message = form.message.value.trim();

  // Client-side validation using Constraint Validation API
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // Simulate form submission
  const btn = form.querySelector('button[type="submit"]');
  const originalText = btn.textContent;
  btn.textContent = "Sending…";
  btn.disabled = true;

  setTimeout(() => {
    formStatus.className = "form-status success";
    formStatus.textContent = `✦ Thank you, ${name}! Your message has been sent. Emy will be in touch within 24–48 hours.`;
    form.reset();
    btn.textContent = originalText;
    btn.disabled = false;

    setTimeout(() => {
      formStatus.className = "form-status";
    }, 8000);
  }, 1200);
});

/* ---- Footer year ---- */
document.getElementById("footerYear").textContent = new Date().getFullYear();
