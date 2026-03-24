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
     PRODUCT TABS
  ========================= */
  const tabs = document.querySelectorAll(".tab");
  const lists = document.querySelectorAll(".product-list");

  tabs.forEach(tab => {
    tab.addEventListener("click", () => {

      tabs.forEach(t => t.classList.remove("active"));
      lists.forEach(l => l.classList.remove("active"));

      tab.classList.add("active");

      const target = document.getElementById(tab.dataset.tab);
      if (target) target.classList.add("active");
    });
  });


  /* =========================
     ADD TO CART BUTTON FIX
  ========================= */

  // document.querySelectorAll(".add-cart-btn").forEach(btn => {

  //   btn.addEventListener("click", function() {

  //     const id = this.dataset.id;
  //     const name = this.dataset.name;
  //     const price = parseFloat(this.dataset.price);
  //     const image = this.dataset.image;

  //     addToCart(id, name, price, image, this);

  //   });

  // });


  /* =========================
     ADD TO CART
  ========================= */
  window.addToCart = function (id, name, price, image, btn = null) {

    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    let existing = cart.find(item => item.id == id);

    if (existing) {
      if (existing.qty >= 10) {
        showToast("Max quantity reached");
        return;
      }
      existing.qty += 1;
    } else {
      cart.push({ id, name, price, image, qty: 1 });
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
     CATEGORY SCROLL
  ========================= */
  // window.scrollCategory = function (amount) {
  //   const slider = document.getElementById("catSlider");
  //   if (!slider) return;

  //   slider.scrollBy({ left: amount, behavior: "smooth" });
  // };


  /* =========================
     REVIEW SCROLL
  ========================= */
  // window.scrollReviews = function (amount) {
  //   const slider = document.getElementById("reviewSlider");
  //   if (!slider) return;

  //   slider.scrollBy({ left: amount, behavior: "smooth" });
  // };


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

const BASE_URL = window.location.origin + ""; // DYNAMIC BASE URL
const form = document.getElementById("franchiseForm");

if (form) {

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch(BASE_URL + "/pages/save-lead.php", {   // ✅ FIXED PATH
      method: "POST",
      body: formData
    })
      .then(res => res.text())
      .then(res => {

        console.log(res); // 🔥 DEBUG

        if (res.includes("success")) {

          let phone = formData.get("phone");

          let msg = encodeURIComponent(
            "Hi, I am interested in Proburst Franchise. My number: " + phone
          );

          window.location.href = `https://wa.me/91YOURNUMBER?text=${msg}`;

        } else {
          alert("Error: " + res);
        }

      })
      .catch(err => {
        console.error(err);
        alert("Something went wrong!");
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
   DROPDOWN (FIXED)
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