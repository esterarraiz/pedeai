<?php
// A variável $activePage deve ser definida no controller que carrega a view.
// Ex: $this->loadView('cozinha/painel', ['activePage' => 'cozinha']);
$activePage = $data['activePage'] ?? 'cozinha'; // 'cozinha' como padrão
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="/cozinha" class="<?= ($activePage === 'cozinha') ? 'active' : '' ?>">
                <i class="fa-solid fa-kitchen-set"></i><span>Cozinha</span>
            </a>
        </li>
        
        <li>
            <a href="/logout">
                <i class="fa-solid fa-sign-out-alt"></i><span>Sair</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-user">
        <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Equipe') ?></h4>
        <span>Equipe Cozinha</span>
    </div>
</aside>