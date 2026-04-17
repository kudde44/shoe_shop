<?php
// functions.php – вспомогательные функции для работы с БД, корзиной и заказами
require_once 'db.php';
session_start();

// Инициализация корзины в сессии
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Получить список всех товаров
 */
function getAllProducts($pdo) {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получить товар по ID
 */
function getProductById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Добавить товар в корзину (сессию)
 */
function addToCart($productId, $quantity = 1) {
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Обновить количество товара в корзине
 */
function updateCartItem($productId, $quantity) {
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Удалить товар из корзины
 */
function removeFromCart($productId) {
    unset($_SESSION['cart'][$productId]);
}

/**
 * Получить содержимое корзины с данными из БД
 * Возвращает массив: [ ['product' => [...], 'quantity' => ...], ... ]
 */
function getCartItems($pdo) {
    $items = [];
    if (empty($_SESSION['cart'])) {
        return $items;
    }
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $product) {
        $productId = $product['id'];
        $items[] = [
            'product' => $product,
            'quantity' => $_SESSION['cart'][$productId]
        ];
    }
    return $items;
}

/**
 * Оформить заказ: проверяет остатки, создаёт запись в БД, уменьшает stock, очищает корзину
 * Возвращает true или выбрасывает исключение с сообщением об ошибке
 */
function createOrder($pdo, $customerName, $customerPhone, $customerAddress) {
    // Начинаем транзакцию
    $pdo->beginTransaction();
    try {
        $cartItems = getCartItems($pdo);
        if (empty($cartItems)) {
            throw new Exception("Корзина пуста");
        }
        $totalAmount = 0;
        $orderItemsData = []; // для вставки в order_items
        // Проверяем наличие товара на складе и собираем данные
        foreach ($cartItems as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            if ($product['stock'] < $quantity) {
                throw new Exception("Недостаточно товара: {$product['name']}. Доступно: {$product['stock']}");
            }
            $price = $product['price'];
            $totalAmount += $price * $quantity;
            $orderItemsData[] = [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'price' => $price
            ];
        }
        // Вставляем заказ
        $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_phone, customer_address, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$customerName, $customerPhone, $customerAddress, $totalAmount]);
        $orderId = $pdo->lastInsertId();
        // Вставляем позиции заказа и уменьшаем остатки
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($orderItemsData as $item) {
            $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
        }
        // Очищаем корзину
        $_SESSION['cart'] = [];
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Получить список всех заказов
 */
function getAllOrders($pdo) {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получить позиции конкретного заказа с названиями товаров
 */
function getOrderItemsWithProducts($pdo, $orderId) {
    $sql = "SELECT oi.*, p.name as product_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Добавить новый товар
 */
function addProduct($pdo, $name, $price, $size, $stock) {
    $stmt = $pdo->prepare("INSERT INTO products (name, price, size, stock) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $price, $size, $stock]);
}

/**
 * Обновить товар
 */
function updateProduct($pdo, $id, $name, $price, $size, $stock) {
    $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, size = ?, stock = ? WHERE id = ?");
    return $stmt->execute([$name, $price, $size, $stock, $id]);
}

/**
 * Удалить товар
 */
function deleteProduct($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    return $stmt->execute([$id]);
}
?>