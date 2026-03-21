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
    if (!slider) return;
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
   ADD TO CART (GLOBAL)
========================= */
function addToCart(id, name, price, image, btn = null) {

  let cart = JSON.parse(localStorage.getItem("cart")) || [];

  let existing = cart.find(item => item.id == id);

  if (existing) {

    if (existing.qty >= 10) {
      showToast("Max quantity reached");
      return;
    }

    existing.qty += 1;

  } else {

    cart.push({
      id: id,
      name: name,
      price: price,
      image: image,
      qty: 1
    });

  }

  localStorage.setItem("cart", JSON.stringify(cart));

  updateCartCount();
  animateToCart(image, btn);
  showToast("Added to cart 🛒");
}


/* =========================
   BUTTON CLICK HANDLER (ALL PAGES)
========================= */
document.querySelectorAll('.add-cart-btn').forEach(btn => {

  btn.addEventListener('click', function () {

    const id = parseInt(this.dataset.id);
    const name = this.dataset.name;
    const price = parseFloat(this.dataset.price);
    const image = this.dataset.image;

    addToCart(id, name, price, image, this);

  });

});


/* =========================
   CART COUNT
========================= */
function updateCartCount() {

  let cart = JSON.parse(localStorage.getItem("cart")) || [];

  let count = 0;

  cart.forEach(item => {
    count += item.qty;
  });

  let badge = document.getElementById("cart-count");

  if (badge) {
    badge.innerText = count;
  }
}

updateCartCount();


/* =========================
   ANIMATION (FLY TO CART)
========================= */
function animateToCart(image, btn) {

  if (!btn) return;

  let img = document.createElement("img");
  img.src = "../assets/images/" + image;

  img.style.position = "fixed";
  img.style.width = "80px";
  img.style.zIndex = "9999";
  img.style.borderRadius = "10px";

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

  setTimeout(() => {
    img.remove();
    cart.classList.add("cart-bounce");
    setTimeout(() => cart.classList.remove("cart-bounce"), 300);
  }, 700);
}


/* =========================
   TOAST
========================= */
function showToast(msg) {

  let toast = document.getElementById("toast");

  if (!toast) return;

  toast.innerText = msg;
  toast.classList.add("show");

  setTimeout(() => {
    toast.classList.remove("show");
  }, 2000);
}


// ==================================
// VIDEO SECTION 
// ==================================

let reels = [];
let currentIndex = 0;

/* OPEN */
function openReel(clickedCard) {

  const cards = document.querySelectorAll(".video-card");
  reels = [];

  cards.forEach(card => {
    reels.push({
      id: card.dataset.id,
      video: card.dataset.video,
      name: card.dataset.name,
      price: card.dataset.price,
      image: card.dataset.image
    });
  });

  currentIndex = Array.from(cards).indexOf(clickedCard);

  renderReels();

  document.getElementById("reelModal").style.display = "flex";
}

/* RENDER */
function renderReels() {

  const container = document.getElementById("reelContainer");
  container.innerHTML = "";

  reels.forEach((item, index) => {

    const div = document.createElement("div");
    div.classList.add("reel");

    div.innerHTML = `
      <video src="assets/videos/${item.video}" muted ${index === currentIndex ? "autoplay" : ""}></video>

      <div class="reel-overlay">
        <h3>${item.name}</h3>
        <p>₹${item.price}</p>
        <button onclick="addToCart(${item.id}, '${item.name}', ${item.price}, '${item.image}', this)">
          Add to Cart
        </button>
      </div>
    `;

    container.appendChild(div);
  });

  scrollToCurrent();
  playCurrentVideo();
}

/* SCROLL POSITION */
function scrollToCurrent() {
  const container = document.getElementById("reelContainer");
  container.scrollTop = currentIndex * container.clientHeight;
}

/* PLAY CONTROL */
function playCurrentVideo() {

  const videos = document.querySelectorAll(".reel video");

  videos.forEach((vid, i) => {
    if (i === currentIndex) {
      vid.play();
    } else {
      vid.pause();
    }
  });
}

/* SCROLL DETECT */
document.addEventListener("DOMContentLoaded", () => {

  const container = document.getElementById("reelContainer");

  container.addEventListener("scroll", () => {

    const index = Math.round(container.scrollTop / container.clientHeight);

    if (index !== currentIndex) {
      currentIndex = index;
      playCurrentVideo();
    }

  });

});

/* AUTO NEXT */
document.addEventListener("ended", (e) => {

  if (e.target.tagName === "VIDEO") {

    if (currentIndex < reels.length - 1) {
      currentIndex++;
      scrollToCurrent();
      playCurrentVideo();
    }

  }

}, true);

/* CLOSE */
function closeReel() {
  document.getElementById("reelModal").style.display = "none";

  document.querySelectorAll(".reel video").forEach(v => v.pause());
}

// ====================================
//  INFO MODAL 
// =====================================

function openInfluencer(video) {

  const modal = document.getElementById("infModal");
  const vid = document.getElementById("infVideo");

  vid.src = "assets/videos/" + video;

  modal.style.display = "flex";

  vid.play();
}

function closeInfluencer() {

  const modal = document.getElementById("infModal");
  const vid = document.getElementById("infVideo");

  vid.pause();
  vid.src = "";

  modal.style.display = "none";
}