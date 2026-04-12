document.addEventListener("DOMContentLoaded", () => {

  /* =========================
     HERO SLIDER
  ========================= */
  document.querySelectorAll(".hero-slider").forEach(wrapper => {

    const slider = wrapper.querySelector(".slider");
    const slides = wrapper.querySelectorAll(".slide");
    const dotsContainer = wrapper.querySelector(".dots");
    const nextBtn = wrapper.querySelector(".next");
    const prevBtn = wrapper.querySelector(".prev");

    let index = 0;
    let interval;

    if (!slider || slides.length === 0) return;

    if (dotsContainer) {
      dotsContainer.innerHTML = "";
      slides.forEach((_, i) => {
        let dot = document.createElement("span");
        dot.addEventListener("click", () => goTo(i));
        dotsContainer.appendChild(dot);
      });
    }

    const dots = dotsContainer ? dotsContainer.querySelectorAll("span") : [];

    function update() {
      slider.style.transform = `translateX(-${index * 100}%)`;
      dots.forEach(d => d.classList.remove("active"));
      if (dots[index]) dots[index].classList.add("active");
    }

    function next() {
      index = (index + 1) % slides.length;
      update();
    }

    function prev() {
      index = (index - 1 + slides.length) % slides.length;
      update();
    }

    function goTo(i) {
      index = i;
      update();
      resetAuto();
    }

    nextBtn && nextBtn.addEventListener("click", next);
    prevBtn && prevBtn.addEventListener("click", prev);

    function startAuto() {
      interval = setInterval(next, 5000);
    }

    function resetAuto() {
      clearInterval(interval);
      startAuto();
    }

    startAuto();
    update();
  });




  /* =========================
     ADD TO CART
  ========================= */
  window.addToCart = function (id, name, price, image, btn = null, qty = 1) {

    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    let existing = cart.find(item => item.id == id);

    if (existing) {
      if (existing.qty >= 10) {
        showToast("Max quantity reached");
        return;
      }
      existing.qty += qty;
    } else {
      cart.push({ id, name, price, image, qty: qty });
    }

    localStorage.setItem("cart", JSON.stringify(cart));

    updateCartCount();
    animateToCart(image, btn);
    showToast("Added to cart 🛒");
  };


  /* =========================
     CART COUNT
  ========================= */
  function updateCartCount() {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    let count = 0;

    cart.forEach(item => count += item.qty);

    let badge = document.getElementById("cart-count");
    if (badge) badge.innerText = count;
  }
  updateCartCount();


  /* =========================
     ANIMATION TO CART
  ========================= */
  function animateToCart(image, btn) {

    if (!btn) return;

    let img = document.createElement("img");
    img.src = "../assets/images/" + image;

    img.style.position = "fixed";
    img.style.width = "80px";
    img.style.zIndex = "9999";

    let rect = btn.getBoundingClientRect();
    img.style.left = rect.left + "px";
    img.style.top = rect.top + "px";

    document.body.appendChild(img);

    let cart = document.querySelector(".cart-icon");
    if (!cart) return;

    let cartRect = cart.getBoundingClientRect();

    setTimeout(() => {
      img.style.transition = "all 0.7s ease";
      img.style.left = cartRect.left + "px";
      img.style.top = cartRect.top + "px";
      img.style.width = "30px";
      img.style.opacity = "0.5";
    }, 10);

    setTimeout(() => img.remove(), 700);
  }


  /* =========================
     TOAST
  ========================= */
  function showToast(msg) {
    let toast = document.getElementById("toast");
    if (!toast) return;

    toast.innerText = msg;
    toast.classList.add("show");

    setTimeout(() => toast.classList.remove("show"), 2000);
  }

  /* ══════════════════════════════════════════════════
   RUP VIDEO SECTION — SHOP OUR MOST LOVED PRODUCTS
══════════════════════════════════════════════════ */

// ── data embedded from PHP data-* attributes ──
var rupVideos = (function() {
  var cards = document.querySelectorAll('.rup-video-card');
  var out = [];
  cards.forEach(function(card) {
    out.push({
      index: parseInt(card.dataset.index),
      video: card.querySelector('.rup-card-video source') ? card.querySelector('.rup-card-video source').src : '',
      name:  card.querySelector('.rup-chip-name')  ? card.querySelector('.rup-chip-name').textContent.trim()  : '',
      price: card.querySelector('.rup-chip-prices strong') ? card.querySelector('.rup-chip-prices strong').textContent.trim() : '',
      image: card.querySelector('.rup-product-chip img')  ? card.querySelector('.rup-product-chip img').src   : '',
      pimg:  card.querySelector('.rup-product-chip img')  ? card.querySelector('.rup-product-chip img').src   : '',
    });
  });
  return out;
})();
var rupCurIdx = 0;

window.rupOpenModal = function(idx) {
  rupCurIdx = idx;
  var modal = document.getElementById('rupModal');
  if (!modal || !rupVideos.length) return;
  rupSetModalVideo(idx);
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
};

function rupSetModalVideo(idx) {
  if (idx < 0) idx = rupVideos.length - 1;
  if (idx >= rupVideos.length) idx = 0;
  rupCurIdx = idx;
  var v = rupVideos[idx];
  var vid = document.getElementById('rupModalVideo');
  if (vid) { vid.src = v.video; vid.play(); }
  var counter = document.getElementById('rupModalCounter');
  if (counter) counter.textContent = (idx + 1) + ' / ' + rupVideos.length;
  var pname  = document.getElementById('rupModalPName');
  var pprice = document.getElementById('rupModalPPrice');
  var pimg   = document.getElementById('rupModalPImg');
  if (pname)  pname.textContent  = v.name;
  if (pprice) pprice.textContent = v.price;
  if (pimg)   pimg.src = v.pimg;
  // Wire up Add to Cart button — re-assign onclick each time
  var cartBtn = document.getElementById('rupModalCartBtn');
  if (cartBtn) {
    cartBtn.onclick = function() {
      var priceNum = parseFloat(String(v.price).replace(/[^\d.]/g, '')) || 0;
      var imgFile = v.image.split('/').pop();
      addToCart(rupVideos[idx] ? idx : 0, v.name, priceNum, imgFile, cartBtn);
    };
  }
}

window.rupModalNav = function(dir) {
  rupSetModalVideo(rupCurIdx + dir);
};

window.rupCloseModal = function() {
  var modal = document.getElementById('rupModal');
  var vid   = document.getElementById('rupModalVideo');
  if (vid) { vid.pause(); vid.src = ''; }
  if (modal) modal.classList.remove('open');
  document.body.style.overflow = '';
};

// Close on backdrop click
(function() {
  var m = document.getElementById('rupModal');
  if (m) m.addEventListener('click', function(e) { if (e.target === m) rupCloseModal(); });
})();

// Scroll track
window.rupScroll = function(dir) {
  var t = document.getElementById('rupVideoTrack');
  if (t) t.scrollBy({ left: dir * 300, behavior: 'smooth' });
};


/* ══════════════════════════════════════════════
   INFLUENCER SECTION
══════════════════════════════════════════════ */
var rupInfVideos = (function() {
  var cards = document.querySelectorAll('.rup-inf-card');
  var out = [];
  cards.forEach(function(card) {
    out.push({
      name:  card.querySelector('.rup-inf-name')  ? card.querySelector('.rup-inf-name').textContent.trim()  : '',
      video: card.getAttribute('onclick') // will extract from onclick
    });
  });
  return out;
})();
// Collect inf data from PHP-rendered elements
var rupInfData = [];
(function() {
  document.querySelectorAll('.rup-inf-card').forEach(function(card, i) {
    var img  = card.querySelector('img');
    var name = card.querySelector('.rup-inf-name');
    rupInfData.push({
      name:  name  ? name.textContent.trim()  : '',
      thumb: img   ? img.src                  : '',
    });
  });
})();
var rupInfCur = 0;

window.rupOpenInf = function(idx) {
  // Get video path from the card's onclick attr — we need it from the video element
  // Since influencer cards have no inline video, use the thumbnail src pattern
  // The video src comes from the PHP data stored in the card click
  // We parse it from the onclick attribute of the card
  var cards = document.querySelectorAll('.rup-inf-card');
  if (!cards[idx]) return;
  var onclickStr = cards[idx].getAttribute('onclick');
  // onclick="rupOpenInf(N)" — we need to get the video from the DOM differently
  // The PHP renders the card with onclick only, no video path in data attr
  // We'll store video path in data-video on each card
  var videoSrc = cards[idx].dataset.video || '';
  if (!videoSrc) return;

  rupInfCur = idx;
  var modal = document.getElementById('rupInfModal');
  var vid   = document.getElementById('rupInfVideo');
  var name  = document.getElementById('rupInfName');
  if (!modal || !vid) return;
  vid.src = videoSrc;
  vid.play();
  if (name) name.textContent = rupInfData[idx] ? rupInfData[idx].name : '';
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
};

window.rupInfNav = function(dir) {
  var cards = document.querySelectorAll('.rup-inf-card');
  var next = rupInfCur + dir;
  if (next < 0) next = cards.length - 1;
  if (next >= cards.length) next = 0;
  rupOpenInf(next);
};

window.rupCloseInf = function() {
  var modal = document.getElementById('rupInfModal');
  var vid   = document.getElementById('rupInfVideo');
  if (vid) { vid.pause(); vid.src = ''; }
  if (modal) modal.classList.remove('open');
  document.body.style.overflow = '';
};

(function() {
  var m = document.getElementById('rupInfModal');
  if (m) m.addEventListener('click', function(e) { if (e.target === m) rupCloseInf(); });
})();

window.rupInfScroll = function(dir) {
  var t = document.getElementById('rupInfTrack');
  if (t) t.scrollBy({ left: dir * 280, behavior: 'smooth' });
};


/* ══════════════════════════════════════════════
   REVIEWS SECTION
══════════════════════════════════════════════ */
var rupRevCur = 0;

window.rupOpenRev = function(idx) {
  var cards = document.querySelectorAll('.rup-rev-card');
  if (!cards[idx]) return;
  rupRevCur = idx;
  var videoSrc = cards[idx].dataset.video || '';
  var revName  = cards[idx].dataset.name  || '';
  var revRole  = cards[idx].dataset.role  || '';
  var modal = document.getElementById('rupRevModal');
  var vid   = document.getElementById('rupRevVideo');
  if (!modal || !vid) return;
  vid.src = videoSrc;
  vid.play();
  var n = document.getElementById('rupRevModalName');
  var r = document.getElementById('rupRevModalRole');
  if (n) n.textContent = revName;
  if (r) r.textContent = revRole;
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
};

window.rupRevNav = function(dir) {
  var cards = document.querySelectorAll('.rup-rev-card');
  var next = rupRevCur + dir;
  if (next < 0) next = cards.length - 1;
  if (next >= cards.length) next = 0;
  rupOpenRev(next);
};

window.rupCloseRev = function() {
  var modal = document.getElementById('rupRevModal');
  var vid   = document.getElementById('rupRevVideo');
  if (vid) { vid.pause(); vid.src = ''; }
  if (modal) modal.classList.remove('open');
  document.body.style.overflow = '';
};

(function() {
  var m = document.getElementById('rupRevModal');
  if (m) m.addEventListener('click', function(e) { if (e.target === m) rupCloseRev(); });
})();

window.rupRevScroll = function(dir) {
  var t = document.getElementById('rupRevTrack');
  if (t) t.scrollBy({ left: dir * 280, behavior: 'smooth' });
};

// Keyboard nav for all modals
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    rupCloseModal(); rupCloseInf(); rupCloseRev();
  }
  if (e.key === 'ArrowRight') {
    if (document.getElementById('rupModal')    && document.getElementById('rupModal').classList.contains('open'))    rupModalNav(1);
    if (document.getElementById('rupInfModal') && document.getElementById('rupInfModal').classList.contains('open')) rupInfNav(1);
    if (document.getElementById('rupRevModal') && document.getElementById('rupRevModal').classList.contains('open')) rupRevNav(1);
  }
  if (e.key === 'ArrowLeft') {
    if (document.getElementById('rupModal')    && document.getElementById('rupModal').classList.contains('open'))    rupModalNav(-1);
    if (document.getElementById('rupInfModal') && document.getElementById('rupInfModal').classList.contains('open')) rupInfNav(-1);
    if (document.getElementById('rupRevModal') && document.getElementById('rupRevModal').classList.contains('open')) rupRevNav(-1);
  }
});

/* =========================
     WHY COUNTER
  ========================= */
  const counters = document.querySelectorAll(".counter");

  if (counters.length > 0) {

    const startCounter = (counter) => {
      const target = +counter.dataset.target;
      let count = 0;

      const update = () => {
        count += target / 100;

        if (count < target) {
          counter.innerText = Math.ceil(count);
          requestAnimationFrame(update);
        } else {
          counter.innerText = target;
        }
      };

      update();
    };

    const whySection = document.querySelector(".why-pro-section");

    if (whySection) {
      const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) {
          counters.forEach(startCounter);
        }
      });

      observer.observe(whySection);
    }
  }

});

/* =========================
   FRANCHISE POPUP
========================= */

function openFranchiseForm() {
  document.getElementById("franchiseModal").style.display = "flex";
}

function closeFranchiseForm() {
  document.getElementById("franchiseModal").style.display = "none";
}

/* SUBMIT */
const BASE_URL = window.location.origin;
const form = document.getElementById("franchiseForm");

if (form) {

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const btn = this.querySelector("button[type=submit]");
    btn.disabled    = true;
    btn.textContent = "Submitting...";

    let formData = new FormData(this);

    fetch(BASE_URL + "/proburst/pages/save-lead.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
    .then(res => {

      if (res.includes("success")) {

        // ✅ Show thank-you message inside the modal
        const box = document.querySelector(".franchise-form-box");
        box.innerHTML = `
          <div style="text-align:center;padding:30px 20px">
            <div style="font-size:2.5rem;margin-bottom:12px">🎉</div>
            <h3 style="color:#fff;margin-bottom:8px">Thank You!</h3>
            <p style="color:#aaa;font-size:.95rem;margin-bottom:20px">
              Your franchise inquiry has been received.<br>
              Our team will contact you within 24 hours.
            </p>
            <button onclick="closeFranchiseForm()"
              style="background:#e63946;color:#fff;border:none;border-radius:8px;
                     padding:10px 24px;font-size:.95rem;cursor:pointer;font-weight:600">
              Close
            </button>
          </div>
        `;

        // Optional: also open WhatsApp after 1.5 seconds
        let phone = formData.get("phone");
        let msg   = encodeURIComponent("Hi, I am interested in Proburst Franchise. My number: " + phone);
        setTimeout(() => window.open("https://wa.me/91YOURNUMBER?text=" + msg, "_blank"), 1500);

      } else {
        alert("Something went wrong. Please try again.");
        btn.disabled    = false;
        btn.textContent = "Submit & Continue";
      }

    })
    .catch(err => {
      console.error(err);
      alert("Network error. Please try again.");
      btn.disabled    = false;
      btn.textContent = "Submit & Continue";
    });

  });
}


// =====================================
// HAMBERGER MENU
// =======================================


const hamburger = document.getElementById("hamburger");
const menu = document.querySelector(".menu-bar");
const overlay = document.getElementById("overlay");
const closeBtn = document.getElementById("menuClose");

/* OPEN MENU */
hamburger.addEventListener("click", () => {
  menu.classList.add("active");
  overlay.classList.add("active");
});

/* CLOSE BUTTON */
closeBtn.addEventListener("click", () => {
  menu.classList.remove("active");
  overlay.classList.remove("active");
});

/* CLICK OUTSIDE */
overlay.addEventListener("click", () => {
  menu.classList.remove("active");
  overlay.classList.remove("active");
});

/* =========================
   DROPDOWN 
========================= */

/* CATEGORY DROPDOWN */
document.querySelectorAll(".dropdown").forEach(drop => {
  drop.addEventListener("click", function (e) {

    if (window.innerWidth < 768) {

      /* prevent link redirect */
      e.preventDefault();

      this.classList.toggle("active");

    }

  });
});

/* SUBMENU */
document.querySelectorAll(".has-submenu").forEach(sub => {
  sub.addEventListener("click", function (e) {

    if (window.innerWidth < 768) {

      e.stopPropagation(); // important fix

      this.classList.toggle("active");

    }

  });
});


/* =========================
   HOT OFFERS SLIDER
========================= */

const slider = document.getElementById("offersSlider");
const next = document.querySelector(".offer-nav.next");
const prev = document.querySelector(".offer-nav.prev");

if (next && prev && slider) {

  next.addEventListener("click", () => {
    slider.scrollBy({ left: 300, behavior: "smooth" });
  });

  prev.addEventListener("click", () => {
    slider.scrollBy({ left: -300, behavior: "smooth" });
  });

}


// ============================
// LIVE SEARCH
// ============================

(function() {
  const input    = document.getElementById('navSearch');
  const dropdown = document.getElementById('searchDropdown');
  let   timer    = null;

  input.addEventListener('input', function() {
    clearTimeout(timer);
    const q = this.value.trim();

    if (q.length < 2) {
      dropdown.classList.remove('open');
      dropdown.innerHTML = '';
      return;
    }

    timer = setTimeout(() => {
      fetch('/proburst/ajax/search.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(products => {
          if (!products.length) {
            dropdown.innerHTML = '<div class="search-no-result">No products found for "' + q + '"</div>';
          } else {
            dropdown.innerHTML = products.map(p => `
              <a href="${p.url}" class="search-result-item">
                <img src="/proburst/assets/images/${p.image}" alt="${p.name}" onerror="this.style.display='none'">
                <div>
                  <span class="sr-name">${p.name}</span>
                  <span class="sr-price">₹${p.price.toLocaleString('en-IN')}</span>
                </div>
              </a>
            `).join('');
          }
          dropdown.classList.add('open');
        })
        .catch(() => {
          dropdown.classList.remove('open');
        });
    }, 300); // 300ms debounce
  });

  // Close on click outside
  document.addEventListener('click', function(e) {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });

  // Navigate on Enter
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && this.value.trim()) {
      window.location.href = '/proburst/pages/shop.php?search=' + encodeURIComponent(this.value.trim());
    }
  });
})();


// ========================================
//     CHECKOUT PAGE
// ========================================

let cart  = JSON.parse(localStorage.getItem('cart')) || [];
let total = 0;

// Render summary
(function() {
  const box = document.getElementById('order-items');
  if (!box) return;
  if (!cart.length) { box.innerHTML = "<p style='color:#888'>Your cart is empty</p>"; return; }
  let html = '';
  cart.forEach(item => {
    const sub = item.price * item.qty;
    total += sub;
    html += `<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem">
      <span>${item.name} × ${item.qty}</span>
      <span>₹${sub.toLocaleString('en-IN')}</span>
    </div>`;
  });
  box.innerHTML = html;
  document.getElementById('order-total').textContent = '₹' + total.toLocaleString('en-IN');
})();

(function(){
var _cf = document.getElementById('checkoutForm');
if (!_cf) return;
_cf.addEventListener('submit', function(e) {
  e.preventDefault();

  if (!cart.length) { showMsg('Your cart is empty!', 'error'); return; }

  const btn = document.getElementById('placeBtn');
  btn.disabled    = true;
  btn.textContent = 'Placing Order...';

  const fd = new FormData(this);
  const data = {
    name:    fd.get('name').trim(),
    phone:   fd.get('phone').trim(),
    email:   fd.get('email').trim(),
    address: fd.get('address').trim(),
    city:    fd.get('city').trim(),
    pincode: fd.get('pincode').trim(),
    cart:    cart,
    total:   total
  };

  fetch('place-order.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(data)
  })
  .then(r => r.text())                     // ✅ text() not json()
  .then(res => {
    if (res.includes('success')) {
      // Extract order id from "success:123"
      const parts   = res.split(':');
      const orderId = parts[1] || '';
      localStorage.removeItem('cart');
      if (typeof updateCartCount === 'function') updateCartCount();
      showMsg('\u2705 Order placed! Redirecting to your confirmation...', 'success');
      // Redirect to Order Confirmation page
      setTimeout(() => {
        window.location.href = '/proburst/pages/order-confirmation.php?order_id=' + orderId;
      }, 1200);
    } else {
      const errMap = {
        'error:missing_fields': 'Please fill in all required fields.',
        'error:empty_cart':     'Your cart is empty.',
        'error:db_failed':      'Database error. Please try again.',
        'error:invalid_json':   'Request error. Please refresh and try again.',
        'error:not_logged_in':  'Please log in to place an order.'
      };
      showMsg('❌ ' + (errMap[res.trim()] || 'Something went wrong. Please try again.'), 'error');
      btn.disabled    = false;
      btn.textContent = 'Place Order';
    }
  })
  .catch(err => {
    console.error('Checkout fetch error:', err);
    showMsg('❌ Could not reach server. Check your connection.', 'error');
    btn.disabled    = false;
    btn.textContent = 'Place Order';
  });
});

})();

function showMsg(msg, type) {
  const el = document.getElementById('checkout-msg');
  el.style.display    = 'block';
  el.style.background = type === 'success' ? '#0a2a0a' : '#2a0a0a';
  el.style.border     = '1px solid ' + (type === 'success' ? '#2d6a2d' : '#e63946');
  el.style.color      = type === 'success' ? '#6fcf97' : '#ff6b6b';
  el.textContent      = msg;
  el.scrollIntoView({ behavior: 'smooth' });
}