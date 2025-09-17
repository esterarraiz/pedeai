<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Cozinha</title>
    
    <link rel="stylesheet" href="/css/style.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content" style="margin-left: 0; padding: 32px;">
            <header class="main-header">
                <h1>Painel da Cozinha</h1>
                
                <a href="/logout" class="btn btn-logout">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </header>

            <div id="pedidos-container" class="kitchen-grid">
                
                <?php if (empty($pedidos)): ?>
                    <p>Nenhum pedido em preparo no momento.</p>
                <?php else: ?>
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <h3><?= htmlspecialchars($pedido['mesa']) ?></h3>
                                <span><?= htmlspecialchars($pedido['hora']) ?></span>
                            </div>
                            <div class="order-card-body">
                                <ul>
                                    <?php foreach ($pedido['itens'] as $item): ?>
                                    <li>
                                        <span><?= htmlspecialchars($item['nome']) ?></span>
                                        <span class="quantity">x<?= htmlspecialchars($item['quantidade']) ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="order-card-footer">
                                <button class="btn-ready" data-id="<?= $pedido['id'] ?>">Marcar como Pronto</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>