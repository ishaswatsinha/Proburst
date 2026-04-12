<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="cart-page">

  <h2 class="cart-title">Your Cart</h2>

  <div id="cart-container"></div>

  <div class="cart-total-box">
    Total: <span id="cart-total">₹0</span>
  </div>

  <button onclick="checkout()" class="cart-checkout-btn">
    Proceed to Checkout →
  </button>

</section>

<?php include '../includes/footer.php'; ?>

<script>

/* =========================
   HELPERS
========================= */
function fmtPrice(n) {
  // Always parse as float first — localStorage stores strings
  return '₹' + parseFloat(n).toLocaleString('en-IN');
}

/* =========================
   LOAD CART
========================= */
function getCart() {
  return JSON.parse(localStorage.getItem("cart")) || [];
}

/* =========================
   SAVE CART
========================= */
function saveCart(cart) {
  localStorage.setItem("cart", JSON.stringify(cart));
}

/* =========================
   RENDER FULL CART
========================= */
function renderCart() {

  let cart = getCart();
  let container = document.getElementById("cart-container");

  if (cart.length === 0) {
    container.innerHTML = "<p class='cart-empty'>Your cart is empty</p>";
    document.getElementById("cart-total").textContent = "₹0";
    return;
  }

  let html = "";

  cart.forEach(item => {
    const price    = parseFloat(item.price);
    const subtotal = price * item.qty;

    html += `
      <div class="cart-item" data-id="${item.id}" data-price="${price}">

        <img src="../assets/images/${item.image}" class="cart-img">

        <div class="cart-details">
          <h3>${item.name}</h3>
          <p>${fmtPrice(price)}</p>

          <div class="cart-qty">
            <button onclick="changeQty(${item.id}, -1)">−</button>
            <span class="qty">${item.qty}</span>
            <button onclick="changeQty(${item.id}, 1)">+</button>
          </div>
        </div>

        <div class="cart-actions">
          <p class="subtotal">${fmtPrice(subtotal)}</p>
          <button class="cart-remove" onclick="removeItem(${item.id})">Remove</button>
        </div>

      </div>
    `;
  });

  container.innerHTML = html;
  updateTotal();
}

/* =========================
   UPDATE TOTAL — live, no refresh needed
========================= */
function updateTotal() {
  let cart  = getCart();
  let total = 0;
  cart.forEach(item => {
    total += parseFloat(item.price) * parseInt(item.qty);
  });
  document.getElementById("cart-total").textContent = fmtPrice(total);
}

/* =========================
   CHANGE QTY — updates DOM live
========================= */
function changeQty(id, change) {

  let cart = getCart();

  cart = cart.map(item => {
    if (item.id == id) {
      item.qty = parseInt(item.qty) + change;
      if (item.qty <= 0) return null;
    }
    return item;
  }).filter(i => i !== null);

  saveCart(cart);

  let item = cart.find(i => i.id == id);
  let card = document.querySelector(`.cart-item[data-id="${id}"]`);

  if (!item && card) {
    // Item removed — animate out then remove
    card.style.transition = "opacity .25s, transform .25s";
    card.style.opacity    = "0";
    card.style.transform  = "translateX(-10px)";
    setTimeout(() => {
      card.remove();
      if (getCart().length === 0) {
        document.getElementById("cart-container").innerHTML =
          "<p class='cart-empty'>Your cart is empty</p>";
        document.getElementById("cart-total").textContent = "₹0";
      }
    }, 250);
  } else if (item && card) {
    // Update qty display
    card.querySelector(".qty").textContent = item.qty;
    // Update subtotal live — parse price from data attribute
    const price = parseFloat(card.dataset.price);
    card.querySelector(".subtotal").textContent = fmtPrice(price * item.qty);
  }

  updateCartCount();
  // Update grand total live — no refresh needed
  updateTotal();
}

/* =========================
   REMOVE ITEM
========================= */
function removeItem(id) {

  let cart = getCart().filter(item => item.id != id);
  saveCart(cart);

  let card = document.querySelector(`.cart-item[data-id="${id}"]`);
  if (card) {
    card.style.transition = "opacity .25s, transform .25s";
    card.style.opacity    = "0";
    card.style.transform  = "translateX(-10px)";
    setTimeout(() => {
      card.remove();
      updateTotal();
      if (getCart().length === 0) {
        document.getElementById("cart-container").innerHTML =
          "<p class='cart-empty'>Your cart is empty</p>";
        document.getElementById("cart-total").textContent = "₹0";
      }
    }, 250);
  }

  updateCartCount();
}

/* =========================
   CHECKOUT — checks login first
========================= */
function checkout() {

  let cart = getCart();

  if (cart.length === 0) {
    alert("Your cart is empty!");
    return;
  }

  // Check login status via AJAX — redirect to login if not logged in
  fetch("/proburst/ajax/check-login.php")
    .then(r => r.json())
    .then(data => {
      if (data.loggedIn) {
        window.location.href = "checkout.php";
      } else {
        window.location.href = "/proburst/pages/login.php?redirect=/proburst/pages/checkout.php";
      }
    })
    .catch(() => {
      // Fallback — server will guard checkout anyway
      window.location.href = "checkout.php";
    });
}

/* INIT */
renderCart();

</script>
