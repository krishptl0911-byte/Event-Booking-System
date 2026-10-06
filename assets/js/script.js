// All page-level JavaScript for EventHub (plain JavaScript, no libraries).
// Each block first checks that its element exists, so one file works on every page.

document.addEventListener('DOMContentLoaded', function () {

  // 1. NAVBAR: open/close the menu on small screens
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () { links.classList.toggle('open'); });
  }

  // 2. ALERTS: close button hides the message
  document.querySelectorAll('.alert-close').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.parentElement.classList.add('hidden'); });
  });

  // 3. EVENT PAGE: update total price when quantity changes
  var qty = document.getElementById('quantity');
  var totalBox = document.getElementById('totalAmount');
  if (qty && totalBox) {
    var price = parseFloat(qty.dataset.price);
    var update = function () {
      var q = parseInt(qty.value) || 0;
      totalBox.textContent = price > 0 ? '₹' + (q * price).toFixed(2) : 'Free';
    };
    qty.addEventListener('input', update);
    update();
  }

  // 4. CONFIRM DIALOG: any form with data-confirm asks before submitting
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  // 5. REGISTER FORM: client-side validation (the server validates again)
  var reg = document.getElementById('registerForm');
  if (reg) {
    reg.addEventListener('submit', function (ev) {
      var pw = document.getElementById('password').value;
      var cpw = document.getElementById('confirm').value;
      var msg = document.getElementById('jsError');
      msg.classList.add('hidden');
      if (pw.length < 6) {
        msg.textContent = 'Password must be at least 6 characters.';
      } else if (pw !== cpw) {
        msg.textContent = 'Passwords do not match.';
      } else {
        return;
      }
      msg.classList.remove('hidden');
      ev.preventDefault();
    });
  }

  // 6. CHECKOUT: show the fields of the selected payment method
  var methods = document.querySelectorAll('input[name="method"]');
  if (methods.length) {
    var showFields = function () {
      var chosen = document.querySelector('input[name="method"]:checked').value;
      document.querySelectorAll('.pay-fields').forEach(function (box) {
        box.classList.toggle('hidden', box.dataset.method !== chosen);
      });
    };
    methods.forEach(function (m) { m.addEventListener('change', showFields); });
    showFields();
  }

  // 7. CHECKOUT: 10-minute countdown (seats are held until it ends)
  var timer = document.getElementById('timer');
  if (timer) {
    var left = parseInt(timer.dataset.seconds);
    var tick = function () {
      if (left <= 0) {
        window.location.href = timer.dataset.redirect;
        return;
      }
      var m = Math.floor(left / 60), s = left % 60;
      timer.textContent = m + ':' + (s < 10 ? '0' : '') + s;
      left--;
    };
    tick();
    setInterval(tick, 1000);
  }

  // 8. TICKET: draw the QR code (drawQR is defined in qr.js)
  var qr = document.getElementById('qrcode');
  if (qr && typeof drawQR === 'function') {
    drawQR(qr.dataset.code, qr, 8);
  }
});
