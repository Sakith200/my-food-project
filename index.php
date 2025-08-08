<?php
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Database configuration
$host = 'localhost';
$dbname = 'sm_fast_food';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
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
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
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
            
        case 'place_order':
            $customer_name = $_POST['customer_name'];
            $customer_phone = $_POST['customer_phone'];
            $customer_email = $_POST['customer_email'];
            $delivery_address = $_POST['delivery_address'];
            $payment_method = $_POST['payment_method'];
            $special_instructions = $_POST['special_instructions'];
            
            // Calculate total
            $total = 0;
            $menu_items = getMenuItems();
            foreach ($_SESSION['cart'] as $item_id => $quantity) {
                $item = array_filter($menu_items, function($item) use ($item_id) {
                    return $item['id'] == $item_id;
                });
                if ($item) {
                    $item = array_values($item)[0];
                    $total += $item['price'] * $quantity;
                }
            }
            
            // Insert order into database
            $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_phone, customer_email, delivery_address, payment_method, special_instructions, total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$customer_name, $customer_phone, $customer_email, $delivery_address, $payment_method, $special_instructions, $total]);
            
            $order_id = $pdo->lastInsertId();
            
            // Insert order items
            foreach ($_SESSION['cart'] as $item_id => $quantity) {
                $item = array_filter($menu_items, function($item) use ($item_id) {
                    return $item['id'] == $item_id;
                });
                if ($item) {
                    $item = array_values($item)[0];
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, item_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$order_id, $item_id, $item['name'], $item['price'], $quantity]);
                }
            }
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => 'Order placed successfully']);
            exit;
            
        case 'get_orders':
            $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($orders);
            exit;
            
        case 'update_order_status':
            $order_id = (int)$_POST['order_id'];
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            echo json_encode(['success' => true]);
            exit;
    }
}

// Get menu items
function getMenuItems() {
    return [
        [
            'id' => 1,
            'name' => 'Cheese Burger',
            'price' => 850,
            'description' => 'Juicy beef patty with melted cheese, lettuce, tomato, and special sauce',
            'category' => 'Burgers',
            'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop'
        ],
        [
            'id' => 2,
            'name' => 'Chicken Kottu',
            'price' => 1200,
            'description' => 'Traditional Sri Lankan street food with chicken, vegetables, and roti',
            'category' => 'Local',
            'image' => 'img/KUTTU-PORATTA.jpg'
        ],
        [
            'id' => 3,
            'name' => 'Fish & Chips',
            'price' => 1450,
            'description' => 'Crispy battered fish with golden fries and tartar sauce',
            'category' => 'Seafood',
            'image' => 'img/fish-and-chips-plate.jpg'
        ],
        [
            'id' => 4,
            'name' => 'Chicken Fried Rice',
            'price' => 950,
            'description' => 'Wok-fried rice with tender chicken pieces and mixed vegetables',
            'category' => 'Rice',
            'image' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&h=300&fit=crop'
        ],
        [
            'id' => 5,
            'name' => 'Margherita Pizza',
            'price' => 1650,
            'description' => 'Fresh tomatoes, mozzarella cheese, basil leaves on a crispy crust',
            'category' => 'Pizza',
            'image' => 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=400&h=300&fit=crop'
        ],
        [
            'id' => 6,
            'name' => 'Submarine',
            'price' => 750,
            'description' => 'Sri Lankan style submarine sandwich with chicken, vegetables, and sauce',
            'category' => 'Sandwiches',
            'image' => 'img/istockphoto-175204982-612x612.jpg'
        ],
        [
            'id' => 7,
            'name' => 'Rice & Curry',
            'price' => 680,
            'description' => 'Traditional Sri Lankan rice and curry with dhal, vegetables, and chicken',
            'category' => 'Local',
            'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=400&h=300&fit=crop'
        ],
        [
            'id' => 8,
            'name' => 'BBQ Chicken Pizza',
            'price' => 1850,
            'description' => 'Grilled chicken with BBQ sauce, onions, and mozzarella cheese',
            'category' => 'Pizza',
            'image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400&h=300&fit=crop'
        ],
        [
            'id' => 9,
            'name' => 'Chicken Wings',
            'price' => 1250,
            'description' => 'Spicy buffalo chicken wings served with blue cheese dip',
            'category' => 'Appetizers',
            'image' => 'https://images.unsplash.com/photo-1527477396000-e27163b481c2?w=400&h=300&fit=crop'
        ],
        [
            'id' => 10,
            'name' => 'Spaghetti Bolognese',
            'price' => 1350,
            'description' => 'Classic Italian pasta with rich meat sauce and parmesan cheese',
            'category' => 'Pasta',
            'image' => 'https://images.unsplash.com/photo-1551892374-ecf8754cf8b0?w=400&h=300&fit=crop'
        ],
        [
            'id' => 11,
            'name' => 'Chicken Shawarma',
            'price' => 950,
            'description' => 'Middle Eastern wrap with spiced chicken, vegetables, and garlic sauce',
            'category' => 'Wraps',
            'image' => 'https://images.unsplash.com/photo-1529006557810-274b9b2fc783?w=400&h=300&fit=crop'
        ],
        [
            'id' => 12,
            'name' => 'Deviled Chicken',
            'price' => 1150,
            'description' => 'Spicy Sri Lankan style chicken with bell peppers and onions',
            'category' => 'Local',
            'image' => 'https://images.unsplash.com/photo-1562967916-eb82221dfb92?w=400&h=300&fit=crop'
        ],
        [
            'id' => 13,
            'name' => 'Beef Burger',
            'price' => 1050,
            'description' => 'Premium beef patty with caramelized onions, lettuce, and special sauce',
            'category' => 'Burgers',
            'image' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=400&h=300&fit=crop'
        ],
        [
            'id' => 14,
            'name' => 'Grilled Chicken Salad',
            'price' => 890,
            'description' => 'Fresh mixed greens with grilled chicken, cherry tomatoes, and vinaigrette',
            'category' => 'Salads',
            'image' => 'img/images.jpg'
        ],
        [
            'id' => 15,
            'name' => 'Seafood Pasta',
            'price' => 1750,
            'description' => 'Creamy pasta with prawns, fish, and mixed seafood',
            'category' => 'Pasta',
            'image' => 'img/one-pot-seafood-pasta-thumb.jpg'
        ],
        [
            'id' => 16,
            'name' => 'Seafood Noodles',
            'price' => 980,
            'description' => 'Stir-fried noodles with chicken and vegetables in soy sauce',
            'category' => 'Noodles',
            'image' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400&h=300&fit=crop'
        ],
        [
            'id' => 17,
            'name' => 'Vegetable Kottu',
            'price' => 850,
            'description' => 'Vegetarian version of Sri Lankan kottu with mixed vegetables',
            'category' => 'Local',
            'image' => 'img/Pork-Kottu-1-DSC07740-1.jpg'
        ],
        [
            'id' => 18,
            'name' => 'Chocolate Milkshake',
            'price' => 450,
            'description' => 'Rich and creamy chocolate milkshake topped with whipped cream',
            'category' => 'Beverages',
            'image' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=400&h=300&fit=crop'
        ],
        [
            'id' => 19,
            'name' => 'Pork Submarine',
            'price' => 850,
            'description' => 'Toasted submarine bread with spiced pork filling and vegetables',
            'category' => 'Sandwiches',
            'image' => 'img/Philly-Roast-Pork-Sandwich-with-Lee-Kum-Kee®-Sauce-for-Orange-Chicken-Hero-Featured.png'
        ],
        [
            'id' => 20,
            'name' => 'Prawns Curry',
            'price' => 1650,
            'description' => 'Spicy Sri Lankan prawns curry with coconut milk and spices',
            'category' => 'Local',
            'image' => 'img/prawn-curry-with-coconut-milk-07.jpg'
        ],
        [
            'id' => 21,
            'name' => 'Club Sandwich',
            'price' => 950,
            'description' => 'Triple-decker sandwich with chicken, bacon, lettuce, and tomato',
            'category' => 'Sandwiches',
            'image' => 'https://images.unsplash.com/photo-1567234669003-dce7a7a88821?w=400&h=300&fit=crop'
        ],
        [
            'id' => 22,
            'name' => 'Watalappan',
            'price' => 380,
            'description' => 'Traditional Sri Lankan coconut custard dessert with jaggery',
            'category' => 'Desserts',
            'image' => 'img/1caa4bec10f59ab47f7b9bc51e767c8b_Watalappan-Sri-Lanka_11.jpg'
        ],
        [
            'id' => 23,
            'name' => 'Mixed Grill',
            'price' => 2250,
            'description' => 'Combination of grilled chicken, beef, and sausages with sides',
            'category' => 'Grills',
            'image' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&h=300&fit=crop'
        ],
        [
            'id' => 24,
            'name' => 'Mango Lassi',
            'price' => 420,
            'description' => 'Refreshing yogurt-based drink with fresh mango and spices',
            'category' => 'Beverages',
            'image' => 'img/Mango-Lassi-Recipe-Card-1.webp'
        ],
        [
            'id' => 25,
            'name' => 'Egg Hoppers',
            'price' => 650,
            'description' => 'Traditional Sri Lankan bowl-shaped pancakes with egg and sambols',
            'category' => 'Local',
            'image' => 'img/23A8360.jpg'
        ]
    ];
}

$menu_items = getMenuItems();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Fast Food - Food Ordering System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍔 SM Fast Food</h1>
            <p>Delicious Sri Lankan & International cuisine delivered to your doorstep</p>
        </div>

        <nav class="nav">
            <div class="nav-links">
                <a href="#" onclick="showSection('menu')" class="active">Menu</a>
                <a href="#" onclick="showSection('cart')">Cart</a>
                <a href="#" onclick="showSection('orders')">My Orders</a>
                <a href="#" onclick="showSection('admin')">Admin</a>
            </div>
            <div class="cart-info">
                🛒 Cart: <span id="cart-count">0</span> items - LKR <span id="cart-total">0.00</span>
            </div>
        </nav>

        <div class="content">
            <div id="alerts"></div>

            <!-- Menu Section -->
            <div id="menu" class="section active">
                <h2>Our Menu</h2>
                <div class="menu-grid" id="menu-grid">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item">
                            <div class="category-tag"><?php echo htmlspecialchars($item['category']); ?></div>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-image">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="price">LKR <?php echo number_format($item['price'], 2); ?></p>
                            <p class="description"><?php echo htmlspecialchars($item['description']); ?></p>
                            <button class="btn" onclick="addToCart(<?php echo $item['id']; ?>)">Add to Cart</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Section -->
            <div id="cart" class="section">
                <h2>Your Cart</h2>
                <div class="cart-items" id="cart-items">
                    <p>Your cart is empty. Add some delicious items from our menu!</p>
                </div>
                <div class="total">
                    Total: LKR <span id="total-amount">0.00</span>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button class="btn btn-success" onclick="showCheckout()" id="checkout-btn" style="display: none;">
                        Proceed to Checkout
                    </button>
                </div>
                
                <div class="order-form" id="checkout-form" style="display: none;">
                    <h3>Checkout</h3>
                    <form id="order-form">
                        <div class="form-group">
                            <label for="customer-name">Full Name</label>
                            <input type="text" id="customer-name" required>
                        </div>
                        <div class="form-group">
                            <label for="customer-phone">Phone Number</label>
                            <input type="tel" id="customer-phone" required>
                        </div>
                        <div class="form-group">
                            <label for="customer-email">Email</label>
                            <input type="email" id="customer-email" required>
                        </div>
                        <div class="form-group">
                            <label for="delivery-address">Delivery Address</label>
                            <textarea id="delivery-address" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="payment-method">Payment Method</label>
                            <select id="payment-method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash on Delivery</option>
                                <option value="card">Credit Card</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="special-instructions">Special Instructions</label>
                            <textarea id="special-instructions" rows="3" placeholder="Any special requests..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Place Order</button>
                    </form>
                </div>
            </div>

            <!-- Orders Section -->
            <div id="orders" class="section">
                <h2>My Orders</h2>
                <div id="orders-list">
                    <!-- Orders will be populated by JavaScript -->
                </div>
            </div>

            <!-- Admin Section -->
            <div id="admin" class="section">
                <h2>Admin Panel</h2>
                <div class="admin-panel">
                    <div class="admin-stats">
                        <div class="stat-card">
                            <h3 id="total-orders">0</h3>
                            <p>Total Orders</p>
                        </div>
                        <div class="stat-card">
                            <h3 id="pending-orders">0</h3>
                            <p>Pending Orders</p>
                        </div>
                        <div class="stat-card">
                            <h3 id="total-revenue">LKR 0</h3>
                            <p>Total Revenue</p>
                        </div>
                        <div class="stat-card">
                            <h3 id="menu-items"><?php echo count($menu_items); ?></h3>
                            <p>Menu Items</p>
                        </div>
                    </div>
                    
                    <h3>All Orders</h3>
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

    <script>
        const menuItems = <?php echo json_encode($menu_items); ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>
<?php
$host = 'localhost';
$dbname = 'sm_fast_food';
$username = 'root';  // or your MySQL username
$password = '';      // or your MySQL password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
