<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="cart-page">

  <h2 class="cart-title">Your Cart</h2>

  <div id="cart-container"></div>

  <button onclick="checkout()" class="cart-checkout-btn">
    Proceed to Checkout →
  </button>

</section>

<?php include '../includes/footer.php'; ?>

<script>

let cart = JSON.parse(localStorage.getItem("cart")) || [];
let container = document.getElementById("cart-container");

/* =========================
   CHECKOUT
========================= */
function checkout() {

  if (cart.length === 0) {
    alert("Your cart is empty!");
    return;
  }

  window.location.href = "checkout.php";
}

/* =========================
   RENDER CART
========================= */
function renderCart() {

  if (cart.length === 0) {
    container.innerHTML = "<p class='cart-empty'>Your cart is empty</p>";
    return;
  }

  let html = "";
  let total = 0;

  cart.forEach(item => {

    total += item.price * item.qty;

    html += `
      <div class="cart-item">

        <img src="../assets/images/${item.image}">

        <div class="cart-details">
          <h3>${item.name}</h3>
          <p>₹${item.price}</p>

          <div class="cart-qty">
            <button onclick="changeQty(${item.id}, -1)">−</button>
            <span>${item.qty}</span>
            <button onclick="changeQty(${item.id}, 1)">+</button>
          </div>
        </div>

        <div class="cart-actions">
          <p>₹${item.price * item.qty}</p>
          <button class="cart-remove" onclick="removeItem(${item.id})">
            Remove
          </button>
        </div>

      </div>
    `;
  });

  html += `<div class="cart-total">Total: ₹${total}</div>`;

  container.innerHTML = html;
}

/* =========================
   CHANGE QTY
========================= */
function changeQty(id, change) {

  cart = cart.map(item => {

    if (item.id === id) {
      item.qty += change;

      if (item.qty <= 0) return null;
    }

    return item;
  }).filter(item => item !== null);

  localStorage.setItem("cart", JSON.stringify(cart));

  updateCartCount();
  renderCart();
}

/* =========================
   REMOVE ITEM
========================= */
function removeItem(id) {

  cart = cart.filter(item => item.id !== id);

  localStorage.setItem("cart", JSON.stringify(cart));

  updateCartCount();
  renderCart();
}

/* INITIAL LOAD */
renderCart();

</script>