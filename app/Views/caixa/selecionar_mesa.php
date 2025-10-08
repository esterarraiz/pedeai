<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Caixa' ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/caixa.css">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
        </div>
        <ul class="sidebar-nav">
            <li><a href="#"><i class="fa-solid fa-home"></i><span>Home</span></a></li>
            <li><a href="/dashboard/caixa" class="active"><i class="fa-solid fa-chair"></i><span>Mesas e Pedidos</span></a></li>
            <li><a href="#"><i class="fa-solid fa-dollar-sign"></i><span>Pagamento</span></a></li>
            <li><a href="#"><i class="fa-solid fa-question-circle"></i><span>Suporte</span></a></li>
            <li><a href="/logout"><i class="fa-solid fa-sign-out-alt"></i><span>Sair</span></a></li>
        </ul>
        <div class="sidebar-user">
            <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?></h4>
            <span>Caixa</span>
        </div>
    </aside>
    <main class="main-content">
        <div class="caixa-card">
            <h1>Selecione uma mesa</h1>
            <div class="mesa-list">
                <?php if (empty($mesas)): ?>
                    <p>Nenhuma mesa ocupada no momento.</p>
                <?php else: ?>
                    <?php foreach ($mesas as $mesa): ?>
                        <a href="/caixa/conta/<?= $mesa['id'] ?>" class="mesa-item">
                            Mesa <?= str_pad($mesa['numero'], 2, '0', STR_PAD_LEFT) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>