<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$count = 0;
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    try {
        $pdo = getDBConnection();
        $item_ids = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT id, price FROM menu_items WHERE id IN ($placeholders)");
        $stmt->execute($item_ids);
        $menu_items = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($_SESSION['cart'] as $item_id => $quantity) {
            if (isset($menu_items[$item_id])) {
                $count += $quantity;
                $total += $menu_items[$item_id] * $quantity;
            }
        }
    } catch (Exception $e) {
        error_log("Error in cart_status.php: " . $e->getMessage());
    }
}

echo json_encode([
    'count' => $count,
    'total' => $total
]);
?>