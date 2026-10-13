// EventHub – Advanced JavaScript UI Enhancements
// Pure vanilla JS: scroll effects, particles, animations, interactivity

document.addEventListener('DOMContentLoaded', function () {

  /* ============================================================
     1. NAVBAR – Glassmorphism scroll effect + mobile toggle
  ============================================================ */
  var navbar = document.querySelector('.navbar');
  var toggle = document.getElementById('navToggle');
  var links  = document.getElementById('navLinks');

  if (navbar) {
    var lastScroll = 0;
    window.addEventListener('scroll', function () {
      var y = window.scrollY;
      // Add scrolled class for glass effect
      navbar.classList.toggle('scrolled', y > 40);
      // Auto-hide on scroll down, show on scroll up
      if (y > lastScroll + 5 && y > 200) {
        navbar.style.transform = 'translateY(-100%)';
      } else if (y < lastScroll - 5) {
        navbar.style.transform = 'translateY(0)';
      }
      lastScroll = y;
    }, { passive: true });
  }

  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
      toggle.textContent = links.classList.contains('open') ? '✕' : '☰';
    });
    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!toggle.contains(e.target) && !links.contains(e.target)) {
        links.classList.remove('open');
        toggle.textContent = '☰';
      }
    });
  }

  /* ============================================================
     2. HERO PARTICLES – Floating glowing orbs
  ============================================================ */
  var particleContainer = document.querySelector('.hero-particles');
  if (particleContainer) {
    var count = 28;
    for (var i = 0; i < count; i++) {
      (function(idx) {
        var span = document.createElement('span');
        var size = 2 + Math.random() * 4;
        var left = Math.random() * 100;
        var dur  = 8 + Math.random() * 16;
        var del  = Math.random() * 12;
        var drift= (Math.random() - 0.5) * 160;
        var hue  = Math.random() > 0.5 ? 265 : 340; // purple or pink

        span.style.cssText = [
          'width:' + size + 'px',
          'height:' + size + 'px',
          'left:' + left + '%',
          'bottom:-10%',
          '--drift:' + drift + 'px',
          'animation-duration:' + dur + 's',
          'animation-delay:' + del + 's',
          'background:hsla(' + hue + ',80%,65%,0.7)',
          'box-shadow:0 0 ' + (size*3) + 'px hsla(' + hue + ',80%,65%,0.4)'
        ].join(';');

        particleContainer.appendChild(span);
      })(i);
    }
  }

  /* ============================================================
     3. INTERSECTION OBSERVER – Reveal animations on scroll
  ============================================================ */
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  // Observe all reveal elements + event cards
  document.querySelectorAll('.reveal, .event-card, .stat').forEach(function (el, idx) {
    if (!el.classList.contains('reveal')) {
      el.style.animationDelay = (idx * 0.07) + 's';
    }
    observer.observe(el);
  });

  /* ============================================================
     4. ALERTS – Close button + auto-dismiss
  ============================================================ */
  document.querySelectorAll('.alert-close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var alert = btn.parentElement;
      alert.style.transition = 'opacity 0.3s, transform 0.3s';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-8px)';
      setTimeout(function () { alert.classList.add('hidden'); }, 300);
    });
  });

  /* ============================================================
     5. EVENT PAGE – Quantity & price updater
  ============================================================ */
  var qty      = document.getElementById('quantity');
  var totalBox = document.getElementById('totalAmount');
  if (qty && totalBox) {
    var price = parseFloat(qty.dataset.price) || 0;
    function updateTotal() {
      var q = parseInt(qty.value) || 0;
      if (price > 0) {
        totalBox.textContent = '₹' + (q * price).toFixed(2);
        totalBox.style.color = '#fff';
      } else {
        totalBox.textContent = 'Free';
        totalBox.style.color = 'var(--green)';
      }
    }
    qty.addEventListener('input', updateTotal);
    updateTotal();
  }

  /* ============================================================
     6. CONFIRM DIALOG – data-confirm forms
  ============================================================ */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  /* ============================================================
     7. REGISTER FORM – Client-side validation
  ============================================================ */
  var reg = document.getElementById('registerForm');
  if (reg) {
    reg.addEventListener('submit', function (ev) {
      var pw  = document.getElementById('password').value;
      var cpw = document.getElementById('confirm').value;
      var msg = document.getElementById('jsError');
      if (msg) {
        msg.classList.add('hidden');
        if (pw.length < 6) {
          msg.textContent = 'Password must be at least 6 characters.';
        } else if (pw !== cpw) {
          msg.textContent = 'Passwords do not match.';
        } else { return; }
        msg.classList.remove('hidden');
        ev.preventDefault();
      }
    });
  }

  /* ============================================================
     8. CHECKOUT – Payment method field toggle
  ============================================================ */
  var methods = document.querySelectorAll('input[name="method"]');
  if (methods.length) {
    function showFields() {
      var chosen = document.querySelector('input[name="method"]:checked');
      if (!chosen) return;
      document.querySelectorAll('.pay-fields').forEach(function (box) {
        var isChosen = box.dataset.method === chosen.value;
        box.classList.toggle('hidden', !isChosen);
        if (isChosen) {
          box.style.animation = 'fade-up 0.35s ease both';
        }
      });
    }
    methods.forEach(function (m) { m.addEventListener('change', showFields); });
    showFields();
  }

  /* ============================================================
     9. CHECKOUT – Countdown timer with urgency animation
  ============================================================ */
  var timer = document.getElementById('timer');
  if (timer) {
    var left = parseInt(timer.dataset.seconds) || 600;
    var timerBar = timer.closest('.timer-bar');

    function tick() {
      if (left <= 0) {
        window.location.href = timer.dataset.redirect;
        return;
      }
      var m = Math.floor(left / 60);
      var s = left % 60;
      timer.textContent = m + ':' + (s < 10 ? '0' : '') + s;
      // Flash red warning when under 2 mins
      if (left <= 120 && timerBar) {
        timerBar.style.animation = 'pulse-urgent 1s ease-in-out infinite';
      }
      left--;
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ============================================================
     10. TICKET – QR code rendering
  ============================================================ */
  var qr = document.getElementById('qrcode');
  if (qr && typeof drawQR === 'function') {
    drawQR(qr.dataset.code, qr, 8);
  }

  /* ============================================================
     11. CURSOR SPOTLIGHT – Subtle glow follows cursor on hero
  ============================================================ */
  var hero = document.querySelector('.hero');
  if (hero && window.innerWidth > 860) {
    hero.addEventListener('mousemove', function (e) {
      var rect = hero.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width  * 100).toFixed(1);
      var y = ((e.clientY - rect.top)  / rect.height * 100).toFixed(1);
      hero.style.setProperty('--mx', x + '%');
      hero.style.setProperty('--my', y + '%');
    });
    // Add a spotlight layer via dynamic inline style
    var spotlight = document.createElement('div');
    spotlight.style.cssText = [
      'position:absolute',
      'inset:0',
      'pointer-events:none',
      'z-index:2',
      'background:radial-gradient(circle 350px at var(--mx,50%) var(--my,50%), rgba(124,92,252,0.12) 0%, transparent 70%)',
      'transition:background 0.1s ease'
    ].join(';');
    hero.appendChild(spotlight);
  }

  /* ============================================================
     12. CARD TILT – Subtle 3D tilt on event cards
  ============================================================ */
  document.querySelectorAll('.event-card').forEach(function (card) {
    card.addEventListener('mousemove', function (e) {
      var rect   = card.getBoundingClientRect();
      var cx     = rect.left + rect.width  / 2;
      var cy     = rect.top  + rect.height / 2;
      var dx     = (e.clientX - cx) / (rect.width  / 2);
      var dy     = (e.clientY - cy) / (rect.height / 2);
      var rotX   = (-dy * 6).toFixed(1);
      var rotY   = (dx  * 6).toFixed(1);
      card.style.transform = 'translateY(-8px) rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg) scale(1.01)';
      card.style.transition = 'transform 0.08s linear';
    });
    card.addEventListener('mouseleave', function () {
      card.style.transform = '';
      card.style.transition = 'transform 0.4s cubic-bezier(0.16,1,0.3,1)';
    });
  });

  /* ============================================================
     13. SMOOTH NUMBER COUNT UP – Stats (admin panel)
  ============================================================ */
  document.querySelectorAll('.stat h2').forEach(function (el) {
    var target = parseInt(el.textContent.replace(/[^0-9]/g, '')) || 0;
    if (target === 0) return;
    var prefix = el.textContent.match(/[^\d]*/)[0];
    var dur    = 1200;
    var start  = null;

    var obsNum = new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting) {
        start = null;
        function step(ts) {
          if (!start) start = ts;
          var progress = Math.min((ts - start) / dur, 1);
          var ease     = 1 - Math.pow(1 - progress, 3);
          el.textContent = prefix + Math.floor(ease * target).toLocaleString();
          if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
        obsNum.unobserve(el);
      }
    });
    obsNum.observe(el);
  });

});

// Pulse urgent keyframe injected at runtime
(function () {
  var style = document.createElement('style');
  style.textContent = '@keyframes pulse-urgent { 0%,100%{opacity:1} 50%{opacity:0.6} }';
  document.head.appendChild(style);
})();
