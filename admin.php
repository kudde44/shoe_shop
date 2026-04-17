<?php
// admin.php – административная панель (управление товарами + просмотр заказов)
require_once 'functions.php';

$message = '';
$error = '';

// --- Обработка действий с товарами ---
// Добавление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    if ($name && $price > 0 && $size) {
        if (addProduct($pdo, $name, $price, $size, $stock)) {
            $message = "Товар добавлен.";
        } else {
            $error = "Ошибка добавления товара.";
        }
    } else {
        $error = "Заполните все поля корректно.";
    }
}

// Обновление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    if ($id && $name && $price > 0 && $size) {
        if (updateProduct($pdo, $id, $name, $price, $size, $stock)) {
            $message = "Товар обновлён.";
        } else {
            $error = "Ошибка обновления.";
        }
    } else {
        $error = "Заполните все поля.";
    }
}

// Удаление товара
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if (deleteProduct($pdo, $id)) {
        $message = "Товар удалён.";
    } else {
        $error = "Ошибка удаления.";
    }
    header('Location: admin.php');
    exit;
}

// --- Просмотр заказов ---
$orders = getAllOrders($pdo);
$viewOrderId = isset($_GET['view_order']) ? (int)$_GET['view_order'] : null;
$orderItems = null;
if ($viewOrderId) {
    $orderItems = getOrderItemsWithProducts($pdo, $viewOrderId);
}

// --- Список товаров для редактирования ---
$products = getAllProducts($pdo);
$editProduct = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $editProduct = getProductById($pdo, $editId);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ панель - Магазин обуви</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Административная панель</h1>
    <div class="nav">
        <a href="index.php">Главная</a> | <a href="cart.php">Корзина</a> | <a href="admin.php">Админка</a>
    </div>

    <?php if ($message): ?><div class="success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <h2>Управление товарами</h2>
    <!-- Форма добавления нового товара -->
    <h3>Добавить товар</h3>
    <form method="post">
        <input type="text" name="name" placeholder="Название" required>
        <input type="number" step="0.01" name="price" placeholder="Цена" required>
        <input type="text" name="size" placeholder="Размер (например 42)" required>
        <input type="number" name="stock" placeholder="Количество" required>
        <button type="submit" name="add_product">Добавить</button>
    </form>

    <!-- Список товаров с возможностью редактирования и удаления -->
    <h3>Список товаров</h3>
    <table>
        <thead><tr><th>ID</th><th>Название</th><th>Цена</th><th>Размер</th><th>Остаток</th><th>Действия</th></tr></thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= number_format($p['price'], 2) ?> руб.</td>
                <td><?= htmlspecialchars($p['size']) ?></td>
                <td><?= $p['stock'] ?></td>
                <td>
                    <a href="?edit_id=<?= $p['id'] ?>">Редактировать</a> |
                    <a href="?delete_id=<?= $p['id'] ?>" onclick="return confirm('Удалить товар?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Форма редактирования товара (появляется при edit_id) -->
    <?php if ($editProduct): ?>
        <h3>Редактирование товара</h3>
        <form method="post">
            <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($editProduct['name']) ?>" required>
            <input type="number" step="0.01" name="price" value="<?= $editProduct['price'] ?>" required>
            <input type="text" name="size" value="<?= htmlspecialchars($editProduct['size']) ?>" required>
            <input type="number" name="stock" value="<?= $editProduct['stock'] ?>" required>
            <button type="submit" name="edit_product">Сохранить изменения</button>
            <a href="admin.php">Отмена</a>
        </form>
    <?php endif; ?>

    <h2>Заказы</h2>
    <table>
        <thead><tr><th>ID заказа</th><th>Имя</th><th>Телефон</th><th>Адрес</th><th>Дата</th><th>Сумма</th></tr></thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><a href="?view_order=<?= $order['id'] ?>"><?= $order['id'] ?></a></td>
                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                <td><?= htmlspecialchars($order['customer_address']) ?></td>
                <td><?= $order['created_at'] ?></td>
                <td><?= number_format($order['total_amount'], 2) ?> руб.</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Детали выбранного заказа -->
    <?php if ($viewOrderId && $orderItems): ?>
        <h3>Состав заказа №<?= $viewOrderId ?></h3>
        <table>
            <thead><tr><th>Товар</th><th>Количество</th><th>Цена за единицу</th><th>Сумма</th></tr></thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 2) ?> руб.</td>
                    <td><?= number_format($item['quantity'] * $item['price'], 2) ?> руб.</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($viewOrderId): ?>
        <p>Заказ не найден.</p>
    <?php endif; ?>
</div>
</body>
</html>