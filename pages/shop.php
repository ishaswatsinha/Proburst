<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="shop-page">

  <!-- LEFT SIDEBAR -->
  <aside class="sidebar">

    <h3>CATEGORIES</h3>

    <div class="filter">
      <h4>FLAVOUR</h4>

      <label><input type="checkbox"> Chocolate</label>
      <label><input type="checkbox"> Vanilla</label>
      <label><input type="checkbox"> Mango</label>
    </div>

    <div class="filter">
      <h4>PRICE</h4>
      <label><input type="checkbox"> ₹0 - ₹1000</label>
      <label><input type="checkbox"> ₹1000 - ₹3000</label>
    </div>

  </aside>


  <!-- RIGHT CONTENT -->
  <div class="products-section">

    <!-- TOP BAR -->
    <div class="top-bar">
      <h2>Shop By Products</h2>

      <select>
        <option>Best selling</option>
        <option>Price low to high</option>
        <option>Price high to low</option>
      </select>
    </div>


    <!-- PRODUCT GRID -->
    <div class="product-grid">

      <!-- CARD -->
      <div class="product-card">
        <img src="assets/images/whey.jpg">

        <h3>Gold Standard Whey Protein</h3>

        <div class="rating">⭐⭐⭐⭐☆</div>

        <p class="price">
          <span class="mrp">₹4,429</span>
          ₹3,949 <span class="off">11% OFF</span>
        </p>

        <small>4 Flavours</small>
      </div>


      <div class="product-card">
        <img src="assets/images/creatine.jpg">

        <h3>Micronized Creatine Powder</h3>

        <div class="rating">⭐⭐⭐⭐☆</div>

        <p class="price">
          <span class="mrp">₹1,039</span>
          ₹889 <span class="off">14% OFF</span>
        </p>

        <small>2 Flavours</small>
      </div>

    </div>

  </div>

</div>

<?php include '../includes/footer.php'; ?>