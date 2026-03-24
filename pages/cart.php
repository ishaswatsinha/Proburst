<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="cart-page">

  <h2 class="cart-title">Your Cart</h2>

  <div id="cart-container"></div>

  <div class="cart-total-box">
    Total: ₹<span id="cart-total">0</span>
  </div>

  <button onclick="checkout()" class="cart-checkout-btn">
    Proceed to Checkout →
  </button>

</section>

<?php include '../includes/footer.php'; ?>

<script>

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
   RENDER INITIAL
========================= */
function renderCart() {

  let cart = getCart();
  let container = document.getElementById("cart-container");

  if (cart.length === 0) {
    container.innerHTML = "<p class='cart-empty'>Your cart is empty</p>";
    return;
  }

  let html = "";

  cart.forEach(item => {
    html += `
      <div class="cart-item" data-id="${item.id}">

        <img src="../assets/images/${item.image}" class="cart-img">

        <div class="cart-details">
          <h3>${item.name}</h3>
          <p>₹${item.price}</p>

          <div class="cart-qty">
            <button onclick="changeQty(${item.id}, -1)">−</button>
            <span class="qty">${item.qty}</span>
            <button onclick="changeQty(${item.id}, 1)">+</button>
          </div>
        </div>

        <div class="cart-actions">
          <p class="subtotal">₹${item.price * item.qty}</p>
          <button class="cart-remove" onclick="removeItem(${item.id})">Remove</button>
        </div>

      </div>
    `;
  });

  container.innerHTML = html;

  updateTotal();
}

/* =========================
   UPDATE TOTAL ONLY
========================= */
function updateTotal() {

  let cart = getCart();
  let total = 0;

  cart.forEach(item => total += item.price * item.qty);

  document.getElementById("cart-total").innerText = total;
}

/* =========================
   CHANGE QTY (AJAX STYLE)
========================= */
function changeQty(id, change) {

  let cart = getCart();

  cart = cart.map(item => {

    if (item.id == id) {
      item.qty += change;

      if (item.qty <= 0) return null;
    }

    return item;
  }).filter(i => i !== null);

  saveCart(cart);

  // 🔥 UPDATE ONLY THIS ITEM
  let item = cart.find(i => i.id == id);
  let card = document.querySelector(`.cart-item[data-id="${id}"]`);

  if (!item && card) {
    card.remove();
  } else if (item && card) {
    card.querySelector(".qty").innerText = item.qty;
    card.querySelector(".subtotal").innerText = "₹" + (item.qty * item.price);
  }

  updateCartCount();
  updateTotal();
}

/* =========================
   REMOVE ITEM (AJAX STYLE)
========================= */
function removeItem(id) {

  let cart = getCart().filter(item => item.id != id);

  saveCart(cart);

  // 🔥 REMOVE ONLY THIS ITEM
  document.querySelector(`.cart-item[data-id="${id}"]`)?.remove();

  updateCartCount();
  updateTotal();

  if (cart.length === 0) {
    document.getElementById("cart-container").innerHTML =
      "<p class='cart-empty'>Your cart is empty</p>";
  }
}

/* =========================
   CHECKOUT
========================= */
function checkout() {

  let cart = getCart();

  if (cart.length === 0) {
    alert("Your cart is empty!");
    return;
  }

  window.location.href = "checkout.php";
}

/* INIT */
renderCart();

</script>