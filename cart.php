<?php
// cart.php – корзина и форма оформления заказа
require_once 'functions.php';

$message = '';
$error = '';

// Обработка изменения количества
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $productId => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            updateCartItem($productId, $qty);
        } else {
            removeFromCart($productId);
        }
    }
    header('Location: cart.php');
    exit;
}

// Обработка удаления позиции
if (isset($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    removeFromCart($productId);
    header('Location: cart.php');
    exit;
}

// Обработка оформления заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $address = trim($_POST['customer_address'] ?? '');
    if (empty($name) || empty($phone) || empty($address)) {
        $error = "Заполните все поля формы.";
    } else {
        try {
            createOrder($pdo, $name, $phone, $address);
            $message = "Заказ принят! Спасибо за покупку.";
            // Очищаем корзину уже внутри createOrder, но перезагрузим страницу
            header('Location: cart.php?success=1');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$cartItems = getCartItems($pdo);
$cartNotEmpty = !empty($cartItems);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина - Магазин обуви</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Корзина</h1>
    <div class="nav">
        <a href="index.php">Главная</a> | <a href="cart.php">Корзина</a> | <a href="admin.php">Админка</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success">Заказ принят! Спасибо за покупку.</div>
    <?php elseif ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$cartNotEmpty): ?>
        <p>Корзина пуста. <a href="index.php">Вернуться к покупкам</a></p>
    <?php else: ?>
        <form method="post">
            <?php foreach ($cartItems as $item): 
                $product = $item['product'];
                $quantity = $item['quantity'];
            ?>
                <div class="cart-item">
                    <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                    Цена: <?= number_format($product['price'], 2) ?> руб.<br>
                    Размер: <?= htmlspecialchars($product['size']) ?><br>
                    Количество:
                    <input type="number" name="quantity[<?= $product['id'] ?>]" value="<?= $quantity ?>" min="0" style="width:60px">
                    <a href="?remove=<?= $product['id'] ?>" onclick="return confirm('Удалить товар?')">Удалить</a>
                </div>
            <?php endforeach; ?>
            <button type="submit" name="update_cart">Обновить корзину</button>
        </form>

        <hr>
        <h2>Оформление заказа</h2>
        <form method="post">
            <label>Имя:</label> <input type="text" name="customer_name" required><br>
            <label>Телефон:</label> <input type="text" name="customer_phone" required><br>
            <label>Адрес:</label> <textarea name="customer_address" required rows="2" cols="30"></textarea><br>
            <button type="submit" name="checkout">Оформить заказ</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>