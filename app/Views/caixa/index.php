<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Caixa - Contas Abertas</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_caixa.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Contas Abertas</h1>
            </header>

            <section class="tables-grid">
                <?php if (empty($data['mesas'])): ?>
                    <p style="text-align: center; color: var(--text-light); font-size: 1.1rem; margin-top: 40px;">Nenhuma mesa com conta aberta no momento.</p>
                <?php else: ?>
                    <?php foreach ($data['mesas'] as $mesa): ?>
                        <?php
                            $statusClass = '';
                            $statusText = '';
                            if ($mesa['status'] === 'ocupada') {
                                $statusClass = 'status-ocupada';
                                $statusText = 'Consumindo';
                            } elseif ($mesa['status'] === 'aguardando_pagamento') {
                                $statusClass = 'status-pagamento'; 
                                $statusText = 'Aguardando Pagamento';
                            }
                        ?>

                        <a href="/caixa/conta/<?= $mesa['id'] ?>" class="table-card-link">
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

