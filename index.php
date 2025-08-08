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
    
    switch ($_POST['action']) {
        case 'add_to_cart':
            $item_id = (int)$_POST['item_id'];
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]++;
            } else {
                $_SESSION['cart'][$item_id] = 1;
            }
            echo json_encode(['success' => true, 'message' => 'Item added to cart successfully!']);
            exit;
            
        case 'update_cart':
            $item_id = (int)$_POST['item_id'];
            $quantity = (int)$_POST['quantity'];
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$item_id]);
            } else {
                $_SESSION['cart'][$item_id] = $quantity;
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'remove_from_cart':
            $item_id = (int)$_POST['item_id'];
            unset($_SESSION['cart'][$item_id]);
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            exit;
            
        case 'get_cart':
            $pdo = getDBConnection();
            $cart_items = [];
            $total = 0;
            
            if (!empty($_SESSION['cart'])) {
                $item_ids = array_keys($_SESSION['cart']);
                $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
                $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
                $stmt->execute($item_ids);
                $menu_items = $stmt->fetchAll();
                
                foreach ($menu_items as $item) {
                    $quantity = $_SESSION['cart'][$item['id']];
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
            $pdo = getDBConnection();
            
            try {
                $pdo->beginTransaction();
                
                // Calculate total
                $total = 0;
                if (!empty($_SESSION['cart'])) {
                    $item_ids = array_keys($_SESSION['cart']);
                    $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
                    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
                    $stmt->execute($item_ids);
                    $menu_items = $stmt->fetchAll();
                    
                    foreach ($menu_items as $item) {
                        $quantity = $_SESSION['cart'][$item['id']];
                        $total += $item['price'] * $quantity;
                    }
                }
                
                // Insert order
                $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_phone, customer_email, delivery_address, payment_method, special_instructions, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([
                    $_POST['customer_name'],
                    $_POST['customer_phone'],
                    $_POST['customer_email'],
                    $_POST['delivery_address'],
                    $_POST['payment_method'],
                    $_POST['special_instructions'],
                    $total
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Insert order items
                foreach ($menu_items as $item) {
                    $quantity = $_SESSION['cart'][$item['id']];
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, item_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$order_id, $item['id'], $item['name'], $item['price'], $quantity]);
                }
                
                $pdo->commit();
                $_SESSION['cart'] = [];
                
                echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => 'Order placed successfully!']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to place order. Please try again.']);
            }
            exit;
            
        case 'get_orders':
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
            $orders = $stmt->fetchAll();
            echo json_encode($orders);
            exit;
            
        case 'update_order_status':
            $pdo = getDBConnection();
            $order_id = (int)$_POST['order_id'];
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            echo json_encode(['success' => true]);
            exit;
    }
}

// Get menu items from database
function getMenuItems() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name");
    return $stmt->fetchAll();
}

$menu_items = getMenuItems();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Fast Food - Food Ordering System</title>
    <link rel="stylesheet" href="enhanced_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <span>Cart: <span id="cart-count">0</span> items - LKR <span id="cart-total">0.00</span></span>
            </div>
        </nav>

        <div class="content">
            <div id="alerts" class="alerts-container"></div>

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
                            <div class="stat-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="menu-items"><?php echo count($menu_items); ?></h3>
                                <p>Menu Items</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-orders">
                        <h3><i class="fas fa-list"></i> All Orders</h3>
                        <div class="table-container">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-orders-table">
                                    <!-- Orders will be populated by JavaScript -->
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

    <script src="enhanced_script.js"></script>
</body>
</html>