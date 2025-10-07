<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Caixa</title>
<link rel="stylesheet" href="/css/caixa.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="/images/pedeai-logo.png" alt="Logo"></div>
        <ul class="sidebar-nav">
            <li><a href="/dashboard/caixa" class="active"><i class="fa-solid fa-cash-register"></i><span>Caixa</span></a></li>
            <li><a href="/logout"><i class="fa-solid fa-sign-out-alt"></i><span>Sair</span></a></li>
        </ul>
        <div class="sidebar-user">
            <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?></h4>
            <span>Caixa</span>
        </div>
    </aside>
    <main class="main-content">
        <h1>Mesas</h1>
        <div class="mesa-grid">
            <?php foreach($data['mesas'] as $mesa): ?>
                <div class="mesa-card <?= strtolower($mesa['status']) ?>">
                    <h2>Mesa <?= str_pad($mesa['numero'], 2, '0', STR_PAD_LEFT) ?></h2>
                    <span>Status: <?= htmlspecialchars($mesa['status']) ?></span>
                    <a href="/caixa/mesa/<?= $mesa['id'] ?>" class="btn">Ver Conta</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
