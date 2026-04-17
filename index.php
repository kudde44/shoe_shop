<?php
// index.php – каталог товаров (главная страница)
require_once 'functions.php';

// Обработка добавления в корзину
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'], $_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    addToCart($productId, 1);
    header('Location: index.php');
    exit;
}

$products = getAllProducts($pdo);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Магазин обуви</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Каталог обуви</h1>
    <div class="nav">
        <a href="index.php">Главная</a> | <a href="cart.php">Корзина</a> | <a href="admin.php">Админка</a>
    </div>
    <?php if (empty($products)): ?>
        <p>Товаров пока нет.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Название</th><th>Цена</th><th>Размер</th><th>Остаток</th><th>Действие</th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= number_format($product['price'], 2) ?> руб.</td>
                        <td><?= htmlspecialchars($product['size']) ?></td>
                        <td><?= (int)$product['stock'] ?></td>
                        <td>
                            <?php if ($product['stock'] > 0): ?>
                                <form method="post" style="margin:0">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="add_to_cart">Добавить в корзину</button>
                                </form>
                            <?php else: ?>
                                <span style="color:gray">Нет в наличии</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>