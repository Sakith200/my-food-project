// Global variables
let currentCart = [];
let currentOrders = [];

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Show loading screen
    createParticles();
    setTimeout(() => {
        document.getElementById('loadingScreen').classList.add('hidden');
        document.getElementById('mainContainer').style.display = 'block';
        
        // Initialize app functions
        updateCartDisplay();
        fetchOrders();
        
        // Add scroll animations
        addScrollAnimations();
        
        // Initialize enhanced animations
        initializeEnhancedAnimations();
    }, 2000);
});

// Create particle effect
function createParticles() {
    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'particles';
    document.querySelector('.loading-screen').appendChild(particlesContainer);
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
        particlesContainer.appendChild(particle);
    }
}

// Initialize enhanced animations
function initializeEnhancedAnimations() {
    // Add staggered animation delays to menu items
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach((item, index) => {
        item.style.animationDelay = (index * 0.1) + 's';
    });
    
    // Add staggered animation delays to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.setProperty('--animation-order', index);
    });
    
    // Add typewriter effect to title
    const title = document.querySelector('.animated-title');
    if (title) {
        title.classList.add('typewriter-text');
    }
    
    // Initialize intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);
    
    // Observe all animated elements
    document.querySelectorAll('.menu-item, .order-card, .stat-card').forEach(el => {
        observer.observe(el);
    });
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    const icon = notification.querySelector('.notification-icon');
    const messageEl = notification.querySelector('.notification-message');
    
    // Set icon based on type
    if (type === 'success') {
        icon.className = 'notification-icon fas fa-check-circle';
        notification.classList.remove('error');
    } else {
        icon.className = 'notification-icon fas fa-exclamation-circle';
        notification.classList.add('error');
    }
    
    messageEl.textContent = message;
    notification.classList.add('show');
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Show and hide sections with animation
function showSection(id) {
    // Remove active class from all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Add active class to target section
    document.getElementById(id).classList.add('active');
    
    // Update navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    const activeLink = [...document.querySelectorAll('.nav-link')].find(link =>
        link.getAttribute('onclick')?.includes(id)
    );
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    // Trigger specific section functions
    if (id === 'cart') {
        loadCart();
    } else if (id === 'orders') {
        fetchOrders();
    } else if (id === 'admin') {
        fetchOrders();
    }
}

// Filter menu items
function filterMenu(category) {
    const menuItems = document.querySelectorAll('.menu-item');
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    // Update filter buttons
    filterBtns.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter items with animation
    menuItems.forEach((item, index) => {
        const itemCategory = item.getAttribute('data-category');
        
        if (category === 'all' || itemCategory === category) {
            item.style.display = 'block';
            item.style.animation = `fadeInUp 0.5s ease ${index * 0.1}s forwards`;
        } else {
            item.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => {
                item.style.display = 'none';
            }, 300);
        }
    });
}

// Add to cart with animation
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
        if (data.success) {
            showNotification(data.message, 'success');
            updateCartDisplay();
            
            // Add visual feedback
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Added!';
            button.style.background = 'var(--success-color)';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
            }, 1500);
        } else {
            showNotification('Failed to add item to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Update cart display
function updateCartDisplay() {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=get_cart'
    })
    .then(res => res.json())
    .then(data => {
        currentCart = data.items;
        const count = data.items.reduce((sum, item) => sum + item.quantity, 0);
        const total = data.total;
        
        document.getElementById('cart-count').textContent = count;
        document.getElementById('cart-total').textContent = total.toFixed(2);
        
        // Update cart icon animation
        const cartIcon = document.querySelector('.cart-icon');
        if (count > 0) {
            cartIcon.style.animation = 'cartBounce 0.5s ease';
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
    });
}

// Load cart items
function loadCart() {
    const cartItemsContainer = document.getElementById('cart-items');
    const cartSummary = document.getElementById('cart-summary');
    
    if (currentCart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart empty-cart-icon"></i>
                <p>Your cart is empty. Add some delicious items from our menu!</p>
            </div>
        `;
        cartSummary.style.display = 'none';
        return;
    }
    
    let cartHTML = '';
    let subtotal = 0;
    
    currentCart.forEach(item => {
        subtotal += item.subtotal;
        cartHTML += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <h4>${item.name}</h4>
                    <p class="item-price">LKR ${item.price.toFixed(2)} each</p>
                </div>
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="quantity-display">${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn btn-danger" onclick="removeFromCart(${item.id})" style="margin-left: 15px;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    cartItemsContainer.innerHTML = cartHTML;
    
    // Update summary
    const deliveryFee = 200;
    const total = subtotal + deliveryFee;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('total-amount').textContent = total.toFixed(2);
    
    cartSummary.style.display = 'block';
}

// Update cart quantity
function updateCartQuantity(itemId, quantity) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=update_cart&item_id=${itemId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay();
            loadCart();
        }
    })
    .catch(error => {
        console.error('Error updating quantity:', error);
    });
}

// Remove from cart
function removeFromCart(itemId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=remove_from_cart&item_id=${itemId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                updateCartDisplay();
                loadCart();
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
        });
    }
}

// Show checkout form
function showCheckout() {
    const checkoutForm = document.getElementById('checkout-form');
    checkoutForm.style.display = 'block';
    checkoutForm.scrollIntoView({ behavior: 'smooth' });
}

// Handle order form submission
document.addEventListener('DOMContentLoaded', function() {
    const orderForm = document.getElementById('order-form');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = orderForm.querySelector('.submit-order-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';
            submitBtn.disabled = true;
            
            const formData = new FormData(orderForm);
            formData.append('action', 'place_order');
            
            // Convert FormData to URLSearchParams
            const params = new URLSearchParams();
            params.append('action', 'place_order');
            params.append('customer_name', document.getElementById('customer-name').value);
            params.append('customer_phone', document.getElementById('customer-phone').value);
            params.append('customer_email', document.getElementById('customer-email').value);
            params.append('delivery_address', document.getElementById('delivery-address').value);
            params.append('payment_method', document.getElementById('payment-method').value);
            params.append('special_instructions', document.getElementById('special-instructions').value);
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(`Order #${data.order_id} placed successfully!`, 'success');
                    
                    // Reset form and hide checkout
                    orderForm.reset();
                    document.getElementById('checkout-form').style.display = 'none';
                    
                    // Update displays
                    updateCartDisplay();
                    loadCart();
                    
                    // Show success animation
                    showOrderSuccessAnimation();
                } else {
                    showNotification(data.message || 'Failed to place order', 'error');
                }
            })
            .catch(error => {
                console.error('Error placing order:', error);
                showNotification('An error occurred while placing your order', 'error');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});

// Show order success animation
function showOrderSuccessAnimation() {
    const successDiv = document.createElement('div');
    successDiv.innerHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                    text-align: center; z-index: 10000; animation: successPop 0.5s ease;">
            <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="success-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="success-checkmark-check" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
            <h3 style="color: var(--dark-color); margin-bottom: 10px;">Order Placed Successfully!</h3>
            <p style="color: #666;">Thank you for your order. We'll prepare it with love!</p>
        </div>
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

// Fetch and display orders
function fetchOrders() {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=get_orders'
    })
    .then(res => res.json())
    .then(orders => {
        currentOrders = orders;
        displayUserOrders(orders);
        displayAdminOrders(orders);
        updateAdminStats(orders);
    })
    .catch(error => {
        console.error('Error fetching orders:', error);
    });
}

// Display user orders
function displayUserOrders(orders) {
    const ordersList = document.getElementById('orders-list');
    
    if (!orders.length) {
        ordersList.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-receipt empty-cart-icon"></i>
                <p>No orders yet. Place your first order from our delicious menu!</p>
            </div>
        `;
        return;
    }
    
    let ordersHTML = '';
    orders.forEach(order => {
        const statusColor = getStatusColor(order.status);
        ordersHTML += `
            <div class="order-card">
                <h4>Order #${order.id}</h4>
                <p><strong>Customer:</strong> ${order.customer_name}</p>
                <p><strong>Phone:</strong> ${order.customer_phone}</p>
                <p><strong>Total:</strong> LKR ${parseFloat(order.total).toFixed(2)}</p>
                <p><strong>Status:</strong> <span style="color: ${statusColor}; font-weight: bold; text-transform: uppercase;">${order.status}</span></p>
                <p><strong>Order Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                ${order.special_instructions ? `<p><strong>Special Instructions:</strong> ${order.special_instructions}</p>` : ''}
            </div>
        `;
    });
    
    ordersList.innerHTML = ordersHTML;
}

// Display admin orders
function displayAdminOrders(orders) {
    const adminTable = document.getElementById('admin-orders-table');
    
    if (!orders.length) {
        adminTable.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                    No orders yet
                </td>
            </tr>
        `;
        return;
    }
    
    let tableHTML = '';
    orders.forEach(order => {
        tableHTML += `
            <tr style="--row-index: ${index};">
                <td>#${order.id}</td>
                <td>${order.customer_name}</td>
                <td>${order.customer_phone}</td>
                <td>LKR ${parseFloat(order.total).toFixed(2)}</td>
                <td>
                    <span style="color: ${getStatusColor(order.status)}; font-weight: bold; text-transform: uppercase;">
                        ${order.status}
                    </span>
                </td>
                <td>${new Date(order.created_at).toLocaleString()}</td>
                <td>
                    <select onchange="updateOrderStatus(${order.id}, this.value)" style="color: ${getStatusColor(order.status)};">
                        <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Processing</option>
                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </td>
            </tr>
        `;
    });
    
    adminTable.innerHTML = tableHTML;
}

// Update admin statistics
function updateAdminStats(orders) {
    let totalRevenue = 0;
    let pendingCount = 0;
    let processingCount = 0;
    let completedCount = 0;
    
    orders.forEach(order => {
        totalRevenue += parseFloat(order.total);
        
        switch(order.status) {
            case 'pending':
                pendingCount++;
                break;
            case 'processing':
                processingCount++;
                break;
            case 'completed':
                completedCount++;
                break;
        }
    });
    
    // Animate numbers
    animateNumber('total-orders', orders.length);
    animateNumber('pending-orders', pendingCount);
    animateRevenue('total-revenue', totalRevenue);
}

// Animate number counting
function animateNumber(elementId, targetNumber) {
    const element = document.getElementById(elementId);
    const startNumber = 0;
    const duration = 1000;
    const startTime = performance.now();
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const currentNumber = Math.floor(startNumber + (targetNumber - startNumber) * progress);
        
        element.textContent = currentNumber;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }
    
    requestAnimationFrame(updateNumber);
}

// Animate revenue counting
function animateRevenue(elementId, targetAmount) {
    const element = document.getElementById(elementId);
    const startAmount = 0;
    const duration = 1000;
    const startTime = performance.now();
    
    function updateAmount(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const currentAmount = startAmount + (targetAmount - startAmount) * progress;
        
        element.textContent = `LKR ${currentAmount.toFixed(2)}`;
        
        if (progress < 1) {
            requestAnimationFrame(updateAmount);
        }
    }
    
    requestAnimationFrame(updateAmount);
}

// Update order status
function updateOrderStatus(orderId, status) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=update_order_status&order_id=${orderId}&status=${status}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification(`Order #${orderId} status updated to ${status}`, 'success');
            fetchOrders();
        } else {
            showNotification('Failed to update order status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating order status:', error);
        showNotification('An error occurred', 'error');
    });
}

// Get status color
function getStatusColor(status) {
    switch(status) {
        case 'pending':
            return '#f39c12';
        case 'processing':
            return '#3498db';
        case 'completed':
            return '#2ecc71';
        case 'cancelled':
            return '#e74c3c';
        default:
            return '#666';
    }
}

// Add scroll animations
function addScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
            }
        });
    }, observerOptions);
    
    // Observe menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        observer.observe(item);
    });
}

// Add CSS animations dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-20px); }
    }
    
    @keyframes successPop {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
        100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    }
    
    @keyframes menuItemHover {
        0% { transform: translateY(0) rotateY(0); }
        50% { transform: translateY(-10px) rotateY(5deg); }
        100% { transform: translateY(-5px) rotateY(0); }
    }
    
    .menu-item:hover {
        animation: menuItemHover 0.6s ease-in-out;
    }
    
    @keyframes buttonPress {
        0% { transform: scale(1); }
        50% { transform: scale(0.95); }
        100% { transform: scale(1); }
    }
    
    .btn:active {
        animation: buttonPress 0.2s ease-in-out;
    }
`;
document.head.appendChild(style);

// Enhanced cart animation
function animateCartUpdate() {
    const cartIcon = document.querySelector('.cart-icon');
    const cartInfo = document.querySelector('.cart-info');
    
    cartIcon.style.animation = 'heartbeat 0.6s ease-in-out';
    cartInfo.style.animation = 'glow 0.8s ease-in-out';
    
    setTimeout(() => {
        cartIcon.style.animation = '';
        cartInfo.style.animation = '';
    }, 800);
}

// Enhanced menu item interaction
function enhanceMenuItemInteractions() {
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
            this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

// Call enhanced interactions when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        enhanceMenuItemInteractions();
    }, 2500);
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + M for Menu
    if (e.altKey && e.key === 'm') {
        e.preventDefault();
        showSection('menu');
    }
    
    // Alt + C for Cart
    if (e.altKey && e.key === 'c') {
        e.preventDefault();
        showSection('cart');
    }
    
    // Alt + O for Orders
    if (e.altKey && e.key === 'o') {
        e.preventDefault();
        showSection('orders');
    }
    
    // Alt + A for Admin
    if (e.altKey && e.key === 'a') {
        e.preventDefault();
        showSection('admin');
    }
});

// Add smooth scrolling for better UX
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Initialize tooltips for better UX
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 14px;
                z-index: 1000;
                pointer-events: none;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// Call initialize tooltips when DOM is ready
document.addEventListener('DOMContentLoaded', initializeTooltips);

// Enhanced scroll reveal animation
function revealOnScroll() {
    const reveals = document.querySelectorAll('.menu-item, .order-card, .stat-card');
    
    for (let i = 0; i < reveals.length; i++) {
        const windowHeight = window.innerHeight;
        const elementTop = reveals[i].getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < windowHeight - elementVisible) {
            reveals[i].classList.add('active');
        } else {
            reveals[i].classList.remove('active');
        }
    }
}

window.addEventListener('scroll', revealOnScroll);

// Performance optimization for animations
function optimizeAnimations() {
    // Reduce animations on low-end devices
    if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) {
        document.documentElement.style.setProperty('--animation-duration', '0.2s');
    }
    
    // Pause animations when tab is not visible
    document.addEventListener('visibilitychange', function() {
        const animatedElements = document.querySelectorAll('[style*="animation"]');
        
        if (document.hidden) {
            animatedElements.forEach(el => {
                el.style.animationPlayState = 'paused';
            });
        } else {
            animatedElements.forEach(el => {
                el.style.animationPlayState = 'running';
            });
        }
    });
}

// Initialize performance optimizations
document.addEventListener('DOMContentLoaded', optimizeAnimations);