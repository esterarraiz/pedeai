<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Cozinha - PedeAI</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link rel="stylesheet" href="/css/style.css"> 
    
    <link rel="stylesheet" href="/css/cozinha.css"> 
    
    </head>
<body>
    <div class="dashboard-container">
        
        <?php 
          // Carrega o partial da sidebar
          include_once __DIR__ . '/../partials/sidebar_cozinha.php'; 
        ?>

        <main class="main-content">
            
            <h1>Cozinha</h1>

            <div id="pedidos-container" class="kitchen-grid">
                
                <?php if (empty($pedidos)): ?>
                    <p>Nenhum pedido em preparo no momento.</p>
                <?php else: ?>
                    <?php foreach ($pedidos as $pedido): ?>
                        
                        <div class="kitchen-order-card" data-pedido-id="<?= $pedido['id'] ?>">
                            <div class="kitchen-order-card-header">
                                <h3><?= htmlspecialchars($pedido['mesa']) ?></h3>
                            </div>
                            <div class="kitchen-order-card-body">
                                <ul>
                                    <?php foreach ($pedido['itens'] as $item): ?>
                                        <li>
                                            <?= htmlspecialchars($item['quantidade']) ?>x <?= htmlspecialchars($item['nome']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="kitchen-order-card-footer">
                                <button class="kitchen-btn-ready" data-id="<?= $pedido['id'] ?>">Pronto</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script src="/js/painel-cozinha.js"></script>

</body>
</html>