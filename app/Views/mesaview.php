<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Garçom - Mesas</title>
    
    <link rel="stylesheet" href="/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <div class="sidebar-logo">
    <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
</div>
            <ul class="sidebar-nav">
                <li><a href="/dashboard/garcom" class="active"><i class="fa-solid fa-chair"></i><span>Mesas</span></a></li>
                <li><a href="#"><i class="fa-solid fa-receipt"></i><span>Pedidos Atuais</span></a></li>
                <li><a href="/logout"><i class="fa-solid fa-sign-out-alt"></i><span>Sair</span></a></li>
            </ul>
            <div class="sidebar-user">
                <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?></h4>
                <span>Garçom</span>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1>Gerenciamento de Mesas</h1>
            </header>

            <section class="tables-grid">

    <?php if (empty($mesas)): // ALTERADO AQUI ?>
        <p>Nenhuma mesa cadastrada para esta empresa.</p>
    <?php else: ?>

        <?php foreach ($mesas as $mesa): // ALTERADO AQUI ?>
            <?php
                // Lógica simplificada para o novo design
                $statusClass = 'status-livre';
                $statusText = 'Livre';

                if ($mesa['status'] === 'ocupada') {
                    $statusClass = 'status-ocupada';
                    $statusText = 'Ocupada';
                } elseif ($mesa['status'] === 'aguardando_pagamento') {
                    $statusClass = 'status-pagamento'; 
                    $statusText = 'Pagamento';
                }
                
                $linkMesa = (BASE_PATH ?? '') . "/pedidos/mesa/" . $mesa['id'];
            ?>

            <a href="<?= $linkMesa ?>" class="table-card-link">
                <div class="table-card <?= $statusClass ?>">
                    
                    <div>
                        <div class="table-number">
                            <?= str_pad($mesa['numero'], 2, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="table-label">Mesa</div>
                    </div>

                    <span class="status"><?= $statusText ?></span>
                    
                </div>
            </a>
        <?php endforeach; ?>

    <?php endif; ?>

</section>
        </main>

    </div>

</body>
</html>