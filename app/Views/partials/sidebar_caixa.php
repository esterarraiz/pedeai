<?php
$activePage = $data['activePage'] ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
    </div>
    <ul class="sidebar-nav">
        <li>
            <a href="/dashboard/caixa" class="<?= ($activePage === 'mesas') ? 'active' : '' ?>">
                <i class="fa-solid fa-cash-register"></i><span>Contas Abertas</span>
            </a>
        </li>
        <li>
            <a href="/logout">
                <i class="fa-solid fa-sign-out-alt"></i><span>Sair</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-user">
        <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Caixa') ?></h4>
        <span>Caixa</span>
    </div>
</aside>
