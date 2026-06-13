/* ============================================================
   QAYEMA — portal interactions (GSAP choreography, theme, FAQ)
   Content + language are server-rendered (Laravel locale); this
   file only handles animation and client-side interactions.
   ============================================================ */
(function () {
  'use strict';

  /* ---------- FAQ accordion (items are server-rendered) ---------- */
  function animateHeight(el, h) {
    if (window.gsap) { gsap.to(el, { height: h, duration: 0.45, ease: 'power3.inOut' }); }
    else { el.style.height = h ? 'auto' : '0'; }
  }
  function initFaq() {
    var box = document.getElementById('faqList');
    if (!box) return;
    box.querySelectorAll('.faq-q').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.parentElement;
        var ans = item.querySelector('.faq-a');
        var open = item.classList.contains('open');
        box.querySelectorAll('.faq-item.open').forEach(function (o) {
          if (o !== item) { o.classList.remove('open'); animateHeight(o.querySelector('.faq-a'), 0); }
        });
        if (open) { item.classList.remove('open'); animateHeight(ans, 0); }
        else { item.classList.add('open'); animateHeight(ans, ans.querySelector('.inner').offsetHeight); }
      });
    });
  }

  /* ---------- Theme toggle (client-side; language is server-side) ---------- */
  function initTheme() {
    var tog = document.getElementById('themeTog');
    if (!tog) return;
    tog.addEventListener('click', function () {
      var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('qayema-theme', next); } catch (e) {}
    });
  }

  /* ---------- Nav scroll state ---------- */
  function initNav() {
    var nav = document.querySelector('.nav');
    if (!nav) return;
    var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 30); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ================= GSAP ================= */
  function initReveals() {
    gsap.utils.toArray('.reveal').forEach(function (el) {
      gsap.set(el, { opacity: 0, y: 28 });
      gsap.to(el, {
        opacity: 1, y: 0, duration: 0.9, ease: 'power3.out',
        scrollTrigger: { trigger: el, start: 'top 86%', once: true },
      });
    });
    gsap.utils.toArray('[data-stagger]').forEach(function (group) {
      var kids = group.children;
      gsap.set(kids, { opacity: 0, y: 30 });
      gsap.to(kids, {
        opacity: 1, y: 0, duration: 0.8, ease: 'power3.out', stagger: 0.09,
        scrollTrigger: { trigger: group, start: 'top 82%', once: true },
      });
    });
  }

  // Pinned horizontal scroll for "How it works" — direction follows the
  // server-set document direction (RTL scrolls the other way).
  function initHowPin() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var track = document.getElementById('howTrack');
    var section = document.getElementById('how');
    if (!track || !section) return;
    gsap.set(track, { x: 0 });
    var dir = document.documentElement.getAttribute('dir') === 'rtl' ? 1 : -1;
    var getScroll = function () { return Math.max(0, track.scrollWidth - window.innerWidth); };
    var bar = section.querySelector('.how-progress i');
    gsap.to(track, {
      x: function () { return dir * getScroll(); },
      ease: 'none',
      scrollTrigger: {
        trigger: section, start: 'top top', end: function () { return '+=' + getScroll(); },
        pin: true, scrub: 1, invalidateOnRefresh: true,
        onUpdate: function (self) { if (bar) { bar.style.width = (self.progress * 100) + '%'; } },
      },
    });
  }

  function initCounters() {
    gsap.utils.toArray('[data-count]').forEach(function (el) {
      var end = parseFloat(el.getAttribute('data-count'));
      var dec = el.getAttribute('data-dec') === '1';
      var suf = el.getAttribute('data-suf') || '';
      var obj = { v: 0 };
      ScrollTrigger.create({
        trigger: el, start: 'top 88%', once: true,
        onEnter: function () {
          gsap.to(obj, {
            v: end, duration: 1.8, ease: 'power2.out',
            onUpdate: function () { el.textContent = (dec ? obj.v.toFixed(1) : Math.round(obj.v).toLocaleString()) + suf; },
          });
        },
      });
    });
  }

  function initMagnetic() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    document.querySelectorAll('[data-magnetic]').forEach(function (el) {
      var strength = 0.35;
      el.addEventListener('mousemove', function (e) {
        var r = el.getBoundingClientRect();
        gsap.to(el, {
          x: (e.clientX - r.left - r.width / 2) * strength,
          y: (e.clientY - r.top - r.height / 2) * strength,
          duration: 0.5, ease: 'power3.out',
        });
      });
      el.addEventListener('mouseleave', function () { gsap.to(el, { x: 0, y: 0, duration: 0.6, ease: 'elastic.out(1,.4)' }); });
    });
  }

  /* ---------- Lenis smooth scroll + in-page anchors ---------- */
  function initLenis() {
    if (!window.Lenis || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var lenis = new Lenis({ duration: 1.1, easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); }, smoothWheel: true });
    function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
    requestAnimationFrame(raf);
    if (window.ScrollTrigger) { lenis.on('scroll', ScrollTrigger.update); }
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var id = a.getAttribute('href');
        if (id.length < 2) return;
        var target = document.querySelector(id);
        if (!target) return;
        e.preventDefault();
        lenis.scrollTo(target, { offset: -60 });
      });
    });
  }

  /* ---------- Boot ---------- */
  function boot() {
    initFaq();
    initNav();
    initTheme();

    if (window.gsap && window.ScrollTrigger) {
      gsap.registerPlugin(ScrollTrigger);
      initLenis();
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        initHowPin();
        initMagnetic();
      }
      initReveals();
      initCounters();
      window.addEventListener('load', function () { ScrollTrigger.refresh(); });
      setTimeout(function () { ScrollTrigger.refresh(); }, 600);
    } else {
      document.documentElement.classList.add('no-gsap');
    }
  }

  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); }
  else { boot(); }
})();
