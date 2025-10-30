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
                        <!-- Os cargos serão carregados aqui via API -->
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
                    <tbody id="funcionarios-table-body">
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">A carregar funcionários...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modais (senha, confirmação) -->
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

    <!-- JavaScript para a SPA -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.getElementById('funcionarios-table-body');
        const searchInput = document.getElementById('searchInput');
        const functionFilter = document.getElementById('functionFilter');
        let funcionariosData = []; // Cache dos dados

        const modalSenha = document.getElementById('modal-senha');
        const formSenha = document.getElementById('form-redefinir-senha');
        const modalConfirmacao = document.getElementById('modal-confirmacao');
        const toast = document.getElementById('toast-notification');
        
        // --- Funções Auxiliares ---
        const apiFetch = async (url, options = {}) => {
            try {
                const response = await fetch(url, options);
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Erro no servidor');
                }
                return data;
            } catch (error) {
                console.error('Erro na API Fetch:', error);
                showToast(error.message, false);
                return null;
            }
        };

        let toastTimeout;
        function showToast(message, isSuccess = true) {
            clearTimeout(toastTimeout);
            toast.className = 'toast-notification';
            toast.querySelector('#toast-message').textContent = message;
            toast.querySelector('#toast-icon').innerHTML = isSuccess ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
            toast.classList.add(isSuccess ? 'success' : 'error');
            setTimeout(() => { toast.classList.add('show'); }, 10);
            toastTimeout = setTimeout(() => { toast.classList.remove('show'); }, 4000);
        }

        const fecharModal = (modal) => {
            modal.style.display = 'none';
        };

        document.querySelectorAll('.btn-cancelar-modal').forEach(btn => {
            btn.addEventListener('click', (e) => fecharModal(e.target.closest('.modal')));
        });

        // --- Lógica Principal ---
        function renderizarTabela(funcionarios) {
            tableBody.innerHTML = '';
            if (!funcionarios || funcionarios.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum funcionário encontrado.</td></tr>';
                return;
            }

            funcionarios.forEach(func => {
                const tr = document.createElement('tr');
                tr.dataset.funcionarioId = func.id;

                const statusBadge = func.ativo
                    ? `<span class="badge-ativo">Ativo</span>`
                    : `<span class="badge-inativo">Inativo</span>`;

                const toggleIcon = func.ativo ? 'fa-ban' : 'fa-check-circle';
                const toggleTitle = func.ativo ? 'Desativar' : 'Ativar';
                
                let initials = '??';
                if (func.nome) {
                    const words = func.nome.split(' ');
                    initials = words[0].charAt(0);
                    if (words.length > 1) {
                        initials += words[words.length - 1].charAt(0);
                    }
                }

                tr.innerHTML = `
                    <td>
                        <div class="user-initials" data-initials="${initials.toUpperCase()}"></div>
                    </td>
                    <td>${func.nome}</td>
                    <td>${func.nome_cargo}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="actions-cell">
                            <a href="/funcionarios/editar/${func.id}" class="action-icon" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                            <span class="action-icon btn-reset-password" title="Redefinir Senha" data-id="${func.id}" data-nome="${func.nome}">
                                <i class="fas fa-key"></i>
                            </span>
                            <span class="action-icon btn-toggle-status" title="${toggleTitle} Usuário" data-id="${func.id}" data-status="${func.ativo ? '1' : '0'}" data-nome="${func.nome}">
                                <i class="fas ${toggleIcon}"></i>
                            </span>
                        </div>
                    </td>
                `;
                tableBody.appendChild(tr);
            });
        }

        function filtrarErenderizar() {
            const searchTerm = searchInput.value.toLowerCase();
            const cargoTerm = functionFilter.value.toLowerCase();

            const filtrados = funcionariosData.filter(func => {
                const nomeMatch = func.nome.toLowerCase().includes(searchTerm);
                const cargoMatch = (cargoTerm === 'todos' || func.nome_cargo.toLowerCase() === cargoTerm);
                return nomeMatch && cargoMatch;
            });
            renderizarTabela(filtrados);
        }

        async function carregarFuncionarios() {
            const data = await apiFetch('/api/funcionarios');
            if (data) {
                funcionariosData = Array.isArray(data) ? data : [];
                filtrarErenderizar();
            }
        }
        
        async function carregarCargos() {
            const cargos = await apiFetch('/api/cargos');
            if (cargos && Array.isArray(cargos)) {
                cargos.forEach(cargo => {
                    const option = document.createElement('option');
                    option.value = cargo.nome_cargo.toLowerCase();
                    option.textContent = cargo.nome_cargo;
                    functionFilter.appendChild(option);
                });
            }
        }

        // --- Ações da Tabela (com delegação de eventos) ---
        tableBody.addEventListener('click', (event) => {
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
                btnConfirmar.className = `btn ${statusAtual ? 'btn-danger' : 'btn-success'}`;
                const newBtn = btnConfirmar.cloneNode(true);
                btnConfirmar.parentNode.replaceChild(newBtn, btnConfirmar);
                newBtn.addEventListener('click', () => executarToggleStatus(id, !statusAtual));
                modalConfirmacao.style.display = 'flex';
            }
        });

        async function executarToggleStatus(id, novoStatus) {
            const response = await apiFetch('/api/funcionarios/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: novoStatus })
            });
            fecharModal(modalConfirmacao);
            if (response?.success) {
                showToast('Status alterado com sucesso!');
                carregarFuncionarios(); // Recarrega os dados
            }
        }

        formSenha.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = document.getElementById('modal-funcionario-id-senha').value;
            const senha = document.getElementById('nova_senha').value;
            const response = await apiFetch('/api/funcionarios/redefinir-senha', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, senha: senha })
            });
            if (response?.success) {
                showToast(response.message);
                fecharModal(modalSenha);
            }
        });

        // --- Carregamento Inicial ---
        searchInput.addEventListener('input', filtrarErenderizar);
        functionFilter.addEventListener('change', filtrarErenderizar);
        
        carregarFuncionarios(); // Carga inicial dos funcionários
        carregarCargos(); // Carga inicial dos cargos para o filtro
    });
    </script>
</body>
</html>

