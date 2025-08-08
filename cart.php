<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add_to_cart') {
    $item_id = $_POST['item_id'];
    $items = [
        1 => ['name' => 'Pizza', 'price' => 1200],
        2 => ['name' => 'Burger', 'price' => 800],
        3 => ['name' => 'Kottu', 'price' => 950],
    ];

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (!isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id] = [
            'id' => $item_id,
            'name' => $items[$item_id]['name'],
            'price' => $items[$item_id]['price'],
            'quantity' => 1
        ];
    } else {
        $_SESSION['cart'][$item_id]['quantity']++;
    }

    echo json_encode(['success' => true, 'message' => 'Item added to cart!']);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fast Food</title>
    <script src="script.js"></script>
</head>
<body>
    <h1>SM Fast Food</h1>

    <button onclick="addToCart(1)">Add Pizza</button>
    <button onclick="addToCart(2)">Add Burger</button>
    <button onclick="addToCart(3)">Add Kottu</button>

    <div id="cart-info">Cart: 0 items | Rs. 0</div>
    <script src="script.js"></script>

</body>
</html>
<?php
session_start();

$count = 0;
$total = 0;

if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
        $total += $item['quantity'] * $item['price'];
    }
}

echo json_encode(['count' => $count, 'total' => $total]);

session_start();
require_once 'config.php';

// Function to get menu items (same as in index.php)
function getMenuItems() {
    return [
        1 => ['name' => 'Cheese Burger', 'price' => 850],
        2 => ['name' => 'Chicken Kottu', 'price' => 1200],
        3 => ['name' => 'Fish & Chips', 'price' => 1450],
        4 => ['name' => 'Chicken Fried Rice', 'price' => 950],
        5 => ['name' => 'Margherita Pizza', 'price' => 1650],
        6 => ['name' => 'Submarine', 'price' => 750],
        7 => ['name' => 'Rice & Curry', 'price' => 680],
        8 => ['name' => 'BBQ Chicken Pizza', 'price' => 1850],
        9 => ['name' => 'Chicken Wings', 'price' => 1250],
        10 => ['name' => 'Spaghetti Bolognese', 'price' => 1350],
        11 => ['name' => 'Chicken Shawarma', 'price' => 950],
        12 => ['name' => 'Deviled Chicken', 'price' => 1150],
        13 => ['name' => 'Beef Burger', 'price' => 1050],
        14 => ['name' => 'Grilled Chicken Salad', 'price' => 890],
        15 => ['name' => 'Seafood Pasta', 'price' => 1750],
        16 => ['name' => 'Seafood Noodles', 'price' => 980],
        17 => ['name' => 'Vegetable Kottu', 'price' => 850],
        18 => ['name' => 'Chocolate Milkshake', 'price' => 450],
        19 => ['name' => 'Pork Submarine', 'price' => 850],
        20 => ['name' => 'Prawns Curry', 'price' => 1650],
        21 => ['name' => 'Club Sandwich', 'price' => 950],
        22 => ['name' => 'Watalappan', 'price' => 380],
        23 => ['name' => 'Mixed Grill', 'price' => 2250],
        24 => ['name' => 'Mango Lassi', 'price' => 420],
        25 => ['name' => 'Egg Hoppers', 'price' => 650]
    ];
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$count = 0;
$total = 0;
$menu_items = getMenuItems();

// Calculate cart totals
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item_id => $quantity) {
        if (isset($menu_items[$item_id])) {
            $count += $quantity;
            $total += $menu_items[$item_id]['price'] * $quantity;
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'count' => $count, 
    'total' => $total
]);
?>