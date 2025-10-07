<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Caixa</title>
<link rel="stylesheet" href="/css/caixa-style.css">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="/images/pedeai-logo.png" alt="Logo PedeAí">
        </div>
        <ul class="sidebar-nav">
            <li><a href="/dashboard/caixa" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
        </ul>
        <div class="sidebar-user">
            <h4><?= $_SESSION['user_nome'] ?? 'Caixa' ?></h4>
            <span><?= $_SESSION['user_cargo'] ?? '' ?></span>
        </div>
    </aside>

    <main class="main-content">
        <h2>Mesas</h2>
        <div class="mesas-container">
            <?php foreach ($mesas as $mesa): ?>
                <div class="mesa-card">
                    <h3><?= htmlspecialchars($mesa['nome']) ?></h3>
                    <p>Status: <?= htmlspecialchars($mesa['status']) ?></p>
                    <a href="/caixa/mesa/<?= $mesa['id'] ?>">Ver Conta</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
