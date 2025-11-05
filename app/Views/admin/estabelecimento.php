<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Gerenciar Estabelecimento') ?></title>
    
    <!-- Seus links de CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/funcionarios.css">
    <link rel="stylesheet" href="/css/form.css"> 
    <link rel="stylesheet" href="/css/estabelecimento.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Estilos do cardápio que são úteis aqui */
        .modal-content { text-align: left; }
        .table-container {
            width: 100%;
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        /* =============================================
          == CORREÇÃO PARA CENTRALIZAR MODAL (v2) ==
          =============================================
        */
        
   

        .modal.show {
            display: flex !important; /* Aplica o flex SÓ QUANDO o modal está visível */
            justify-content: center;
            align-items: center;
        }

        .modal-dialog {
            margin-left: auto;
            margin-right: auto;
        }
        
        .modal-dialog.modal-dialog-centered {
            min-height: unset; 
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1 style="margin-left: 0; margin-right: auto; text-align: left;">
                    Gerenciar Estabelecimento
                </h1>
            </header>

            <!-- O container de feedback antigo ainda é útil para erros de 'carregarMesas' -->
            <div id="feedback-container"></div>
            
            <div class="card-add-mesas">
                <div class="card-header">
                    <h4>Adicionar Novas Mesas</h4>
                </div>
                <div class="card-body">
                    <p>Gere novas mesas para seu estabelecimento. Elas serão numeradas sequencialmente a partir da última mesa cadastrada.</p>
                    
                    <form id="form-criar-mesas">
                        <div class="form-group-mesas">
                            
                            <!-- Seletor de Quantidade -->
                            <div class="quantity-picker">
                                <button type="button" class="btn-qtd" id="btn-menos" title="Diminuir">-</button>
                                <input type="text" id="quantidade" value="1" min="1" max="100" readonly>
                                <button type="button" class="btn-qtd" id="btn-mais" title="Aumentar">+</button>
                            </div>
                            
                            <!-- Botão de Envio -->
                            <button type="submit" class="btn btn-success">Gerar Mesas</button>
                        </div>
                        <!-- Para mensagens de erro do formulário -->
                        <div id="form-mensagem" class="mt-2"></div> 
                    </form>
                </div>
            </div>


            <!-- 2. Listagem de Mesas Existentes -->
            <div class="card">
                 <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                    <h4 style="margin: 0; font-size: 1.25rem;">Mesas Cadastradas</h4>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Número da Mesa</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tabela-mesas-corpo">
                                <tr>
                                    <td colspan="3" class="text-center">Carregando mesas...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- 
      =====================================
      == HTML DO MODAL DE SUCESSO ==
      =====================================
    -->
    <div class="modal fade" id="sucessoModal" tabindex="-1" aria-labelledby="sucessoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content">
          <div class="modal-body">
             <div class="text-center p-4">
                <i class="fas fa-check-circle"></i>
                <h4 class="mt-3" id="sucessoModalMensagem">Mesas geradas com sucesso!</h4>
             </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" data-bs-dismiss="modal" id="sucessoModalOkBtn">OK</button>
          </div>
        </div>
      </div>
    </div>
    <!-- ===============================
      == FIM DO HTML DO MODAL ==
      =============================== -->


    <!-- Script do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript da Página (sem alterações) -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- CONSTANTES GLOBAIS ---
        const apiBaseUrl = '/api/estabelecimento';
        const feedbackContainer = document.getElementById('feedback-container');
        
        // Elementos da página
        const tabelaCorpo = document.getElementById('tabela-mesas-corpo');
        const formCriar = document.getElementById('form-criar-mesas');
        const formMensagem = document.getElementById('form-mensagem');

        // Seletor de quantidade
        const inputQtd = document.getElementById('quantidade');
        const btnMenos = document.getElementById('btn-menos');
        const btnMais = document.getElementById('btn-mais');
        let contagemMesas = 1;
        const maxMesas = 100;
        const minMesas = 1;

        // === Variáveis do Modal de Sucesso ===
        const sucessoModalEl = document.getElementById('sucessoModal');
        const sucessoModal = new bootstrap.Modal(sucessoModalEl);
        const sucessoModalMensagem = document.getElementById('sucessoModalMensagem');
        let modalTimer = null; // Timer para fechar o modal


        // --- FUNÇÕES DE RENDERIZAÇÃO ---
        function renderizarMesas(mesas) {
            if (!mesas || mesas.length === 0) {
                tabelaCorpo.innerHTML = '<tr><td colspan="3" class="text-center">Nenhuma mesa cadastrada. Use o formulário acima.</td></tr>';
                return;
            }
            tabelaCorpo.innerHTML = '';
            mesas.sort((a, b) => a.numero - b.numero);
            
            mesas.forEach(mesa => {
                const statusTexto = mesa.status.charAt(0).toUpperCase() + mesa.status.slice(1);
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>Mesa ${mesa.numero}</strong></td>
                    <td>
                        <span class="badge ${mesa.status === 'disponivel' ? 'bg-success' : 'bg-danger'}">
                            ${statusTexto}
                        </span>
                    </td>
                    <td class="actions-cell" style="justify-content: center;">
                        <button class="action-icon btn-remover" title="Remover" 
                                data-id="${mesa.id}" 
                                data-numero="${mesa.numero}" 
                                style="border:none; background:none; cursor:pointer; color: #dc3545;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;
                tabelaCorpo.appendChild(tr);
            });
        }

        // --- FUNÇÃO PRINCIPAL DE CARREGAMENTO ---
        async function carregarMesas() {
            try {
                const response = await fetch(`${apiBaseUrl}/mesas`);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                if (data.success) {
                    renderizarMesas(data.data);
                } else {
                    mostrarFeedback(data.message || 'Não foi possível carregar as mesas.', 'danger');
                }
            } catch (error) {
                console.error('Erro ao carregar mesas:', error);
                mostrarFeedback('Erro de comunicação com o servidor.', 'danger');
            }
        }

        // --- HELPERS (Funções de utilidade) ---

        // Feedback de erro (o de sucesso foi removido)
        function mostrarFeedback(mensagem, tipo = 'danger') {
            const alertClass = (tipo === 'success') ? 'alert-success' : 'alert-danger';
            feedbackContainer.innerHTML = `<div class="alert ${alertClass}">${mensagem}</div>`;
            setTimeout(() => { feedbackContainer.innerHTML = ''; }, 5000);
        }

        function mostrarFeedbackForm(mensagem, tipo = 'danger') {
            const textClass = (tipo === 'success') ? 'text-success' : 'text-danger';
            formMensagem.innerHTML = `<span class="${textClass}">${mensagem}</span>`;
            setTimeout(() => { formMensagem.innerHTML = ''; }, 5000);
        }

        // Seletor de quantidade
        function atualizarSeletor() {
            inputQtd.value = contagemMesas;
            btnMenos.disabled = (contagemMesas <= minMesas);
            btnMais.disabled = (contagemMesas >= maxMesas);
        }

        // --- EVENT LISTENERS (Ações do Usuário) ---

        // Listeners do seletor +/-
        btnMais.addEventListener('click', () => {
            if (contagemMesas < maxMesas) {
                contagemMesas++;
                atualizarSeletor();
            }
        });

        btnMenos.addEventListener('click', () => {
            if (contagemMesas > minMesas) {
                contagemMesas--;
                atualizarSeletor();
            }
        });
        
        // (ADICIONAR) Envio do formulário
        formCriar.addEventListener('submit', async function (e) {
            e.preventDefault();
            const quantidade = contagemMesas; 
            const btn = this.querySelector('button[type="submit"]');
            
            btn.disabled = true;
            btn.innerText = "Processando...";
            formMensagem.innerHTML = '<span class="text-info">Processando...</span>';

            try {
                const response = await fetch(`${apiBaseUrl}/mesas`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ quantidade: quantidade })
                });
                const result = await response.json();

                // === Chamar o Modal ===
                if (response.ok && result.success) {
                    
                    // 1. Atualiza o texto e mostra o modal
                    sucessoModalMensagem.innerText = result.message;
                    sucessoModal.show();
                    
                    // 2. Define o timer de 10 segundos para fechar
                    if (modalTimer) clearTimeout(modalTimer); // Limpa timer antigo
                    modalTimer = setTimeout(() => {
                        sucessoModal.hide();
                    }, 10000); // 10.000 ms = 10 segundos
                    
                    // 3. Recarrega a lista e reseta o formulário
                    carregarMesas();
                    contagemMesas = 1;
                    atualizarSeletor();
                    formMensagem.innerHTML = '';

                } else {
                    mostrarFeedbackForm(result.message || 'Erro ao adicionar.', 'danger');
                }
            } catch (error) {
                console.error('Erro ao adicionar:', error);
                mostrarFeedbackForm('Erro de comunicação ao adicionar.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerText = "Gerar Mesas";
            }
        });
        
        // === Listener para o Modal ===
        // Quando o modal for fechado (pelo botão OK ou pelo timer),
        // nós limpamos o timer para evitar que ele tente fechar um modal já fechado.
        sucessoModalEl.addEventListener('hidden.bs.modal', () => {
            if (modalTimer) {
                clearTimeout(modalTimer);
            }
        });


        // (REMOVER) Delegação de evento
        tabelaCorpo.addEventListener('click', async function(e) {
            const btnRemover = e.target.closest('.btn-remover');
            if (btnRemover) {
                const id = btnRemover.dataset.id;
                const numero = btnRemover.dataset.numero;
                
                if (confirm(`Tem certeza que deseja remover a "Mesa ${numero}"?`)) {
                    btnRemover.disabled = true;
                    btnRemover.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    try {
                        const response = await fetch(`${apiBaseUrl}/mesas/excluir`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ id: id })
                        });
                        const result = await response.json();

                        if (response.ok && result.success) {
                            // Vamos usar o modal de sucesso aqui também!
                            sucessoModalMensagem.innerText = result.message;
                            sucessoModal.show();
                            if (modalTimer) clearTimeout(modalTimer);
                            modalTimer = setTimeout(() => { sucessoModal.hide(); }, 10000);
                            
                            carregarMesas();
                        } else {
                            // Erro ao remover
                            mostrarFeedback(result.message || 'Erro ao remover.', 'danger');
                            btnRemover.disabled = false;
                            btnRemover.innerHTML = '<i class="fas fa-trash-alt"></i>';
                        }
                    } catch (error) {
                        console.error('Erro ao remover:', error);
                        mostrarFeedback('Erro de comunicação ao remover.', 'danger');
                        btnRemover.disabled = false;
                        btnRemover.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    }
                }
            }
        });

        // --- INICIALIZAÇÃO ---
        carregarMesas(); // Carrega as mesas ao iniciar a página
        atualizarSeletor(); // Define o estado inicial dos botões +/-
    });
    </script>
</body>
</html>

