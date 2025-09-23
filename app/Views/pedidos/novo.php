<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançar Pedido - Mesa <?= htmlspecialchars($mesa_id) ?></title>
    <link rel="stylesheet" href="/css/style.css"> <style>
        /* Estilos básicos para o formulário (pode mover para style.css) */
        .categoria { margin-bottom: 20px; border: 1px solid #eee; padding: 15px; border-radius: 8px; }
        .item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .item:last-child { border-bottom: none; }
        .item input { width: 60px; text-align: center; }
        .item .info { flex-grow: 1; margin-right: 15px; }
        .item .info p { margin: 5px 0 0; font-size: 0.9em; color: #666; }
        .btn-submit { background-color: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
    </style>
</head>
<body>

    <h1>Lançar Pedido para a Mesa <?= htmlspecialchars($mesa_id) ?></h1>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <form action="/pedidos/criar" method="POST">
        <input type="hidden" name="mesa_id" value="<?= htmlspecialchars($mesa_id) ?>">

        <?php if (empty($cardapio)): ?>
            <p>Não foi possível carregar o cardápio.</p>
        <?php else: ?>
            <?php foreach ($cardapio as $categoria => $itens): ?>
                <div class="categoria">
                    <h2><?= htmlspecialchars($categoria) ?></h2>
                    <?php foreach ($itens as $item): ?>
                        <div class="item">
                            <div class="info">
                                <strong><?= htmlspecialchars($item['nome']) ?></strong>
                                <small>(R$ <?= number_format($item['preco'], 2, ',', '.') ?>)</small>
                                <p><?= htmlspecialchars($item['descricao']) ?></p>
                            </div>
                            <div class="quantidade">
                                <label for="item-<?= $item['id'] ?>">Qtd:</label>
                                <input type="number" id="item-<?= $item['id'] ?>" name="itens[<?= $item['id'] ?>]" min="0" value="0">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <br>
        <button type="submit" class="btn-submit">Lançar Pedido</button>
    </form>

</body>
</html>