<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="checkout">

  <h2>Checkout</h2>

  <div class="checkout-container">

    <!-- LEFT: FORM -->
    <div class="checkout-form">

      <h3>Shipping Details</h3>

      <form id="checkoutForm">

        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="email" name="email" placeholder="Email Address" required>

        <textarea name="address" placeholder="Full Address" required></textarea>

        <div class="row">
          <input type="text" name="city" placeholder="City" required>
          <input type="text" name="pincode" placeholder="Pincode" required>
        </div>

        <button type="submit">Place Order</button>

      </form>

    </div>

    <!-- RIGHT: SUMMARY -->
    <div class="checkout-summary">

      <h3>Order Summary</h3>

      <div id="order-items"></div>

      <h2 id="order-total">Total: ₹0</h2>

    </div>

  </div>

</section>

<?php include '../includes/footer.php'; ?>

<script>

// LOAD CART
let cart = JSON.parse(localStorage.getItem("cart")) || [];
let container = document.getElementById("order-items");
let totalBox = document.getElementById("order-total");

let total = 0;

function renderCheckout() {

  if (cart.length === 0) {
    container.innerHTML = "<p>Your cart is empty</p>";
    return;
  }

  let html = "";

  cart.forEach(item => {

    total += item.price * item.qty;

    html += `
      <div class="checkout-item">
        <span>${item.name} x ${item.qty}</span>
        <span>₹${item.price * item.qty}</span>
      </div>
    `;
  });

  container.innerHTML = html;
  totalBox.innerText = "Total: ₹" + total;
}

renderCheckout();

/* =========================
   PLACE ORDER
========================= */

document.getElementById("checkoutForm").addEventListener("submit", function(e) {
  e.preventDefault();

  let formData = new FormData(this);

  let data = {
    name: formData.get("name"),
    phone: formData.get("phone"),
    email: formData.get("email"),
    address: formData.get("address"),
    city: formData.get("city"),
    pincode: formData.get("pincode"),
    cart: cart,
    total: total
  };

  fetch("place-order.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  })
  .then(res => res.text())
  .then(res => {

    alert("Order Placed Successfully!");

    localStorage.removeItem("cart");

    window.location.href = "../index.php";
  });
});

</script>