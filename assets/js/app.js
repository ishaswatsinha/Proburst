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

  /* =========================
     VIDEO REELS 
  ========================= */

  let allVideos = [];

  /* COLLECT DATA */
  document.querySelectorAll(".video-card").forEach(card => {
    allVideos.push({
      id: card.dataset.id,
      video: card.dataset.video,
      name: card.dataset.name,
      price: card.dataset.price,
      image: card.dataset.image
    });
  });


  /* OPEN MODAL */
  window.openReel = function () {

    const modal = document.getElementById("reelModal");
    const container = document.getElementById("reelContainer");

    if (!modal || !container) return;

    container.innerHTML = allVideos.map(v => `
    <div class="reel">

      <video playsinline loop>
        <source src="assets/videos/${v.video}">
      </video>

      <div class="mute-btn">🔊</div>

      <div class="reel-overlay">
        <h3>${v.name}</h3>
        <p>₹${v.price}</p>
<button 
  class="add-cart-btn"
  onclick="addToCart(
    ${v.id},
    '${v.name.replace(/'/g, "\\'")}',
    ${v.price},
    '${v.image}',
    this
  )"
>
  Add to Cart
</button>


      </div>

    </div>
  `).join("");

    modal.style.display = "flex";
    document.body.style.overflow = "hidden";

    observeVideos();
  };





  /* CLOSE */
  function closeReel() {
    const modal = document.getElementById("reelModal");
    if (!modal) return;

    modal.style.display = "none";
    document.body.style.overflow = "auto";

    document.querySelectorAll(".reel video").forEach(v => v.pause());
  }

  /* CLOSE BUTTON */
  document.getElementById("closeReelBtn")?.addEventListener("click", closeReel);

  /* CLICK OUTSIDE */
  document.getElementById("reelModal")?.addEventListener("click", function (e) {
    if (e.target.id === "reelModal") closeReel();
  });


  /* AUTO PLAY */
  function observeVideos() {

    const videos = document.querySelectorAll(".reel video");

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {

        if (entry.isIntersecting) {
          entry.target.muted = false;
          entry.target.volume = 1;
          entry.target.play();
        } else {
          entry.target.pause();
        }

      });
    }, { threshold: 0.7 });

    videos.forEach(v => observer.observe(v));
  }


  /* =========================
     GLOBAL CLICK HANDLER
  ========================= */

  document.addEventListener("click", function (e) {

    /* MUTE BUTTON */
    const muteBtn = e.target.closest(".mute-btn");
    if (muteBtn) {
      const video = muteBtn.closest(".reel").querySelector("video");

      video.muted = !video.muted;
      muteBtn.innerText = video.muted ? "🔇" : "🔊";
    }

  });


  /* =========================
   INFLUENCER VIDEO MODAL
========================= */

// Open modal and play video
window.openInfluencer = function(videoPath) {
  const modal = document.getElementById("infModal");
  const video = document.getElementById("infVideo");

  // Set video source
  video.src = videoPath;

  // Show modal
  modal.style.display = "flex";

  // Play video
  video.play();
};


// Close modal
window.closeInfluencer = function() {
  const modal = document.getElementById("infModal");
  const video = document.getElementById("infVideo");

  // Pause and reset video
  video.pause();
  video.currentTime = 0;
  video.src = "";

  // Hide modal
  modal.style.display = "none";
};


// Optional: close modal when clicking outside video
window.onclick = function(e) {
  const modal = document.getElementById("infModal");

  if (e.target === modal) {
    closeInfluencer();
  }
};

 
  /* =========================
     REVIEW SCROLL
  ========================= */
  window.scrollReviews = function (amount) {
    const slider = document.getElementById("reviewSlider");
    if (!slider) return;

    slider.scrollBy({ left: amount, behavior: "smooth" });
  };

  /* =========================
   REVIEW REEL MODAL
========================= */

// Open modal
window.openReviewReel = function(card) {
  const modal = document.getElementById("reviewModal");
  const video = document.getElementById("reviewVideo");

  const name = document.getElementById("reviewName");
  const role = document.getElementById("reviewRole");

  // Get data from clicked card
  const videoSrc = card.dataset.video;
  const userName = card.dataset.name;
  const userRole = card.dataset.role;

  // Set data in modal
  video.src = "assets/videos/" + videoSrc;
  name.innerText = userName;
  role.innerText = userRole;

  // Show modal
  modal.style.display = "flex";

  // Play video
  video.play();
};


// Close modal
window.closeReview = function() {
  const modal = document.getElementById("reviewModal");
  const video = document.getElementById("reviewVideo");

  // Stop video
  video.pause();
  video.currentTime = 0;
  video.src = "";

  // Hide modal
  modal.style.display = "none";
};


// Close on outside click
window.onclick = function(e) {
  const modal = document.getElementById("reviewModal");

  if (e.target === modal) {
    closeReview();
  }
};


/* =========================
   SCROLL REVIEWS
========================= */
window.scrollReviews = function(value) {
  const slider = document.getElementById("reviewSlider");
  slider.scrollLeft += value;
};


/* =========================
   LIKE BUTTON
========================= */
window.likeVideo = function(btn) {
  const countSpan = btn.querySelector("span");
  let count = parseInt(countSpan.innerText);

  count++;
  countSpan.innerText = count;

  // simple animation
  btn.style.transform = "scale(1.2)";
  setTimeout(() => {
    btn.style.transform = "scale(1)";
  }, 200);
};

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

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
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
      showMsg('✅ Order placed successfully!' + (orderId ? ' Order #' + orderId : ''), 'success');
      setTimeout(() => window.location.href = '/proburst/index.php', 2500);
    } else {
      const errMap = {
        'error:missing_fields': 'Please fill in all required fields.',
        'error:empty_cart':     'Your cart is empty.',
        'error:db_failed':      'Database error. Please try again.',
        'error:invalid_json':   'Request error. Please refresh and try again.'
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

function showMsg(msg, type) {
  const el = document.getElementById('checkout-msg');
  el.style.display    = 'block';
  el.style.background = type === 'success' ? '#0a2a0a' : '#2a0a0a';
  el.style.border     = '1px solid ' + (type === 'success' ? '#2d6a2d' : '#e63946');
  el.style.color      = type === 'success' ? '#6fcf97' : '#ff6b6b';
  el.textContent      = msg;
  el.scrollIntoView({ behavior: 'smooth' });
}


