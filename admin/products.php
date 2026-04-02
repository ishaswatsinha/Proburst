<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

$msg = ''; $msgType = 'success';

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function uploadOneImage(array $fileArr, string $destDir): string|false {
    $allowed = ['jpg','jpeg','png','webp','avif','gif'];
    $ext     = strtolower(pathinfo($fileArr['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed))        return false;
    if ($fileArr['error'] !== UPLOAD_ERR_OK) return false;
    $name = uniqid('product_', true) . '.' . $ext;
    if (!move_uploaded_file($fileArr['tmp_name'], $destDir . $name)) return false;
    return $name;
}

$imgDir = __DIR__ . '/../assets/images/';

// ── DELETE PRODUCT ──
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];

    // 1. Fetch cover image BEFORE deleting
    $prodRow = $conn->query("SELECT image FROM products WHERE id=$did")->fetch_assoc();

    // 2. Fetch all gallery images BEFORE deleting
    $galRes  = $conn->query("SELECT image FROM product_images WHERE product_id=$did");
    $galImgs = [];
    if ($galRes) while ($g = $galRes->fetch_assoc()) $galImgs[] = $g['image'];

    // 3. Delete from DB (gallery rows cascade-delete via FK)
    $conn->query("DELETE FROM products WHERE id=$did");

    // 4. Delete cover image from disk
    if (!empty($prodRow['image'])) {
        $p = $imgDir . $prodRow['image'];
        if (file_exists($p)) @unlink($p);
    }

    // 5. Delete every gallery image from disk
    foreach ($galImgs as $gf) {
        $p = $imgDir . $gf;
        if (file_exists($p)) @unlink($p);
    }

    $msg = "Product and all its images deleted successfully.";
}

// ── DELETE GALLERY IMAGE ──
if (isset($_GET['delete_img'])) {
    $imgId = (int)$_GET['delete_img'];
    $row   = $conn->query("SELECT image, product_id FROM product_images WHERE id=$imgId")->fetch_assoc();
    if ($row) {
        @unlink($imgDir . $row['image']);
        $conn->query("DELETE FROM product_images WHERE id=$imgId");
        $msg = "Gallery image removed.";
        // redirect back to edit page
        header("Location: products.php?edit=" . $row['product_id'] . "&msg=imgdeleted");
        exit;
    }
}

// ── ADD / EDIT PRODUCT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $pid   = (int)($_POST['product_id'] ?? 0);
    $name  = $conn->real_escape_string(trim($_POST['name']));
    $slug  = $conn->real_escape_string($_POST['slug'] ?: slugify($_POST['name']));
    $catId = (int)$_POST['category_id'];
    $subId = (int)$_POST['subcategory_id'];
    $price = (float)$_POST['price'];
    $mrp   = (float)$_POST['mrp'];
    $disc  = (int)$_POST['discount_percent'];
    $stock = (int)$_POST['stock'];
    $desc  = $conn->real_escape_string(trim($_POST['description']));

    // ── MAIN (cover) IMAGE ──
    $image = $conn->real_escape_string(trim($_POST['image_current'] ?? ''));
    if (!empty($_FILES['image_file']['name'])) {
        $uploaded = uploadOneImage($_FILES['image_file'], $imgDir);
        if ($uploaded) {
            $image = $conn->real_escape_string($uploaded);
        } else {
            $msg = "⚠️ Main image upload failed. Check file type and folder permissions.";
            $msgType = 'danger';
        }
    }

    if ($msgType === 'success') {
        if ($pid > 0) {
            $conn->query("UPDATE products SET name='$name',slug='$slug',category_id=$catId,subcategory_id=$subId,
                price=$price,mrp=$mrp,discount_percent=$disc,stock=$stock,description='$desc',image='$image'
                WHERE id=$pid");
            $msg = "✅ Product updated successfully.";
        } else {
            $conn->query("INSERT INTO products (name,slug,category_id,subcategory_id,price,mrp,discount_percent,stock,description,image)
                VALUES ('$name','$slug',$catId,$subId,$price,$mrp,$disc,$stock,'$desc','$image')");
            $pid = $conn->insert_id;
            $msg = "✅ Product added successfully.";
        }

        // Save weights
        if (!empty($_POST['weight_label'])) {
            $conn->query("DELETE FROM product_weights WHERE product_id=$pid");
            foreach ($_POST['weight_label'] as $i => $wl) {
                $wl = $conn->real_escape_string(trim($wl));
                if (!$wl) continue;
                $wp = (float)($_POST['weight_price'][$i] ?? 0);
                $wm = (float)($_POST['weight_mrp'][$i]   ?? 0);
                $wi = isset($_POST['weight_instock'][$i]) ? 1 : 0;
                $wo = (int)($i + 1);
                $conn->query("INSERT INTO product_weights (product_id,label,price,mrp,in_stock,sort_order)
                    VALUES ($pid,'$wl',$wp,$wm,$wi,$wo)");
            }
        }

        // Save flavours
        if (!empty($_POST['flavour_name'])) {
            $conn->query("DELETE FROM product_flavours WHERE product_id=$pid");
            foreach ($_POST['flavour_name'] as $i => $fn) {
                $fn = $conn->real_escape_string(trim($fn));
                if (!$fn) continue;
                $fi = isset($_POST['flavour_instock'][$i]) ? 1 : 0;
                $fo = (int)($i + 1);
                $conn->query("INSERT INTO product_flavours (product_id,name,in_stock,sort_order)
                    VALUES ($pid,'$fn',$fi,$fo)");
            }
        }

        // ── GALLERY IMAGES (multiple) ──
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $files = $_FILES['gallery_images'];
            $count = count($files['name']);
            // get current max sort_order
            $maxRow = $conn->query("SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id=$pid")->fetch_row();
            $order  = (int)$maxRow[0];
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $single = [
                    'name'     => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                ];
                $uploaded = uploadOneImage($single, $imgDir);
                if ($uploaded) {
                    $order++;
                    $esc = $conn->real_escape_string($uploaded);
                    $conn->query("INSERT INTO product_images (product_id,image,sort_order)
                        VALUES ($pid,'$esc',$order)");
                }
            }
        }

        // Redirect to edit page so gallery shows up fresh
        if ($pid > 0) {
            header("Location: products.php?edit=$pid&saved=1");
            exit;
        }
    }
}

// ── LOAD EDIT PRODUCT ──
$editProduct  = null;
$editWeights  = [];
$editFlavours = [];
$editGallery  = [];
if (isset($_GET['edit'])) {
    $eid         = (int)$_GET['edit'];
    $editProduct = $conn->query("SELECT * FROM products WHERE id=$eid")->fetch_assoc();
    $wRes        = $conn->query("SELECT * FROM product_weights WHERE product_id=$eid ORDER BY sort_order");
    while ($w = $wRes->fetch_assoc()) $editWeights[] = $w;
    $fRes        = $conn->query("SELECT * FROM product_flavours WHERE product_id=$eid ORDER BY sort_order");
    while ($f = $fRes->fetch_assoc()) $editFlavours[] = $f;
    $gRes        = $conn->query("SELECT * FROM product_images WHERE product_id=$eid ORDER BY sort_order");
    while ($g = $gRes->fetch_assoc()) $editGallery[] = $g;
}

if (!empty($_GET['saved'])) $msg = "✅ Product saved successfully.";

// ── LOAD DATA ──
$categories    = $conn->query("SELECT * FROM categories ORDER BY name");
$subcategories = $conn->query("SELECT * FROM subcategories ORDER BY name");
$products      = $conn->query("
    SELECT p.*, c.name AS cname,
           (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS gallery_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
");

adminHead('Products');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Products <span>Management</span></h1>
    <p class="page-subtitle">Add, edit, and manage products, gallery images, weights & flavours.</p>
  </div>
  <a href="products.php?add=1" class="btn btn-primary">+ Add Product</a>
</div>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ═══ ADD / EDIT FORM ═══ -->
<?php if (isset($_GET['add']) || $editProduct): ?>
<div class="form-card">
  <h2 class="table-card-title" style="margin-bottom:20px">
    <?= $editProduct ? 'Edit Product #' . $editProduct['id'] : 'Add New Product' ?>
  </h2>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?? 0 ?>">

    <!-- BASIC FIELDS -->
    <div class="form-grid">
      <div class="form-group">
        <label>Product Name *</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Slug (auto if empty)</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($editProduct['slug'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select name="category_id" required>
          <option value="">Select Category</option>
          <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= ($editProduct['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Subcategory</label>
        <select name="subcategory_id">
          <option value="0">None</option>
          <?php $subcategories->data_seek(0); while ($s = $subcategories->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= ($editProduct['subcategory_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Base Price (₹) *</label>
        <input type="number" name="price" step="0.01" required value="<?= $editProduct['price'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label>MRP (₹)</label>
        <input type="number" name="mrp" step="0.01" value="<?= $editProduct['mrp'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label>Discount %</label>
        <input type="number" name="discount_percent" min="0" max="100" value="<?= $editProduct['discount_percent'] ?? 0 ?>">
      </div>
      <div class="form-group">
        <label>Stock (units)</label>
        <input type="number" name="stock" min="0" value="<?= $editProduct['stock'] ?? 0 ?>">
      </div>
    </div>

    <div class="form-group" style="margin-top:14px">
      <label>Description</label>
      <textarea name="description"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
    </div>

    <!-- ══════════════════════════════════════════
         MAIN (COVER) IMAGE
    ══════════════════════════════════════════ -->
    <div class="divider"></div>
    <p class="fw-bold" style="margin-bottom:4px">🖼️ Main / Cover Image</p>
    <p class="text-muted" style="font-size:12px;margin-bottom:14px">This is the primary image shown on product cards and the top of the product page.</p>

    <input type="hidden" name="image_current" value="<?= htmlspecialchars($editProduct['image'] ?? '') ?>">
    <label for="imageFile" class="upload-area" id="uploadArea">
      <div class="upload-icon">📁</div>
      <div class="upload-text">Click to choose a cover image, or drag & drop</div>
      <div class="upload-hint">JPG, PNG, WEBP, AVIF, GIF</div>
      <input type="file" name="image_file" id="imageFile" accept="image/*" style="display:none">
    </label>
    <div id="previewBox" style="margin-top:12px;display:<?= !empty($editProduct['image']) ? 'flex' : 'none' ?>;align-items:center;gap:14px;">
      <img id="imagePreview"
        src="<?= !empty($editProduct['image']) ? '../assets/images/'.htmlspecialchars($editProduct['image']) : '' ?>"
        style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:2px solid var(--border);">
      <div>
        <div id="previewName" style="font-size:13px;font-weight:600;color:var(--text);">
          <?= htmlspecialchars($editProduct['image'] ?? '') ?>
        </div>
        <div id="previewNote" style="font-size:12px;color:var(--muted);margin-top:3px;">
          <?= !empty($editProduct['image']) ? 'Current cover image — choose a new file to replace.' : '' ?>
        </div>
        <button type="button" onclick="clearCoverImage()"
          style="margin-top:6px;background:none;border:none;color:var(--danger);font-size:12px;cursor:pointer;padding:0;">
          ✕ Remove
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         GALLERY IMAGES (multiple)
    ══════════════════════════════════════════ -->
    <div class="divider"></div>
    <p class="fw-bold" style="margin-bottom:4px">🖼️ Gallery Images <span style="font-size:12px;font-weight:400;color:var(--muted)">(shown as thumbnails on product page)</span></p>
    <p class="text-muted" style="font-size:12px;margin-bottom:14px">You can upload multiple images at once. They will appear alongside the main image in the gallery.</p>

    <!-- Existing gallery thumbnails (edit mode) -->
    <?php if (!empty($editGallery)): ?>
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;" id="existingGallery">
      <?php foreach($editGallery as $gi): ?>
      <div style="position:relative;display:inline-block;">
        <img src="../assets/images/<?= htmlspecialchars($gi['image']) ?>"
          style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--border);">
        <a href="products.php?delete_img=<?= $gi['id'] ?>"
           onclick="return confirm('Remove this gallery image?')"
           style="position:absolute;top:-6px;right:-6px;background:var(--danger);color:#fff;
                  border-radius:50%;width:20px;height:20px;display:flex;align-items:center;
                  justify-content:center;font-size:11px;text-decoration:none;font-weight:700;">✕</a>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="text-muted" style="font-size:12px;margin-bottom:12px;">
      <?= count($editGallery) ?> gallery image(s) saved. Click ✕ on any image to remove it.
    </p>
    <?php endif; ?>

    <!-- New gallery upload -->
    <label for="galleryInput" class="upload-area" id="galleryArea" style="border-color:var(--info);">
      <div class="upload-icon">🖼️</div>
      <div class="upload-text">Click to add more gallery images</div>
      <div class="upload-hint">Select multiple files at once — hold Ctrl/Cmd to pick more than one</div>
      <input type="file" name="gallery_images[]" id="galleryInput" accept="image/*" multiple style="display:none">
    </label>

    <!-- Preview grid for newly selected gallery images -->
    <div id="galleryPreviewGrid" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;"></div>

    <!-- WEIGHTS -->
    <div class="divider"></div>
    <p class="fw-bold" style="margin-bottom:12px">Weight / Size Variants</p>
    <div id="weights-container">
      <?php
      $displayWeights = !empty($editWeights) ? $editWeights : [['label'=>'','price'=>'','mrp'=>'','in_stock'=>1]];
      foreach ($displayWeights as $i => $w): ?>
      <div class="form-grid weight-row" style="margin-bottom:10px;align-items:center">
        <div class="form-group"><label>Label</label><input type="text" name="weight_label[]" placeholder="e.g. 1kg" value="<?= htmlspecialchars($w['label']) ?>"></div>
        <div class="form-group"><label>Price (₹)</label><input type="number" name="weight_price[]" step="0.01" value="<?= $w['price'] ?? '' ?>"></div>
        <div class="form-group"><label>MRP (₹)</label><input type="number" name="weight_mrp[]" step="0.01" value="<?= $w['mrp'] ?? '' ?>"></div>
        <div class="form-group" style="justify-content:flex-end">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="checkbox" name="weight_instock[<?= $i ?>]" <?= ($w['in_stock'] ?? 1) ? 'checked' : '' ?>> In Stock
          </label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" onclick="addWeight()" class="btn btn-ghost btn-sm" style="margin-bottom:16px">+ Add Weight</button>

    <!-- FLAVOURS -->
    <div class="divider"></div>
    <p class="fw-bold" style="margin-bottom:12px">Flavour Variants</p>
    <div id="flavours-container">
      <?php
      $displayFlavours = !empty($editFlavours) ? $editFlavours : [['name'=>'','in_stock'=>1]];
      foreach ($displayFlavours as $i => $f): ?>
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px" class="flavour-row">
        <input type="text" name="flavour_name[]" placeholder="Flavour name" value="<?= htmlspecialchars($f['name']) ?>"
          style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:8px 12px;border-radius:7px;font-size:13px;flex:1">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;white-space:nowrap">
          <input type="checkbox" name="flavour_instock[<?= $i ?>]" <?= ($f['in_stock'] ?? 1) ? 'checked' : '' ?>> In Stock
        </label>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" onclick="addFlavour()" class="btn btn-ghost btn-sm" style="margin-bottom:16px">+ Add Flavour</button>

    <div class="form-actions">
      <button name="save_product" class="btn btn-primary">💾 Save Product</button>
      <a href="products.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- ═══ PRODUCTS TABLE ═══ -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">All Products (<?= $products->num_rows ?>)</span>
  </div>
  <div class="table-scroll">
  <?php if ($products->num_rows > 0): ?>
  <table>
    <thead><tr>
      <th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>MRP</th><th>Disc%</th><th>Stock</th><th>Gallery</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php while ($p = $products->fetch_assoc()): ?>
    <tr>
      <td><img src="../assets/images/<?= htmlspecialchars($p['image']) ?>" class="product-thumb" onerror="this.style.opacity=.2"></td>
      <td>
        <span class="fw-bold"><?= htmlspecialchars($p['name']) ?></span><br>
        <small class="text-muted"><?= htmlspecialchars($p['slug']) ?></small>
      </td>
      <td class="text-muted"><?= htmlspecialchars($p['cname'] ?? '—') ?></td>
      <td class="fw-bold">₹<?= number_format($p['price']) ?></td>
      <td class="text-muted"><?= $p['mrp'] ? '₹'.number_format($p['mrp']) : '—' ?></td>
      <td><?= $p['discount_percent'] ? $p['discount_percent'].'%' : '—' ?></td>
      <td>
        <span class="badge <?= $p['stock'] > 5 ? 'badge-instock' : ($p['stock'] > 0 ? 'badge-pending' : 'badge-outstock') ?>">
          <?= $p['stock'] ?>
        </span>
      </td>
      <td>
        <span class="badge" style="background:rgba(59,130,246,.15);color:#3b82f6;">
          🖼 <?= $p['gallery_count'] ?> imgs
        </span>
      </td>
      <td>
        <div class="action-group">
          <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
          <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this product and all its gallery images?')">🗑</a>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="empty-state"><div class="empty-icon">🛒</div><p>No products yet.</p></div>
  <?php endif; ?>
  </div>
</div>

<!-- UPLOAD AREA STYLES -->
<style>
.upload-area {
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:6px;padding:28px 20px;border:2px dashed var(--border);border-radius:10px;
  cursor:pointer;background:var(--surface);transition:border-color .2s,background .2s;text-align:center;
}
.upload-area:hover,.upload-area.dragover{border-color:var(--accent);background:rgba(255,77,0,.05);}
.upload-icon{font-size:30px;} .upload-text{font-size:14px;font-weight:600;color:var(--text);}
.upload-hint{font-size:12px;color:var(--muted);}
.gallery-preview-item{position:relative;display:inline-block;}
.gallery-preview-item img{width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--border);}
.gallery-preview-item .remove-btn{
  position:absolute;top:-6px;right:-6px;background:var(--danger);color:#fff;
  border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;
  font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0;
}
</style>

<script>
let wIdx = <?= count($displayWeights  ?? []) ?>;
let fIdx = <?= count($displayFlavours ?? []) ?>;

/* ── COVER IMAGE PREVIEW ── */
const fileInput   = document.getElementById('imageFile');
const uploadArea  = document.getElementById('uploadArea');
const previewBox  = document.getElementById('previewBox');
const previewImg  = document.getElementById('imagePreview');
const previewName = document.getElementById('previewName');
const previewNote = document.getElementById('previewNote');

if (uploadArea) uploadArea.addEventListener('click', () => fileInput.click());
if (fileInput) fileInput.addEventListener('change', function () {
  const file = this.files[0]; if (!file) return;
  previewImg.src = URL.createObjectURL(file);
  previewName.textContent = file.name;
  previewNote.textContent = (file.size/1024).toFixed(1)+' KB — ready to upload';
  previewBox.style.display = 'flex';
});
if (uploadArea) {
  uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
  uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
  uploadArea.addEventListener('drop', e => {
    e.preventDefault(); uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const dt = new DataTransfer(); dt.items.add(file);
    fileInput.files = dt.files;
    fileInput.dispatchEvent(new Event('change'));
  });
}
function clearCoverImage() {
  if (fileInput) fileInput.value = '';
  if (previewImg) previewImg.src = '';
  if (previewBox) previewBox.style.display = 'none';
  const cur = document.querySelector('input[name="image_current"]');
  if (cur) cur.value = '';
}

/* ── GALLERY MULTI-PREVIEW ── */
const galleryInput = document.getElementById('galleryInput');
const galleryGrid  = document.getElementById('galleryPreviewGrid');
const galleryArea  = document.getElementById('galleryArea');
let   galleryFiles = new DataTransfer();

if (galleryArea) galleryArea.addEventListener('click', () => galleryInput.click());

if (galleryInput) galleryInput.addEventListener('change', function() {
  Array.from(this.files).forEach(addGalleryPreview);
  // rebuild combined file list
  rebuildGalleryInput();
});

if (galleryArea) {
  galleryArea.addEventListener('dragover', e => { e.preventDefault(); galleryArea.classList.add('dragover'); });
  galleryArea.addEventListener('dragleave', () => galleryArea.classList.remove('dragover'));
  galleryArea.addEventListener('drop', e => {
    e.preventDefault(); galleryArea.classList.remove('dragover');
    Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')).forEach(addGalleryPreview);
    rebuildGalleryInput();
  });
}

function addGalleryPreview(file) {
  galleryFiles.items.add(file);
  const wrap = document.createElement('div');
  wrap.className = 'gallery-preview-item';
  const img = document.createElement('img');
  img.src = URL.createObjectURL(file);
  const btn = document.createElement('button');
  btn.className = 'remove-btn'; btn.type = 'button'; btn.textContent = '✕';
  btn.onclick = () => {
    // Remove from DataTransfer
    const newDT = new DataTransfer();
    Array.from(galleryFiles.files).filter(f => f !== file).forEach(f => newDT.items.add(f));
    galleryFiles = newDT;
    rebuildGalleryInput();
    wrap.remove();
  };
  wrap.appendChild(img); wrap.appendChild(btn);
  if (galleryGrid) galleryGrid.appendChild(wrap);
}

function rebuildGalleryInput() {
  if (!galleryInput) return;
  galleryInput.files = galleryFiles.files;
}

/* ── WEIGHT ROW ── */
function addWeight() {
  const c = document.getElementById('weights-container');
  const d = document.createElement('div');
  d.className = 'form-grid weight-row'; d.style.marginBottom = '10px';
  d.innerHTML = `
    <div class="form-group"><label>Label</label><input type="text" name="weight_label[]" placeholder="e.g. 2kg"></div>
    <div class="form-group"><label>Price (₹)</label><input type="number" name="weight_price[]" step="0.01"></div>
    <div class="form-group"><label>MRP (₹)</label><input type="number" name="weight_mrp[]" step="0.01"></div>
    <div class="form-group" style="justify-content:flex-end">
      <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
        <input type="checkbox" name="weight_instock[${wIdx++}]" checked> In Stock
      </label>
    </div>`;
  c.appendChild(d);
}

/* ── FLAVOUR ROW ── */
function addFlavour() {
  const c = document.getElementById('flavours-container');
  const d = document.createElement('div');
  d.style.cssText = 'display:flex;gap:10px;align-items:center;margin-bottom:8px';
  d.innerHTML = `
    <input type="text" name="flavour_name[]" placeholder="Flavour name"
      style="background:var(--surface);border:1px solid var(--border);color:var(--text);
             padding:8px 12px;border-radius:7px;font-size:13px;flex:1">
    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;white-space:nowrap">
      <input type="checkbox" name="flavour_instock[${fIdx++}]" checked> In Stock
    </label>`;
  c.appendChild(d);
}
</script>

<?php adminClose(); ?>