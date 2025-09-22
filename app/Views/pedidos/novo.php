<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançar Pedido - Mesa <?php echo $data['mesa_id']; ?></title>
    <style>
        /* Estilos básicos para o formulário */
        body { font-family: sans-serif; }
        .categoria { margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; }
        .item { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; }
        .item input { width: 60px; }
        .item .info { flex-grow: 1; }
    </style>
</head>
<body>

    <h1>Lançar Pedido para a Mesa <?php echo htmlspecialchars($data['mesa_id']); ?></h1>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <form action="/pedidos/processar" method="POST">
        <input type="hidden" name="mesa_id" value="<?php echo htmlspecialchars($data['mesa_id']); ?>">

        <?php foreach ($data['cardapio'] as $categoria => $itens): ?>
            <div class="categoria">
                <h2><?php echo htmlspecialchars($categoria); ?></h2>
                <?php foreach ($itens as $item): ?>
                    <div class="item">
                        <div class="info">
                            <strong><?php echo htmlspecialchars($item['nome']); ?></strong>
                            <small>(R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?>)</small>
                            <p><?php echo htmlspecialchars($item['descricao']); ?></p>
                        </div>
                        <div class="quantidade">
                            <label for="item-<?php echo $item['id']; ?>">Qtd:</label>
                            <input type="number" id="item-<?php echo $item['id']; ?>" name="itens[<?php echo $item['id']; ?>]" min="0" value="0">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit">Lançar Pedido</button>
    </form>

</body>
</html>