<?php
// A variável $activePage deve ser definida no controller que carrega a view.
// Ex: $this->loadView('dashboard/admin/index', ['activePage' => 'home']);
$activePage = $data['activePage'] ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
    </div>
    <ul class="sidebar-nav">
        <li>
            <a href="/dashboard/admin" class="<?= ($activePage === 'home') ? 'active' : '' ?>">
                <i class="fa-solid fa-home"></i><span>Home</span>
            </a>
        </li>
        <li>
            <a href="/relatorios/vendas" class="<?= ($activePage === 'relatorios') ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i><span>Relatórios de Vendas</span>
            </a>
        </li>
        <li>
            <a href="/estabelecimento" class="<?= ($activePage === 'estabelecimento') ? 'active' : '' ?>">
                <i class="fa-solid fa-store"></i><span>Estabelecimento</span>
            </a>
        </li>
        <li>
            <a href="/dashboard/admin/cardapio" class="<?= ($activePage === 'cardapio') ? 'active' : '' ?>">
                <i class="fa-solid fa-book-open"></i><span>Gerenciamento de Cardápio</span>
            </a>
        </li>
        <li>
            <a href="/funcionarios" class="<?= ($activePage === 'funcionarios') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i><span>Gerenciamento de Usuários</span>
            </a>
        </li>
        <li>
            <a href="/suporte" class="<?= ($activePage === 'suporte') ? 'active' : '' ?>">
                <i class="fa-solid fa-headset"></i><span>Suporte</span>
            </a>
        </li>
        <li>
            <a href="/logout">
                <i class="fa-solid fa-sign-out-alt"></i><span>Sair</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-user">
        <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Admin') ?></h4>
        <span>Administrador</span>
    </div>
</aside>
