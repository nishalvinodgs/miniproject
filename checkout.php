<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - thriftIN</title>
    <style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial, sans-serif;background:#000;color:#fff}
.wrap{max-width:1000px;margin:100px auto 40px;padding:0 20px;display:grid;grid-template-columns:2fr 1fr;gap:20px}
.card{background:#0e0e0e;border:1px solid rgba(255,255,255,0.15);border-radius:12px;padding:16px}
label{display:block;margin:10px 0 6px}
input,textarea,select{width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.2);background:#111;color:#fff}
.summary-row{display:flex;justify-content:space-between;margin:6px 0}
.pay-btn{width:100%;padding:12px 16px;border:none;border-radius:999px;background:linear-gradient(45deg,#fff,#ccc);color:#000;font-weight:800;cursor:pointer;margin-top:12px}
.pay-btn:disabled{background:#333;color:#666;cursor:not-allowed}
.muted{color:#aaa;font-size:12px}
.loading{display:none;text-align:center;padding:10px}
@media(max-width:900px){.wrap{grid-template-columns:1fr}}
    </style>
    <script src="https://checkout.razorpay.com/v1/checkout.js" async></script>
</head>
<body>
    <div class="wrap">
        <section class="card">
            <h2>Delivery Address</h2>
            <form id="addressForm">
                <label>Full Name</label>
                <input type="text" id="fullName" required>
                <label>Phone</label>
                <input type="tel" id="phone" required>
                <label>Address Line 1</label>
                <input type="text" id="addr1" required>
                <label>Address Line 2</label>
                <input type="text" id="addr2">
                <label>City</label>
                <input type="text" id="city" required>
                <label>State</label>
                <input type="text" id="state" required>
                <label>Pincode</label>
                <input type="text" id="pincode" required>
                <p class="muted">Payment via Razorpay will open after clicking Place Order.</p>
            </form>
        </section>
        <aside class="card">
            <h2>Order Summary</h2>
            <div id="summaryItems" class="muted" style="margin:8px 0"></div>
            <div class="summary-row"><span>Items</span><strong id="sumCount">0</strong></div>
            <div class="summary-row"><span>Subtotal</span><strong id="sumSubtotal">₹0</strong></div>
            <div class="loading" id="loadingMsg">Processing payment...</div>
            <button id="placeOrder" class="pay-btn">Place Order</button>
        </aside>
    </div>

    <script>
// Load cart
const items = JSON.parse(localStorage.getItem('cart_items') || '[]');
const count = items.reduce((s,i)=>s+i.quantity,0);
const subtotal = items.reduce((s,i)=>s+i.quantity*i.price,0);
document.getElementById('sumCount').textContent = count;
document.getElementById('sumSubtotal').textContent = `₹${subtotal.toLocaleString()}`;
document.getElementById('summaryItems').innerHTML = items.map(i=>`${i.title} × ${i.quantity}`).join('<br>');

function getAddress(){
  return {
    fullName: document.getElementById('fullName').value.trim(),
    phone: document.getElementById('phone').value.trim(),
    addr1: document.getElementById('addr1').value.trim(),
    addr2: document.getElementById('addr2').value.trim(),
    city: document.getElementById('city').value.trim(),
    state: document.getElementById('state').value.trim(),
    pincode: document.getElementById('pincode').value.trim()
  };
}

function validateAddress(addr){
  return addr.fullName && addr.phone && addr.addr1 && addr.city && addr.state && addr.pincode;
}

function showLoading(show) {
  const loading = document.getElementById('loadingMsg');
  const button = document.getElementById('placeOrder');
  if (show) {
    loading.style.display = 'block';
    button.disabled = true;
    button.textContent = 'Processing...';
  } else {
    loading.style.display = 'none';
    button.disabled = false;
    button.textContent = 'Place Order';
  }
}

async function createRazorpayOrder(amountPaise){
  const res = await fetch('create_razorpay_order.php', {
    method: 'POST', 
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ amount: amountPaise, currency: 'INR' })
  });
  
  if (!res.ok) {
    throw new Error(`HTTP error! status: ${res.status}`);
  }
  
  const contentType = res.headers.get('content-type');
  if (!contentType || !contentType.includes('application/json')) {
    const text = await res.text();
    console.error('Non-JSON response:', text);
    throw new Error('Server returned non-JSON response');
  }
  
  return res.json();
}

async function persistOrder(address, rzpPayload){
    const res = await fetch('create_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
      address, 
      items, 
      total: subtotal, 
      razorpay: rzpPayload 
    })
  });
  
  if (!res.ok) {
    throw new Error(`HTTP error! status: ${res.status}`);
  }
  
  const contentType = res.headers.get('content-type');
  if (!contentType || !contentType.includes('application/json')) {
    const text = await res.text();
    console.error('Non-JSON response:', text);
    throw new Error('Server returned non-JSON response');
  }
  
  return res.json();
}

function openRazorpay(order, keyId, address){
  return new Promise((resolve, reject) => {
    const options = {
      key: keyId,
      amount: order.amount,
      currency: order.currency,
      name: 'thriftIN',
      description: 'Order Payment',
      order_id: order.id,
      prefill: {
        name: address.fullName,
        contact: address.phone
      },
      notes: { 
        address: `${address.addr1} ${address.addr2}, ${address.city}, ${address.state} - ${address.pincode}` 
      },
      theme: { color: '#111111' },
      handler: function (response) {
        console.log('Payment successful:', response);
        resolve(response);
      },
      modal: { 
        ondismiss: function(){ 
          console.log('Payment dismissed');
          reject(new Error('Payment cancelled by user')); 
        } 
      }
    };
    
    const rzp = new window.Razorpay(options);
    rzp.on('payment.failed', function (response){
      console.log('Payment failed:', response);
      reject(new Error('Payment failed: ' + response.error.description));
    });
    
    rzp.open();
  });
}

document.getElementById('placeOrder').addEventListener('click', async (e) => {
  e.preventDefault();
  
  const address = getAddress();
  if (!validateAddress(address)) { 
    alert('Please fill all required address fields.'); 
    return; 
  }
  
  if (!items.length) { 
    alert('Your cart is empty.'); 
    return; 
  }

  showLoading(true);

  try {
    // 1) Create Razorpay order on server
    const amountPaise = Math.round(subtotal * 100);
    console.log('Creating Razorpay order for amount:', amountPaise);
    
    const orderResp = await createRazorpayOrder(amountPaise);
    console.log('Order response:', orderResp);
    
    if (!orderResp.ok) { 
      throw new Error('Failed to initiate payment: ' + (orderResp.error || 'Unknown error'));
    }

    // 2) Open Razorpay Checkout
    console.log('Opening Razorpay checkout...');
    const rzpResponse = await openRazorpay(orderResp.order, orderResp.key_id, address);
    console.log('Razorpay response:', rzpResponse);

    // 3) Persist order in DB with payment info
    console.log('Saving order to database...');
    const saveResp = await persistOrder(address, {
      razorpay_payment_id: rzpResponse.razorpay_payment_id,
      razorpay_order_id: rzpResponse.razorpay_order_id,
      razorpay_signature: rzpResponse.razorpay_signature
    });
    
    console.log('Save response:', saveResp);

    if (saveResp.ok) {
      // Clear cart and redirect to success page
      localStorage.setItem('last_order_address', JSON.stringify(address));
      localStorage.setItem('cart_items', '[]');
      window.location.href = 'order_success.php?order_id=' + saveResp.order_id;
    } else {
      throw new Error('Order save failed: ' + (saveResp.error || 'Unknown error'));
    }
    
  } catch (error) {
    console.error('Payment error:', error);
    alert(error.message || 'Payment failed. Please try again.');
  } finally {
    showLoading(false);
  }
});

// Load saved address if available
window.addEventListener('load', () => {
  const savedAddress = localStorage.getItem('last_order_address');
  if (savedAddress) {
    try {
      const addr = JSON.parse(savedAddress);
      if (addr.fullName) document.getElementById('fullName').value = addr.fullName;
      if (addr.phone) document.getElementById('phone').value = addr.phone;
      if (addr.addr1) document.getElementById('addr1').value = addr.addr1;
      if (addr.addr2) document.getElementById('addr2').value = addr.addr2;
      if (addr.city) document.getElementById('city').value = addr.city;
      if (addr.state) document.getElementById('state').value = addr.state;
      if (addr.pincode) document.getElementById('pincode').value = addr.pincode;
    } catch (e) {
      console.log('Could not load saved address');
    }
  }
});
    </script>
</body>
</html>
