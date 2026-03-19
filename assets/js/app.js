/* =========================
   HERO SLIDER (FIXED)
========================= */

document.querySelectorAll(".hero-slider").forEach(wrapper => {

  const slider = wrapper.querySelector(".slider");
  const slides = wrapper.querySelectorAll(".slide");
  const dotsContainer = wrapper.querySelector(".dots");
  const nextBtn = wrapper.querySelector(".next");
  const prevBtn = wrapper.querySelector(".prev");

  let index = 0;
  let interval;

  /* CREATE DOTS */
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

  /* BUTTONS */
  nextBtn && nextBtn.addEventListener("click", next);
  prevBtn && prevBtn.addEventListener("click", prev);

  /* AUTO SLIDE */
  function startAuto() {
    interval = setInterval(next, 5000);
  }

  function resetAuto() {
    clearInterval(interval);
    startAuto();
  }

  startAuto();

  /* SWIPE */
  let startX = 0;

  slider.addEventListener("touchstart", e => {
    startX = e.touches[0].clientX;
  });

  slider.addEventListener("touchend", e => {
    let endX = e.changedTouches[0].clientX;

    if (startX > endX + 50) next();
    else if (startX < endX - 50) prev();
  });

  update();
});


/* =========================
   RANGE SLIDER (IMAGE + TEXT)
========================= */

document.querySelectorAll(".range-slider").forEach(wrapper => {

  const track = wrapper.querySelector(".range-track");
  const items = wrapper.querySelectorAll(".range-item");
  const dotsContainer = wrapper.querySelector(".range-dots");
  const nextBtn = wrapper.querySelector(".next");
  const prevBtn = wrapper.querySelector(".prev");

  let index = 0;
  let interval;

  /* DOTS */
  if (dotsContainer) {
    dotsContainer.innerHTML = "";
    items.forEach((_, i) => {
      let dot = document.createElement("span");
      dot.addEventListener("click", () => goTo(i));
      dotsContainer.appendChild(dot);
    });
  }

  const dots = dotsContainer ? dotsContainer.querySelectorAll("span") : [];

  function update() {
    track.style.transform = `translateX(-${index * 100}%)`;

    dots.forEach(d => d.classList.remove("active"));
    if (dots[index]) dots[index].classList.add("active");
  }

  function next() {
    index = (index + 1) % items.length;
    update();
  }

  function prev() {
    index = (index - 1 + items.length) % items.length;
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
    interval = setInterval(next, 6000);
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



// ===========================
// FOOTER 
// ==================================

const cols = document.querySelectorAll('.footer-col');

window.addEventListener('scroll', () => {
    cols.forEach(col => {
        let pos = col.getBoundingClientRect().top;
        if (pos < window.innerHeight - 50) {
            col.style.opacity = "1";
            col.style.transform = "translateY(0)";
        }
    });
});


// ==============================
// HAMBURGER MENU (MOBILE)
// ==================================


const hamburger = document.getElementById("hamburger");
const menu = document.querySelector(".menu-bar");
const overlay = document.getElementById("overlay");

/* OPEN MENU */
hamburger.onclick = () => {
  menu.classList.add("active");
  overlay.classList.add("active");
}

/* CLOSE MENU */
overlay.onclick = () => {
  menu.classList.remove("active");
  overlay.classList.remove("active");

  /* CLOSE ALL DROPDOWNS */
  document.querySelectorAll(".dropdown, .has-submenu").forEach(el => {
    el.classList.remove("active");
  });
}

/* MAIN DROPDOWN */
document.querySelectorAll(".dropdown").forEach(item => {

  item.addEventListener("click", function(e) {

    if (window.innerWidth < 768) {

      e.stopPropagation();

      /* CLOSE OTHER DROPDOWNS */
      document.querySelectorAll(".dropdown").forEach(el => {
        if (el !== this) el.classList.remove("active");
      });

      /* TOGGLE CURRENT */
      this.classList.toggle("active");
    }

  });

});

/* SUBMENU FIX */
document.querySelectorAll(".has-submenu").forEach(item => {

  item.addEventListener("click", function(e) {

    if (window.innerWidth < 768) {

      e.stopPropagation();

      /* CLOSE OTHER SUBMENUS */
      document.querySelectorAll(".has-submenu").forEach(el => {
        if (el !== this) el.classList.remove("active");
      });

      /* TOGGLE */
      this.classList.toggle("active");

    }

  });

});

/* CLICK OUTSIDE MENU CLOSE */
document.addEventListener("click", function(e) {

  if (window.innerWidth < 768) {

    if (!menu.contains(e.target) && !hamburger.contains(e.target)) {

      menu.classList.remove("active");
      overlay.classList.remove("active");

      document.querySelectorAll(".dropdown, .has-submenu").forEach(el => {
        el.classList.remove("active");
      });

    }

  }

});

