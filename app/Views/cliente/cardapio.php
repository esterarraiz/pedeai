<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio - <?= htmlspecialchars($data['empresa']['nome_empresa']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/cardapio_publico.css">
</head>
<body>
    <div class="menu-container">
        <header class="menu-header">
            <h1><?= htmlspecialchars($data['empresa']['nome_empresa']) ?></h1>
        </header>

        <main class="menu-content">
            <?php if (empty($data['cardapio'])): ?>
                <p style="text-align: center; padding: 30px;">Este estabelecimento ainda não publicou o seu cardápio.</p>
            <?php else: ?>
                <?php foreach ($data['cardapio'] as $categoria => $itens): ?>
                    <section class="category-group">
                        <h2><?= htmlspecialchars($categoria) ?></h2>
                        <?php foreach ($itens as $item): ?>
                            <div class="menu-item">
                                <div class="menu-item-details">
                                    <h4><?= htmlspecialchars($item['nome']) ?></h4>
                                    <p><?= htmlspecialchars($item['descricao']) ?></p>
                                </div>
                                <div class="menu-item-price">
                                    R$ <?= number_format($item['preco'], 2, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
        
        <footer class="menu-footer">
            <a href="/cardapio/<?= $data['empresa']['id'] ?>/pdf" class="btn btn-secondary" target="_blank">
                Baixar Cardápio em PDF
            </a>
        </footer>
    </div>
</body>
</html>