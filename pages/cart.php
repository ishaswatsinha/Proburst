<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2 style="padding:20px;">Your Cart</h2>

<div id="cart-container" style="padding:20px;"></div>

<button onclick="checkout()">Proceed to Checkout</button>

<?php include '../includes/footer.php'; ?>

<script>

let cart = JSON.parse(localStorage.getItem("cart")) || [];
let container = document.getElementById("cart-container");

function renderCart() {

  if (cart.length === 0) {
    container.innerHTML = "<p>Your cart is empty</p>";
    return;
  }

  let html = "";
  let total = 0;

  cart.forEach(item => {

    total += item.price * item.qty;

    html += `
      <div style="border-bottom:1px solid #ddd; padding:15px; display:flex; gap:20px; align-items:center;">

        <img src="../assets/images/${item.image}" width="80">

        <div style="flex:1;">
          <h3>${item.name}</h3>
          <p>₹${item.price}</p>

          <div style="margin-top:10px;">
            <button onclick="changeQty(${item.id}, -1)">−</button>
            <span style="margin:0 10px;">${item.qty}</span>
            <button onclick="changeQty(${item.id}, 1)">+</button>
          </div>
        </div>

        <div>
          <p>₹${item.price * item.qty}</p>
          <button onclick="removeItem(${item.id})">Remove</button>
        </div>

      </div>
    `;
  });

  html += `<h2 style="margin-top:20px;">Total: ₹${total}</h2>`;

  container.innerHTML = html;
}

/* CHANGE QUANTITY */
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

/* REMOVE ITEM */
function removeItem(id) {

  cart = cart.filter(item => item.id !== id);

  localStorage.setItem("cart", JSON.stringify(cart));

  updateCartCount();
  renderCart();
}

/* INITIAL LOAD */
renderCart();

</script>