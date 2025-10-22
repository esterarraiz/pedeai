<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Mesa <?= htmlspecialchars($data['mesa']['numero']) ?></title>
    <link rel="stylesheet" href="/css/caixa.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
            </div>
            <ul class="sidebar-nav">
                <li><a href="/dashboard/caixa" class="active"><i class="fa-solid fa-chair"></i><span>Mesas</span></a></li>
                <li><a href="/logout"><i class="fa-solid fa-sign-out-alt"></i><span>Sair</span></a></li>
            </ul>
            <div class="sidebar-user">
                <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?></h4>
                <span>Caixa</span>
            </div>
        </aside>
        <main class="main-content">
            <div class="details-header">
                <h1>Mesa <?= str_pad(htmlspecialchars($data['mesa']['numero']), 2, '0', STR_PAD_LEFT) ?></h1>
            </div>

            <?php if (empty($data['pedido'])): ?>
                <div class="order-card"><p>Nenhum pedido ativo para esta mesa.</p></div>
            <?php else: ?>
                <?php $pedido = $data['pedido']; ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <h3>Pedido (#<?= $pedido['id'] ?>)</h3>
                        <span>Status: <strong><?= htmlspecialchars($pedido['status']) ?></strong> | Horário: <?= $pedido['hora'] ?></span>
                    </div>
                    <div class="order-card-body">
                        <ul>
                            <?php foreach ($pedido['itens'] as $item): ?>
                                <li>
                                    <span><?= htmlspecialchars($item['nome']) ?></span>
                                    <span><?= $item['quantidade'] ?>x R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="total-bill">
                    Valor Total: R$ <?= number_format($pedido['total'], 2, ',', '.') ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
