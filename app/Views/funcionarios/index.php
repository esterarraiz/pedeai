<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários</title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/funcionarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>
        
        <main class="main-content">
            <header class="main-header">
                <h1>Gerenciamento de Usuários</h1>
                <a href="/funcionarios/novo" class="btn btn-success"><i class="fas fa-plus"></i>Novo usuário</a>
            </header>

            <div class="filters-bar">
                <div class="search-container">
                    <input type="search" id="searchInput" placeholder="Pesquisar por nome....">
                </div>
                <div class="select-container">
                    <select id="functionFilter">
                        <option value="todos">Função: Todos</option>
                        <option value="administrador">Administrador</option>
                        <option value="garçom">Garçom</option>
                        <option value="cozinheiro">Cozinheiro</option>
                        <option value="caixa">Caixa</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Foto</th>
                            <th>Nome</th>
                            <th>Função</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['funcionarios'] as $func): ?>
                            <tr data-funcionario-id="<?= $func['id'] ?>">
                                <td>
                                    <div class="user-initials">
                                        <?php
                                            $name = $func['nome'];
                                            $words = explode(' ', $name);
                                            $initials = mb_substr($words[0], 0, 1);
                                            if (count($words) > 1) {
                                                $initials .= mb_substr(end($words), 0, 1);
                                            }
                                            echo htmlspecialchars(strtoupper($initials));
                                        ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($func['nome']) ?></td>
                                <td><?= htmlspecialchars($func['nome_cargo']) ?></td>
                                <td>
                                    <?php if ($func['ativo']): ?>
                                        <span class="badge-ativo">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge-inativo">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="/funcionarios/editar/<?= $func['id'] ?>" class="action-icon" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                        <span class="action-icon btn-reset-password" title="Redefinir Senha" 
                                              data-id="<?= $func['id'] ?>" 
                                              data-nome="<?= htmlspecialchars($func['nome']) ?>">
                                            <i class="fas fa-key"></i>
                                        </span>
                                        <span class="action-icon btn-toggle-status" title="<?= $func['ativo'] ? 'Desativar' : 'Ativar' ?> Usuário" 
                                              data-id="<?= $func['id'] ?>" 
                                              data-status="<?= $func['ativo'] ? '1' : '0' ?>"
                                              data-nome="<?= htmlspecialchars($func['nome']) ?>">
                                            <i class="fas fa-ban"></i>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modais e Toast -->
    <div id="modal-senha" class="modal">
        <div class="modal-content">
            <h2>Redefinir Senha</h2>
            <p>Digite a nova senha para <strong id="modal-nome-funcionario-senha"></strong>:</p>
            <form id="form-redefinir-senha">
                <input type="hidden" id="modal-funcionario-id-senha" name="id">
                <div class="form-group" style="text-align: left;">
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="senha" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary btn-cancelar-modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar Nova Senha</button>
                </div>
            </form>
        </div>
    </div>
    <div id="modal-confirmacao" class="modal">
        <div class="modal-content">
            <h2 id="modal-confirmacao-titulo"></h2>
            <p id="modal-confirmacao-texto"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-cancelar-modal">Cancelar</button>
                <button type="button" class="btn" id="btn-confirmar-acao">Sim, continuar</button>
            </div>
        </div>
    </div>
    <div id="toast-notification" class="toast-notification">
        <span id="toast-icon"></span>
        <span id="toast-message"></span>
        <button class="close-toast" onclick="this.parentElement.classList.remove('show')">&times;</button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const functionFilter = document.getElementById('functionFilter');
        const tableRows = document.querySelectorAll('.data-table tbody tr');
        const modalSenha = document.getElementById('modal-senha');
        const formSenha = document.getElementById('form-redefinir-senha');
        const modalConfirmacao = document.getElementById('modal-confirmacao');
        const toast = document.getElementById('toast-notification');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const functionValue = functionFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const nomeCell = row.cells[1].textContent.toLowerCase();
                const funcaoCell = row.cells[2].textContent.toLowerCase();

                const searchMatch = nomeCell.includes(searchTerm);
                const functionMatch = (functionValue === 'todos' || funcaoCell.includes(functionValue));

                if (searchMatch && functionMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (functionFilter) functionFilter.addEventListener('change', filterTable);
        
        let toastTimeout;
        function showToast(message, isSuccess = true) {
            clearTimeout(toastTimeout);
            toast.className = 'toast-notification';
            toast.querySelector('#toast-message').textContent = message;
            toast.querySelector('#toast-icon').innerHTML = isSuccess ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
            toast.classList.add(isSuccess ? 'success' : 'error');
            
            setTimeout(() => { toast.classList.add('show'); }, 10);

            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        const fecharModal = (modal) => {
            modal.style.display = 'none';
        };

        document.querySelectorAll('.btn-cancelar-modal').forEach(btn => {
            btn.addEventListener('click', (e) => fecharModal(e.target.closest('.modal')));
        });

        document.querySelector('.data-table tbody').addEventListener('click', function(event) {
            const target = event.target.closest('.action-icon');
            if (!target) return;

            const id = target.dataset.id;
            const nome = target.dataset.nome;

            if (target.classList.contains('btn-reset-password')) {
                document.getElementById('modal-nome-funcionario-senha').textContent = nome;
                document.getElementById('modal-funcionario-id-senha').value = id;
                modalSenha.style.display = 'flex';
            }
            
            if (target.classList.contains('btn-toggle-status')) {
                const statusAtual = target.dataset.status === '1';
                const acao = statusAtual ? 'desativar' : 'ativar';
                
                document.getElementById('modal-confirmacao-titulo').textContent = `${acao.charAt(0).toUpperCase() + acao.slice(1)} Utilizador`;
                document.getElementById('modal-confirmacao-texto').textContent = `Tem a certeza que deseja ${acao} o utilizador ${nome}?`;
                
                const btnConfirmar = document.getElementById('btn-confirmar-acao');
                btnConfirmar.classList.remove('btn-success', 'btn-danger');
                btnConfirmar.classList.add(statusAtual ? 'btn-danger' : 'btn-success');

                const newBtn = btnConfirmar.cloneNode(true);
                btnConfirmar.parentNode.replaceChild(newBtn, btnConfirmar);

                newBtn.addEventListener('click', function() {
                    executarToggleStatus(id, !statusAtual);
                });
                
                modalConfirmacao.style.display = 'flex';
            }
        });

        function executarToggleStatus(id, novoStatus) {
            fetch('/funcionarios/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: novoStatus })
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (ok) {
                    showToast(data.message || 'Estado alterado com sucesso!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message || 'Erro ao alterar o estado.', false);
                }
            })
            .catch(err => showToast('Erro de comunicação com o servidor.', false))
            .finally(() => fecharModal(modalConfirmacao));
        }

        formSenha.addEventListener('submit', function(event) {
            event.preventDefault();
            const id = document.getElementById('modal-funcionario-id-senha').value;
            const senha = document.getElementById('nova_senha').value;

            fetch('/funcionarios/redefinir-senha', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, senha: senha })
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (ok) {
                    showToast(data.message || 'Senha redefinida com sucesso!');
                    fecharModal(modalSenha);
                } else {
                    showToast(data.message || 'Não foi possível redefinir a senha.', false);
                }
            })
            .catch(err => showToast('Erro de comunicação com o servidor.', false));
        });
    });
    </script>
</body>
</html>

