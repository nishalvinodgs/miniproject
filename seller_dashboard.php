<?php
session_start();// Check if the seller is logged in
if (!isset($_SESSION['seller'])) {
    header("Location: login_page.html");
    exit;
}


// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login_page.html");
    exit;
}
?>
<!DOCTYPE html><?php
if (!isset($_SESSION['seller_id'])) {
    header("Location: login_page.html");
    exit;
}

require_once 'db_connect.php'; // Include your database connection file

// Fetch seller's email from session
$seller_email = $_SESSION['seller'];

// Fetch seller's ID from the database using their email
$stmt = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
$stmt->bind_param("s", $seller_email);
$stmt->execute();
$result = $stmt->get_result();
$seller_data = $result->fetch_assoc();
$seller_id = $seller_data['id'];

$_SESSION['seller_id'] = $seller_id; // Ensure seller_id is in session
$_SESSION['seller_email'] = $seller_email; // Ensure seller_email is in session
?>

<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Dashboard - thriftIN</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Arial', sans-serif; background: #0d0d0f; color: #fff; overflow-x: hidden; }

    /* HEADER */
    .header { display:flex; justify-content:space-between; align-items:center; padding:14px 24px; background:rgba(0,0,0,0.9); backdrop-filter: blur(12px); border-bottom:1px solid rgba(255,255,255,0.08); position:sticky; top:0; z-index:100; }
    .logo { display:flex; align-items:center; gap:10px; }
    .logo img { height:40px; }
    .logo strong { font-size:20px; font-weight:700; background:linear-gradient(45deg,#a78bfa,#60a5fa); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }

    /* LOGOUT BUTTON */
    .logout-btn { padding:10px 18px; border:none; border-radius:999px; background:linear-gradient(135deg,#f87171,#fb923c); color:#fff; font-weight:600; cursor:pointer; transition:.2s; }
    .logout-btn:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(251,146,60,0.3); }

    /* LAYOUT */
    .layout { display:grid; grid-template-columns:260px 1fr; gap:20px; padding:24px; max-width:1400px; margin:0 auto; }

    /* SIDEBAR */
    .sidebar { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:20px; backdrop-filter:blur(10px); }
    .menu { list-style:none; display:flex; flex-direction:column; gap:8px; }
    .menu a { display:flex; align-items:center; gap:10px; padding:12px 14px; color:#aaa; text-decoration:none; border-radius:12px; transition:.2s; }
    .menu a.active, .menu a:hover { background:rgba(99,102,241,0.1); color:#fff; border:1px solid rgba(99,102,241,0.3); }

    .muted { font-size:13px; color:#aaa; margin-top:20px; }

    /* CONTENT */
    .content { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:24px; backdrop-filter:blur(8px); }
    .section { display:none; }
    .section.active { display:block; animation:fadeIn .3s ease; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(6px);} to { opacity:1; transform:translateY(0);} }

    /* CARDS */
    .cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }
    .card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12); border-radius:14px; padding:16px; transition:.2s; }
    .card:hover { transform:translateY(-4px); box-shadow:0 12px 28px rgba(0,0,0,0.4); }

    .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; border:1px solid #333; }
    .badge.pending { color:#facc15; border-color:#facc15; }
    .badge.approved { color:#4ade80; border-color:#4ade80; }
    .badge.rejected { color:#f87171; border-color:#f87171; }

    /* TABLE */
    .grid { width:100%; border-collapse:collapse; margin-top:12px; }
    .grid th, .grid td { padding:12px; border-bottom:1px solid rgba(255,255,255,0.12); text-align:left; }
    .grid thead th { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#bbb; }

    /* FORM */
    form { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:14px; }
    form .full { grid-column:1 / -1; }
    input, select, textarea { width:100%; padding:12px; border-radius:12px; border:1px solid rgba(255,255,255,0.15); background:rgba(0,0,0,0.6); color:#fff; }
    button[type="submit"] { padding:12px 16px; border:none; border-radius:999px; background:linear-gradient(135deg,#818cf8,#a78bfa); color:#fff; font-weight:700; cursor:pointer; transition:.2s; }
    button[type="submit"]:hover { transform:translateY(-1px); box-shadow:0 8px 20px rgba(167,139,250,0.3); }

    /* Responsive */
    @media(max-width:900px) {
      .layout { grid-template-columns:1fr; }
      .sidebar { position:static; }
      form { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="logo">
      <img src="logo1.png" alt="logo">
      <strong>thriftIN Seller</strong>
    </div>
    <a href="?logout=true"><button class="logout-btn">Logout</button></a>
  </header>

  <main class="layout">
    <aside class="sidebar">
      <ul class="menu">
        <li><a href="#upload" data-target="upload" class="active">📤 Upload Product</a></li>
        <li><a href="#my-products" data-target="my-products">📦 My Products</a></li>
        <li><a href="#orders" data-target="orders">🛒 Orders</a></li>
      </ul>
      <p class="muted">Logged in as seller: 
  <?php echo isset($_SESSION['seller_email']) ? htmlspecialchars($_SESSION['seller_email']) : 'guest'; ?>
</p>
    </aside>

    <section class="content">
      <!-- Upload Section -->
      <div id="upload" class="section active">
        <h2>Upload Product</h2>
        <p class="muted">Submitted products appear as requests to admin until approved.</p>
        <form id="uploadForm" action="upload_product.php" method="POST" enctype="multipart/form-data">
          <div><label>Title</label><input type="text" name="title" required></div>
          <div><label>Category</label>
            <select name="category" required>
              <option value="bags">Bags</option>
              <option value="watches">Watches</option>
              <option value="shoes">Shoes</option>
              <option value="gaming">Gaming</option>
              <option value="gadgets">Gadgets</option>
            </select>
          </div>
          <div><label>Price (₹)</label><input type="number" step="0.01" name="price" required></div>
          <div><label>Original Price (₹) - Optional</label><input type="number" step="0.01" name="original_price"></div>
          <div><label>Condition</label>
            <select name="condition" required>
              <option value="new">new</option>
              <option value="like-new">like-new</option>
              <option value="good">good</option>
              <option value="fair">fair</option>
            </select>
          </div>
          <div class="full"><label>Description</label><textarea name="description" rows="4" placeholder="Brief description"></textarea></div>
          <div class="full"><label>Image</label><input type="file" name="image" accept="image/*" required></div>
          <div class="full"><button type="submit">Submit for Review</button></div>
        </form>
      </div>

      <!-- My Products Section -->
      <div id="my-products" class="section">
        <h2>My Products</h2>
        <div id="myProducts" class="cards"></div>
      </div>

      <!-- Orders Section -->
      <div id="orders" class="section">
        <h2>Orders</h2>
        <table class="grid" id="ordersTable">
          <thead>
            <tr><th>Order #</th><th>Product</th><th>Price</th><th>Qty</th><th>Status</th><th>Buyer</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    // Sidebar switching
    document.querySelectorAll('.menu a').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.menu a').forEach(a => a.classList.remove('active'));
        link.classList.add('active');
        const target = link.dataset.target;
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById(target).classList.add('active');
        if (target === 'my-products') loadMyProducts();
        if (target === 'orders') loadOrders();
      });
    });

    function loadMyProducts() {
      fetch('get_seller_products.php')
        .then(r => r.json())
        .then(rows => {
          const container = document.getElementById('myProducts');
          if (!Array.isArray(rows) || rows.length === 0) {
            container.innerHTML = '<p class="muted">No products yet.</p>';
            return;
          }
          container.innerHTML = rows.map(p => `
            <div class="card">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <strong>${p.title}</strong>
                <span class="badge ${p.approval_status}">${p.approval_status}</span>
              </div>
              <div class="muted">Category: ${p.category}</div>
              <div style="margin-top:8px;font-weight:700;">₹${Number(p.price).toLocaleString()}</div>
            </div>
          `).join('');
        })
        .catch(() => {
          document.getElementById('myProducts').innerHTML = '<p class="muted">Failed to load products.</p>';
        });
    }

    function loadOrders() {
      fetch('get_seller_orders.php')
        .then(r => r.json())
        .then(rows => {
          const tbody = document.querySelector('#ordersTable tbody');
          if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="muted">No orders yet.</td></tr>';
            return;
          }
          tbody.innerHTML = rows.map(o => `
            <tr>
              <td>${o.order_id}</td>
              <td>${o.product_name}</td>
              <td>₹${Number(o.price).toLocaleString()}</td>
              <td>${o.quantity}</td>
              <td>${o.order_status}</td>
              <td>${o.buyer_email}</td>
            </tr>
          `).join('');
        })
        .catch(() => {
          document.querySelector('#ordersTable tbody').innerHTML = '<tr><td colspan="6" class="muted">Failed to load orders.</td></tr>';
        });
    }

    // Initial hash-based loading
    if (location.hash === '#my-products') document.querySelector('[data-target="my-products"]').click();
    if (location.hash === '#orders') document.querySelector('[data-target="orders"]').click();

    // AJAX upload
    const uploadForm = document.getElementById('uploadForm');
    uploadForm.addEventListener('submit', async e => {
      e.preventDefault();
      const btn = uploadForm.querySelector('button[type="submit"]');
      btn.disabled = true; btn.textContent = 'Uploading...';
      try {
        const formData = new FormData(uploadForm);
        const res = await fetch('upload_product.php', { method: 'POST', body: formData });
        if (res.ok) {
          alert('Product submitted for review');
          uploadForm.reset();
          document.querySelector('[data-target="my-products"]').click();
        } else {
          const text = await res.text();
          alert('Upload failed: ' + text);
        }
      } catch (err) {
        alert('Network error uploading product');
      } finally {
        btn.disabled = false; btn.textContent = 'Submit for Review';
      }
    });
  </script>
</body>
</html>