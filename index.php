<?php
session_start();
require_once 'config.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // CSRF protection (basic)
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    switch ($_POST['action']) {
        case 'add_to_cart':
            $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
            if (!$item_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
                exit;
            }
            
            // Verify item exists and is available
            $item = fetchOne("SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1", [$item_id]);
            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found or unavailable']);
                exit;
            }
            
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]++;
            } else {
                $_SESSION['cart'][$item_id] = 1;
            }
            echo json_encode([
                'success' => true, 
                'message' => $item['name'] . ' added to cart successfully!',
                'item_name' => $item['name']
            ]);
            exit;
            
        case 'update_cart':
            $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
            
            if (!$item_id || $quantity < 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }
            
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$item_id]);
            } else {
                // Limit quantity to prevent abuse
                $quantity = min($quantity, 99);
                $_SESSION['cart'][$item_id] = $quantity;
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'remove_from_cart':
            $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
            if (!$item_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
                exit;
            }
            unset($_SESSION['cart'][$item_id]);
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            exit;
            
        case 'get_cart':
            $cart_items = [];
            $total = 0;
            
            if (!empty($_SESSION['cart'])) {
                $item_ids = array_keys($_SESSION['cart']);
                $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
                $menu_items = fetchAll("SELECT * FROM menu_items WHERE id IN ($placeholders) AND is_available = 1", $item_ids);
                
                foreach ($menu_items as $item) {
                    $quantity = isset($_SESSION['cart'][$item['id']]) ? $_SESSION['cart'][$item['id']] : 0;
                    if ($quantity <= 0) continue;
                    
                    $cart_items[] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $quantity,
                        'subtotal' => $item['price'] * $quantity
                    ];
                    $total += $item['price'] * $quantity;
                }
            }
            
            echo json_encode(['items' => $cart_items, 'total' => $total]);
            exit;
            
        case 'place_order':
            // Validate required fields
            $required_fields = ['customer_name', 'customer_phone', 'customer_email', 'delivery_address', 'payment_method'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
                    exit;
                }
            }
            
            // Sanitize and validate input
            $customer_name = sanitizeInput($_POST['customer_name']);
            $customer_phone = sanitizeInput($_POST['customer_phone']);
            $customer_email = sanitizeInput($_POST['customer_email']);
            $delivery_address = sanitizeInput($_POST['delivery_address']);
            $payment_method = sanitizeInput($_POST['payment_method']);
            $special_instructions = sanitizeInput($_POST['special_instructions'] ?? '');
            
            // Validate email and phone
            if (!validateEmail($customer_email)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                exit;
            }
            
            if (!validatePhone($customer_phone)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
                exit;
            }
            
            // Check if cart is not empty
            if (empty($_SESSION['cart'])) {
                echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
                exit;
            }
            
            try {
                $pdo = getDBConnection();
                $pdo->beginTransaction();
                
                // Calculate total
                $total = 0;
                $item_ids = array_keys($_SESSION['cart']);
                $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
                $menu_items = fetchAll("SELECT * FROM menu_items WHERE id IN ($placeholders) AND is_available = 1", $item_ids);
                
                if (empty($menu_items)) {
                    throw new Exception("No valid items found in cart");
                }
                
                foreach ($menu_items as $item) {
                    $quantity = $_SESSION['cart'][$item['id']];
                    $total += $item['price'] * $quantity;
                }
                
                // Add delivery fee
                $delivery_fee = 200.00;
                $total += $delivery_fee;
                
                // Insert order
                executeQuery("INSERT INTO orders (customer_name, customer_phone, customer_email, delivery_address, payment_method, special_instructions, total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())", [
                    $customer_name,
                    $customer_phone,
                    $customer_email,
                    $delivery_address,
                    $payment_method,
                    $special_instructions,
                    $total
                ]);
                
                $order_id = getLastInsertId();
                
                // Insert order items
                foreach ($menu_items as $item) {
                    $quantity = $_SESSION['cart'][$item['id']];
                    executeQuery("INSERT INTO order_items (order_id, item_id, item_name, price, quantity) VALUES (?, ?, ?, ?, ?)", [
                        $order_id, $item['id'], $item['name'], $item['price'], $quantity
                    ]);
                }
                
                $pdo->commit();
                $_SESSION['cart'] = [];
                
                echo json_encode([
                    'success' => true, 
                    'order_id' => $order_id, 
                    'message' => 'Order #' . $order_id . ' placed successfully!',
                    'total' => $total
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Order placement failed: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to place order. Please try again.']);
            }
            exit;
            
        case 'get_orders':
            $orders = fetchAll("SELECT * FROM orders ORDER BY created_at DESC LIMIT 100");
            echo json_encode($orders);
            exit;
            
        case 'update_order_status':
            $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
            $status = sanitizeInput($_POST['status']);
            
            $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
            if (!$order_id || !in_array($status, $valid_statuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }
            
            executeQuery("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $order_id]);
            echo json_encode(['success' => true]);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Get menu items from database
function getMenuItems() {
    return fetchAll("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name");
}

// Get cart count for display
function getCartCount() {
    if (empty($_SESSION['cart'])) return 0;
    return array_sum($_SESSION['cart']);
}

// Get cart total for display
function getCartTotal() {
    if (empty($_SESSION['cart'])) return 0;
    
    $item_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
    $menu_items = fetchAll("SELECT id, price FROM menu_items WHERE id IN ($placeholders) AND is_available = 1", $item_ids);
    
    $total = 0;
    foreach ($menu_items as $item) {
        $quantity = $_SESSION['cart'][$item['id']];
        $total += $item['price'] * $quantity;
    }
    
    return $total;
}

try {
    $menu_items = getMenuItems();
    $cart_count = getCartCount();
    $cart_total = getCartTotal();
} catch (Exception $e) {
    error_log("Error loading page data: " . $e->getMessage());
    $menu_items = [];
    $cart_count = 0;
    $cart_total = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Fast Food - Food Ordering System</title>
    <meta name="description" content="Order delicious Sri Lankan & International cuisine online. Fast delivery, fresh ingredients, authentic flavors.">
    <meta name="keywords" content="food delivery, Sri Lankan food, fast food, online ordering">
    <link rel="stylesheet" href="enhanced_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-animation">
            <div class="burger-animation">
                <div class="bun-top"></div>
                <div class="lettuce"></div>
                <div class="tomato"></div>
                <div class="patty"></div>
                <div class="bun-bottom"></div>
            </div>
            <h2>Loading Delicious Food...</h2>
        </div>
    </div>

    <div class="container" id="mainContainer">
        <div class="header">
            <div class="floating-icons">
                <i class="fas fa-pizza-slice floating-icon"></i>
                <i class="fas fa-hamburger floating-icon"></i>
                <i class="fas fa-ice-cream floating-icon"></i>
                <i class="fas fa-coffee floating-icon"></i>
            </div>
            <h1 class="animated-title">🍔 SM Fast Food</h1>
            <p class="subtitle">Delicious Sri Lankan & International cuisine delivered to your doorstep</p>
        </div>

        <nav class="nav">
            <div class="nav-links">
                <a href="#" onclick="showSection('menu')" class="nav-link active">
                    <i class="fas fa-utensils"></i> Menu
                </a>
                <a href="#" onclick="showSection('cart')" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a>
                <a href="#" onclick="showSection('orders')" class="nav-link">
                    <i class="fas fa-receipt"></i> My Orders
                </a>
                <a href="#" onclick="showSection('admin')" class="nav-link">
                    <i class="fas fa-cog"></i> Admin
                </a>
            </div>
            <div class="cart-info" id="cartInfo">
                <i class="fas fa-shopping-cart cart-icon"></i>
                <span>Cart: <span id="cart-count"><?php echo $cart_count; ?></span> items - LKR <span id="cart-total"><?php echo number_format($cart_total, 2); ?></span></span>
            </div>
        </nav>

        <div class="content">
            <div id="alerts" class="alerts-container"></div>
            
            <!-- Hidden CSRF token for AJAX requests -->
            <input type="hidden" id="csrf-token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

            <!-- Menu Section -->
            <div id="menu" class="section active">
                <div class="section-header">
                    <h2><i class="fas fa-utensils"></i> Our Menu</h2>
                    <div class="category-filter">
                        <button class="filter-btn active" onclick="filterMenu('all')">All</button>
                        <button class="filter-btn" onclick="filterMenu('Burgers')">Burgers</button>
                        <button class="filter-btn" onclick="filterMenu('Pizza')">Pizza</button>
                        <button class="filter-btn" onclick="filterMenu('Local')">Local</button>
                        <button class="filter-btn" onclick="filterMenu('Beverages')">Beverages</button>
                        <button class="filter-btn" onclick="filterMenu('Seafood')">Seafood</button>
                        <button class="filter-btn" onclick="filterMenu('Desserts')">Desserts</button>
                    </div>
                </div>
                <div class="menu-grid" id="menu-grid">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item" data-category="<?php echo htmlspecialchars($item['category']); ?>" data-aos="fade-up">
                            <div class="category-tag"><?php echo htmlspecialchars($item['category']); ?></div>
                            <div class="menu-item-image-container">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-image">
                                <div class="image-overlay">
                                    <button class="quick-add-btn" onclick="addToCart(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="menu-item-content">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="price">LKR <?php echo number_format($item['price'], 2); ?></p>
                                <p class="description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <button class="btn add-to-cart-btn" onclick="addToCart(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Section -->
            <div id="cart" class="section">
                <div class="section-header">
                    <h2><i class="fas fa-shopping-cart"></i> Your Cart</h2>
                </div>
                <div class="cart-container">
                    <div class="cart-items" id="cart-items">
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart empty-cart-icon"></i>
                            <p>Your cart is empty. Add some delicious items from our menu!</p>
                        </div>
                    </div>
                    <div class="cart-summary" id="cart-summary" style="display: none;">
                        <div class="total-section">
                            <h3>Order Summary</h3>
                            <div class="total-line">
                                <span>Subtotal:</span>
                                <span>LKR <span id="subtotal">0.00</span></span>
                            </div>
                            <div class="total-line">
                                <span>Delivery Fee:</span>
                                <span>LKR 200.00</span>
                            </div>
                            <div class="total-line total">
                                <span>Total:</span>
                                <span>LKR <span id="total-amount">200.00</span></span>
                            </div>
                            <button class="btn btn-success checkout-btn" onclick="showCheckout()" id="checkout-btn">
                                <i class="fas fa-credit-card"></i> Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-form" id="checkout-form" style="display: none;">
                    <h3><i class="fas fa-credit-card"></i> Checkout</h3>
                    <form id="order-form" class="animated-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer-name"><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" id="customer-name" required>
                            </div>
                            <div class="form-group">
                                <label for="customer-phone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" id="customer-phone" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="customer-email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="customer-email" required>
                        </div>
                        <div class="form-group">
                            <label for="delivery-address"><i class="fas fa-map-marker-alt"></i> Delivery Address</label>
                            <textarea id="delivery-address" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="payment-method"><i class="fas fa-credit-card"></i> Payment Method</label>
                            <select id="payment-method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash on Delivery</option>
                                <option value="card">Credit Card</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="special-instructions"><i class="fas fa-comment"></i> Special Instructions</label>
                            <textarea id="special-instructions" rows="3" placeholder="Any special requests..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success submit-order-btn">
                            <i class="fas fa-check"></i> Place Order
                        </button>
                    </form>
                </div>
            </div>

            <!-- Orders Section -->
            <div id="orders" class="section">
                <div class="section-header">
                    <h2><i class="fas fa-receipt"></i> My Orders</h2>
                </div>
                <div id="orders-list" class="orders-container">
                    <!-- Orders will be populated by JavaScript -->
                </div>
            </div>

            <!-- Admin Section -->
            <div id="admin" class="section">
                <div class="section-header">
                    <h2><i class="fas fa-cog"></i> Admin Panel</h2>
                </div>
                <div class="admin-panel">
                    <div class="admin-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-orders">0</h3>
                                <p>Total Orders</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="pending-orders">0</h3>
                                <p>Pending Orders</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-revenue">LKR 0</h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <input type="text" id="customer-name" required maxlength="255" pattern="[A-Za-z\s]+" title="Please enter a valid name">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="stat-content">
                            <input type="tel" id="customer-phone" required maxlength="20" pattern="[0-9+\-\s()]+" title="Please enter a valid phone number">
                                <p>Menu Items</p>
                            </div>
                        </div>
                    </div>
                        <input type="email" id="customer-email" required maxlength="255">
                    <div class="admin-orders">
                        <h3><i class="fas fa-list"></i> All Orders</h3>
                        <div class="table-container">
                        <textarea id="delivery-address" rows="3" required maxlength="500"></textarea>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                            <option value="bank_transfer">Bank Transfer</option>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-orders-table">
                        <textarea id="special-instructions" rows="3" placeholder="Any special requests..." maxlength="500"></textarea>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="notification" id="notification">
        <div class="notification-content">
            <i class="notification-icon"></i>
            <span class="notification-message"></span>
        </div>
    </div>

    <!-- Performance monitoring -->
    <script>
        // Basic performance monitoring
        window.addEventListener('load', function() {
            if ('performance' in window) {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log('Page load time:', loadTime + 'ms');
            }
        });
    </script>
    
    <script src="enhanced_script.js"></script>
</body>
</html>