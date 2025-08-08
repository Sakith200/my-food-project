// Show and hide sections
function showSection(id) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(id).classList.add('active');

    document.querySelectorAll('.nav-links a').forEach(link => {
        link.classList.remove('active');
    });
    const activeLink = [...document.querySelectorAll('.nav-links a')].find(link =>
        link.getAttribute('onclick')?.includes(id)
    );
    if (activeLink) activeLink.classList.add('active');
}

// Update cart count and total
function updateCartDisplay() {
    fetch('cart_status.php') // you can make this call dynamic or skip if not using
        .then(res => res.json())
        .then(data => {
            document.getElementById('cart-count').textContent = data.count;
            document.getElementById('cart-total').textContent = data.total.toFixed(2);
        });
}

// Add item to cart
function addToCart(itemId) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=add_to_cart&item_id=${itemId}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        updateCartDisplay();
    });
}

// Show checkout form if cart not empty
function showCheckout() {
    document.getElementById('checkout-form').style.display = 'block';
}

// Handle order form submit
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('order-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const data = {
                action: 'place_order',
                customer_name: document.getElementById('customer-name').value,
                customer_phone: document.getElementById('customer-phone').value,
                customer_email: document.getElementById('customer-email').value,
                delivery_address: document.getElementById('delivery-address').value,
                payment_method: document.getElementById('payment-method').value,
                special_instructions: document.getElementById('special-instructions').value
            };

            const params = new URLSearchParams(data).toString();

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert('Order placed successfully!');
                    location.reload();
                } else {
                    alert('Something went wrong. Please try again.');
                }
            });
        });
    }
});

// Fetch and show orders
function fetchOrders() {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_orders'
    })
    .then(res => res.json())
    .then(orders => {
        const ordersList = document.getElementById('orders-list');
        const adminTable = document.getElementById('admin-orders-table');

        if (!orders.length) {
            ordersList.innerHTML = '<p>No orders yet.</p>';
            adminTable.innerHTML = '';
            return;
        }

        ordersList.innerHTML = '';
        adminTable.innerHTML = '';

        let totalRevenue = 0;
        let pendingCount = 0;

        orders.forEach(order => {
            totalRevenue += parseFloat(order.total);
            if (order.status === 'pending') pendingCount++;

            // User view
            const div = document.createElement('div');
            div.classList.add('order-card');
            div.innerHTML = `
                <h4>Order #${order.id}</h4>
                <p><strong>Name:</strong> ${order.customer_name}</p>
                <p><strong>Total:</strong> LKR ${order.total}</p>
                <p><strong>Status:</strong> ${order.status}</p>
                <p><strong>Placed:</strong> ${order.created_at}</p>
            `;
            ordersList.appendChild(div);

            // Admin table
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${order.id}</td>
                <td>${order.customer_name}</td>
                <td>${order.customer_phone}</td>
                <td>LKR ${order.total}</td>
                <td>${order.status}</td>
                <td>${order.created_at}</td>
                <td>
                    <select onchange="updateOrderStatus(${order.id}, this.value)">
                        <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Processing</option>
                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Completed</option>
                    </select>
                </td>
            `;
            adminTable.appendChild(row);
        });

        document.getElementById('total-orders').textContent = orders.length;
        document.getElementById('pending-orders').textContent = pendingCount;
        document.getElementById('total-revenue').textContent = 'LKR ' + totalRevenue.toFixed(2);
    });
}

// Update order status (admin)
function updateOrderStatus(orderId, status) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_order_status&order_id=${orderId}&status=${status}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            alert('Order status updated');
            fetchOrders();
        }
    });
}

// Run on page load
document.addEventListener('DOMContentLoaded', () => {
    fetchOrders();
    updateCartDisplay();
});
function addToCart(itemId) {
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add_to_cart&item_id=${itemId}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        updateCartDisplay();
    });
}

function updateCartDisplay() {
    fetch('cart_status.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('cart-info').innerText = `Cart: ${data.count} items | Rs. ${data.total}`;
        });
}

window.onload = updateCartDisplay;
function addToCart(itemId) {
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add_to_cart&item_id=${itemId}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        updateCartDisplay();
    });
}
fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=add_to_cart&item_id=${itemId}`
})
