<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - thriftIN</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- ✅ Added Chart.js -->
    <style>
        * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter','Arial',sans-serif;
      background: linear-gradient(135deg,#0a0a0a 0%,#1a1a1a 50%,#0f0f0f 100%);
      color: #fff;
      min-height: 100vh;
      overflow-x: hidden;
    }
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: radial-gradient(circle at 20% 50%,rgba(120,119,198,0.1) 0%,transparent 50%),
                  radial-gradient(circle at 80% 20%,rgba(255,119,198,0.1) 0%,transparent 50%),
                  radial-gradient(circle at 40% 80%,rgba(120,255,198,0.1) 0%,transparent 50%);
      z-index: -1;
    }
    /* Header */
    .header {
      position: sticky;
      top: 0;
      background: rgba(0,0,0,0.9);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255,255,255,0.1);
      padding: 15px 0;
      z-index: 1000;
      box-shadow: 0 6px 24px rgba(0,0,0,0.4);
    }
    .nav-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo-section {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .logo {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      background: linear-gradient(45deg,#667eea,#764ba2);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 20px rgba(102,126,234,0.4);
    }
    .logo::before {
      content: "📊";
      font-size: 22px;
    }
    .brand-text {
      font-size: 24px;
      font-weight: 700;
      background: linear-gradient(135deg,#667eea,#764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .admin-text {
      font-size: 14px;
      color: #aaa;
      margin-left: 8px;
    }
    .logout-btn {
      background: linear-gradient(135deg,#ff6b6b,#ee5a24);
      color: #fff;
      border: none;
      padding: 10px 22px;
      border-radius: 50px;
      font-weight: 600;
      cursor: pointer;
      transition: all .3s ease;
      box-shadow: 0 4px 15px rgba(255,107,107,.3);
    }
    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255,107,107,.4);
    }
    /* Layout */
    .layout {
      max-width: 1400px;
      margin: 30px auto;
      padding: 0 30px;
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 25px;
    }
    /* Sidebar */
    .sidebar {
      background: rgba(255,255,255,0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 18px;
      padding: 25px 15px;
      position: sticky;
      top: 100px;
      height: fit-content;
      box-shadow: 0 6px 24px rgba(0,0,0,0.4);
    }
    .menu {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .menu a {
      display: block;
      padding: 14px 18px;
      border-radius: 14px;
      color: #bbb;
      text-decoration: none;
      font-weight: 500;
      border: 1px solid transparent;
      transition: all .3s ease;
      position: relative;
      overflow: hidden;
    }
    .menu a::before {
      content: "";
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);
      transition: left .5s;
    }
    .menu a:hover::before {
      left: 100%;
    }
    .menu a:hover,
    .menu a.active {
      background: linear-gradient(135deg,rgba(102,126,234,.2),rgba(118,75,162,.2));
      border-color: rgba(102,126,234,.3);
      color: #fff;
      transform: translateX(5px);
      box-shadow: 0 4px 18px rgba(102,126,234,.25);
    }
    /* Content */
    .content {
      background: rgba(255,255,255,0.03);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 18px;
      padding: 35px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.4);
    }
    .section { display: none; }
    .section.active { display: block; animation: fadeIn .5s ease; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .section h2 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 25px;
      background: linear-gradient(135deg,#667eea,#764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    /* Toolbar */
    .toolbar {
      display: flex;
      gap: 12px;
      margin-bottom: 25px;
      flex-wrap: wrap;
      align-items: center;
    }
    input[type=text] {
      padding: 12px 16px;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,.2);
      background: rgba(0,0,0,.4);
      color: #fff;
      font-size: 14px;
      min-width: 220px;
      transition: all .3s ease;
    }
    input[type=text]:focus {
      outline: none;
      border-color: rgba(102,126,234,.5);
      box-shadow: 0 0 15px rgba(102,126,234,.2);
      transform: translateY(-1px);
    }
    button {
      padding: 12px 20px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg,#667eea,#764ba2);
      color: #fff;
      font-weight: 600;
      cursor: pointer;
      font-size: 14px;
      transition: all .3s ease;
      box-shadow: 0 4px 12px rgba(102,126,234,.3);
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(102,126,234,.4);
    }
    /* Tables */
    .grid {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255,255,255,.02);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 4px 18px rgba(0,0,0,.3);
    }
    .grid th, .grid td {
      padding: 16px 18px;
      border-bottom: 1px solid rgba(255,255,255,.08);
      text-align: left;
    }
    .grid thead { background: rgba(102,126,234,.1); }
    .grid thead th {
      font-size: 12px;
      text-transform: uppercase;
      color: #bbb;
      letter-spacing: .05em;
    }
    .grid tbody tr:hover {
      background: rgba(255,255,255,.05);
      transform: scale(1.01);
    }
    /* Badges */
    .badge {
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      border: 1px solid;
    }
    .badge.pending { color:#ffd93d; border-color:rgba(255,217,61,.3); background:rgba(255,217,61,.1);}
    .badge.approved { color:#6BCF7F; border-color:rgba(107,207,127,.3); background:rgba(107,207,127,.1);}
    .badge.rejected { color:#ff6b6b; border-color:rgba(255,107,107,.3); background:rgba(255,107,107,.1);}
    /* Charts & Stats */
    .stats-container { display:flex; flex-wrap:wrap; gap:18px; margin-bottom:25px; }
    .stat-card {
      flex:1 1 220px;
      background: rgba(255,255,255,.05);
      border:1px solid rgba(255,255,255,.1);
      border-radius:14px;
      padding:18px;
      text-align:center;
    }
    .stat-card h3 { font-size:14px; color:#bbb; margin-bottom:6px; }
    .stat-card p { font-size:24px; font-weight:700; }
    .chart-container {
      background: rgba(255,255,255,.03);
      border:1px solid rgba(255,255,255,.1);
      border-radius:18px;
      padding:25px;
      margin-bottom:30px;
    }
    .chart-container h3 {
      font-size:20px;
      margin-bottom:15px;
      background: linear-gradient(135deg,#667eea,#764ba2);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
    }
    /* Responsive */
    @media(max-width:1200px){
      .layout{ grid-template-columns:1fr; }
      .sidebar{ position:static; order:-1; }
      .menu{ flex-direction:row; overflow-x:auto; }
      .menu a{ min-width:140px; text-align:center; }
    }
    @media(max-width:768px){
      .layout{ padding:0 20px; }
      .content{ padding:25px 18px; }
      .brand-text{ font-size:20px; }
      .admin-text{ display:none; }
      .toolbar{ flex-direction:column; align-items:stretch; }
      input[type=text]{ min-width:100%; }
    }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="logo-section">
                <div class="logo"></div>
                <div>
                    <span class="brand-text">thriftIN</span>
                    <span class="admin-text">Admin Panel</span>
                </div>
            </div>
            <button class="logout-btn" onclick="logout()">
                <span></span> Logout
            </button>
        </div>
    </header>

    <main class="layout">
        <aside class="sidebar">
            <ul class="menu">
                <li><a href="#product-requests" class="active" data-target="product-requests">📋 Product Requests</a></li>
                <li><a href="#products" data-target="products">📦 Manage Products</a></li>
                <li><a href="#sellers" data-target="sellers">👥 Manage Sellers</a></li>
                <li><a href="#users" data-target="users">👤 Manage Users</a></li>
                <li><a href="#site" data-target="site">⚙️ Site Settings</a></li>
            </ul>
        </aside>
        
        <section class="content">
            <!-- 📊 Analytics Chart -->
            <div class="chart-container" id="analytics-section">
            <h3>📊 Product Status Overview</h3>
            <canvas id="productChart" width="200" height="100" style="max-width:300px; margin:auto;"></canvas>
            </div>
            <div id="product-requests" class="section active">
                <h2>Product Upload Requests</h2>
                <div class="toolbar">
                    <input type="text" id="reqSearch" placeholder="🔍 Search by title or seller">
                    <button onclick="loadProductRequests()">🔄 Refresh</button>
                </div>
                <table class="grid" id="requestsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" class="loading">Loading product requests...</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="products" class="section">
                <h2>All Products</h2>
                    <div class="stats-container">
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <p id="productCount">0</p>
                    </div>
                    </div>
                <div class="toolbar">
                    <input type="text" id="prodSearch" placeholder="🔍 Search products">
                    <button onclick="loadAllProducts()">🔄 Refresh</button>
                </div>
                <table class="grid" id="productsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" class="loading">Loading products...</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="sellers" class="section">
                <h2>Sellers</h2>
                <div class="stats-container">
  <div class="stat-card">
    <h3>Total Sellers</h3>
    <p id="sellerCount">0</p>
  </div>
</div>
                <div class="toolbar">
                    <button onclick="loadSellers()">🔄 Refresh</button>
                </div>
                <table class="grid" id="sellersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" class="loading">Loading sellers...</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="users" class="section">
                <h2>Users</h2>
                <div class="stats-container">
  <div class="stat-card">
    <h3>Total Users</h3>
    <p id="userCount">0</p>
  </div>
</div>
                <div class="toolbar">
                    <button onclick="loadUsers()">🔄 Refresh</button>
                </div>
                <table class="grid" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="3" class="loading">Loading users...</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="site" class="section">
                <h2>Website Features</h2>
                <p style="color:#888; margin-bottom: 30px;">Configure website features and settings</p>
                <div class="toolbar">
                    <label>
                        <input type="checkbox" id="featureHero" checked>
                        Enable Hero Video
                    </label>
                    <label>
                        <input type="checkbox" id="featureParticles" checked>
                        Enable Particles
                    </label>
                </div>
                <button onclick="saveSiteSettings()">💾 Save Settings</button>
            </div>
        </section>
    </main>

    <script>
        // Logout functionality
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                // Clear any session data
                fetch('logout.php', { method: 'POST' })
                    .then(() => {
                        window.location.href = 'login_page.html';
                    })
                    .catch(() => {
                        // Fallback - redirect anyway
                        window.location.href = 'login_page.html';
                    });
            }
        }

        // Sidebar navigation
        document.querySelectorAll('.menu a').forEach(a => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.menu a').forEach(x => x.classList.remove('active'));
            a.classList.add('active');
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            const target = document.getElementById(a.dataset.target);
            if (target) target.classList.add('active');

            // Show or hide the Product Status Overview chart based on active tab
           const chartContainer = document.getElementById('analytics-section');
            if (chartContainer) {
            if (['product-requests', 'products'].includes(a.dataset.target)) {
                chartContainer.style.display = 'block';
            } else {
                chartContainer.style.display = 'none';
            }
            }

            // Load corresponding data
            switch (a.dataset.target) {
            case 'product-requests': loadProductRequests(); break;
            case 'products': loadAllProducts(); break;
            case 'sellers': loadSellers(); break;
            case 'users': loadUsers(); break;
            }
        });
        });

        function loadProductRequests() {
            const tbody = document.querySelector('#requestsTable tbody');
            tbody.innerHTML = '<tr><td colspan="7" class="loading">Loading product requests...</td></tr>';
            
            fetch('get_products_admin.php')
                .then(r => r.json())
                .then(rows => {
                    const query = (document.getElementById('reqSearch').value || '').toLowerCase();
                    const filtered = rows.filter(x => !query || `${x.name} ${x.seller}`.toLowerCase().includes(query));
                    
                    tbody.innerHTML = filtered
                        .filter(x => x.status === 'pending')
                        .map(x => `
                            <tr>
                                <td>${x.id}</td>
                                <td>${x.name}</td>
                                <td>${x.seller}</td>
                                <td>${x.category}</td>
                                <td>₹${x.price}</td>
                                <td><span class="badge ${x.status}">${x.status}</span></td>
                                <td>
                                    <button class="action-btn approve-btn" onclick="setProductStatus(${x.id}, 'approved')">✅ Approve</button>
                                    <button class="action-btn reject-btn" onclick="setProductStatus(${x.id}, 'rejected')">❌ Reject</button>
                                </td>
                            </tr>
                        `).join('') || '<tr><td colspan="7" style="text-align: center; color: #888;">No pending requests found</td></tr>';
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #ff6b6b;">❌ Failed to load requests</td></tr>';
                });
        }

        function loadAllProducts() {
    const tbody = document.querySelector('#productsTable tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="loading">Loading products...</td></tr>';
    
    fetch('get_products_admin.php')
        .then(r => r.json())
        .then(rows => {
            document.getElementById('productCount').textContent = rows.length;
            updateProductChart(rows); // Update the chart with new data

            const query = (document.getElementById('prodSearch').value || '').toLowerCase();
            const filtered = rows.filter(x => !query || `${x.name} ${x.seller}`.toLowerCase().includes(query));
            
            tbody.innerHTML = filtered.map(x => `
                <tr>
                    <td>${x.id}</td>
                    <td>${x.name}</td>
                    <td>${x.seller}</td>
                    <td>${x.category}</td>
                    <td>₹${x.price}</td>
                    <td><span class="badge ${x.status}">${x.status}</span></td>
                    <td>
                        <button class="action-btn delete-btn" onclick="deleteProduct(${x.id})">🗑️ Delete</button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="7" style="text-align: center; color: #888;">No products found</td></tr>';
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #ff6b6b;">❌ Failed to load products</td></tr>';
        });
}

        function loadSellers() {
            const tbody = document.querySelector('#sellersTable tbody');
            tbody.innerHTML = '<tr><td colspan="4" class="loading">Loading sellers...</td></tr>';
            
            fetch('get_sellers.php')
                .then(r => r.json())
                .then(rows => {
                    document.getElementById('sellerCount').textContent = rows.length;
                    tbody.innerHTML = rows.map(s => `
                        <tr>
                            <td>${s.id}</td>
                            <td>${s.name || 'N/A'}</td>
                            <td>${s.email || 'N/A'}</td>
                            <td>
                                <a href="edit_seller.php?id=${s.id}" style="color:#667eea; text-decoration:none; margin-right: 15px;">✏️ Edit</a>
                                <a href="delete_seller.php?id=${s.id}" style="color:#ff6b6b; text-decoration:none;" onclick="return confirm('Delete this seller?')">🗑️ Delete</a>
                            </td>
                        </tr>
                    `).join('') || '<tr><td colspan="4" style="text-align: center; color: #888;">No sellers found</td></tr>';
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #ff6b6b;">❌ Failed to load sellers</td></tr>';
                });
        }

        function loadUsers() {
            const tbody = document.querySelector('#usersTable tbody');
            tbody.innerHTML = '<tr><td colspan="3" class="loading">Loading users...</td></tr>';
            
            fetch('get_users.php')
                .then(r => r.json())
                .then(rows => {
                    document.getElementById('userCount').textContent = rows.length;
                    tbody.innerHTML = rows.map(u => `
                        <tr>
                            <td>${u.id}</td>
                            <td>${u.name || 'N/A'}</td>
                            <td>${u.email || 'N/A'}</td>
                        </tr>
                    `).join('') || '<tr><td colspan="3" style="text-align: center; color: #888;">No users found</td></tr>';
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: #ff6b6b;">❌ Failed to load users</td></tr>';
                });
        }
        let productChartInstance = null;

            function updateProductChart(rows) {
            const statusCounts = { approved: 0, pending: 0, rejected: 0 };
            rows.forEach(p => {
                if (p.status) statusCounts[p.status] = (statusCounts[p.status] || 0) + 1;
            });

            const ctx = document.getElementById('productChart').getContext('2d');
            const data = {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                label: 'Product Status',
                data: [statusCounts.approved, statusCounts.pending, statusCounts.rejected],
                backgroundColor: ['#6BCF7F', '#FFD93D', '#FF6B6B'],
                borderWidth: 1
                }]
            };

            if (productChartInstance) {
                productChartInstance.data = data;
                productChartInstance.update();
            } else {
                productChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data,
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                });
            }
            }

        function setProductStatus(id, status) {
            const form = new FormData();
            form.append('id', id);
            form.append('status', status);
            
            fetch('update_product_status.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(response => {
                    if (response.success || response) {
                        alert(`✅ Product ${status} successfully!`);
                        loadProductRequests();
                        loadAllProducts();
                    } else {
                        alert('❌ Failed to update status');
                    }
                })
                .catch(() => alert('❌ Failed to update status'));
        }

        function deleteProduct(id) {
            if (confirm('⚠️ Are you sure you want to delete this product? This action cannot be undone.')) {
                const form = new FormData();
                form.append('id', id);
                
                fetch('delete_product.php', { method: 'POST', body: form })
                    .then(r => r.json())
                    .then(response => {
                        if (response.success) {
                            alert('✅ Product deleted successfully!');
                            loadAllProducts();
                        } else {
                            alert('❌ Failed to delete product: ' + (response.error || 'Unknown error'));
                        }
                    })
                    .catch(() => alert('❌ Failed to delete product'));
            }
        }

        function saveSiteSettings() {
            const settings = {
                featureHero: document.getElementById('featureHero').checked,
                featureParticles: document.getElementById('featureParticles').checked
            };
            
            // Save to localStorage for demo (replace with server-side call in production)
            localStorage.setItem('thriftin_site_settings', JSON.stringify(settings));
            alert('💾 Settings saved successfully!');
        }

        // Add search functionality with debouncing
        let searchTimeout;
        
        function debounceSearch(func, delay) {
            return function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(func, delay);
            };
        }
        
        document.getElementById('reqSearch').addEventListener('input', 
            debounceSearch(loadProductRequests, 300));
        document.getElementById('prodSearch').addEventListener('input', 
            debounceSearch(loadAllProducts, 300));

        // Load site settings on page load
        window.addEventListener('load', () => {
            const savedSettings = localStorage.getItem('thriftin_site_settings');
            if (savedSettings) {
                const settings = JSON.parse(savedSettings);
                document.getElementById('featureHero').checked = settings.featureHero;
                document.getElementById('featureParticles').checked = settings.featureParticles;
            }
        });

        // Initial load
        loadProductRequests();
    </script>
</body>
</html>