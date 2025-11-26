<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard-admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        .modal {
            display: none; position: fixed; z-index: 1000;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center; align-items: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background-color: #fff; padding: 30px;
            border-radius: 12px; width: 90%; max-width: 500px;
            box-shadow: var(--shadow-md);
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px; margin-bottom: 20px;
        }
        .modal-header h3 { margin: 0; font-size: 1.5rem; }
        .modal-header .close-btn {
            font-size: 2rem; color: var(--text-light);
            cursor: pointer; line-height: 1;
        }
        .modal-body ul { list-style: none; padding: 0; }
        .modal-body li {
            display: flex; justify-content: space-between;
            padding: 8px 0; border-bottom: 1px dashed var(--border-color);
        }
        .modal-body .total {
            font-size: 1.2rem; font-weight: 700;
            text-align: right; margin-top: 15px;
        }
        #modal-pedido-status { margin-left: 15px; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Dashboard</h1>
            </header>

            <section class="metrics-grid">
                <div class="metric-card faturamento">
                    <h3>Faturamento do dia</h3>
                    <div class="value">
                        <span id="faturamento-dia">R$ 0,00</span>
                        <span class="icon fas fa-dollar-sign"></span>
                    </div>
                </div>
                <div class="metric-card pedidos">
                    <h3>Pedidos em andamento</h3>
                    <div class="value" id="pedidos-andamento">0</div>
                </div>
                <div class="metric-card mesas">
                    <h3>Mesas Ocupadas</h3>
                    <div class="value" id="mesas-ocupadas">0/0</div>
                </div>
            </section>

            <section class="recent-orders-section">
                <h2>Pedidos em Andamento</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mesa</th>
                            <th>Garçom</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="pedidos-recentes-tbody">
                        <tr><td colspan="5" style="text-align: center;">Carregando...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="modal-detalhes-pedido" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes do Pedido</h3>
                <span class="close-btn">&times;</span>
            </div>
            <div class="modal-body">
                <p><strong>Mesa:</strong> <span id="modal-pedido-mesa">--</span>
                   <span id="modal-pedido-status"></span>
                </p>
                <ul id="modal-pedido-itens">
                    </ul>
                <div class="total">
                    Total: <span id="modal-pedido-total">R$ 0,00</span>
                </div>
            </div>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do Dashboard
    const faturamentoDiaEl = document.getElementById('faturamento-dia');
    const pedidosAndamentoEl = document.getElementById('pedidos-andamento');
    const mesasOcupadasEl = document.getElementById('mesas-ocupadas');
    const pedidosRecentesTbody = document.getElementById('pedidos-recentes-tbody');

    // **** (NOVOS) Elementos do Modal ****
    const modal = document.getElementById('modal-detalhes-pedido');
    const modalCloseBtn = modal.querySelector('.close-btn');
    const modalMesa = document.getElementById('modal-pedido-mesa');
    const modalStatus = document.getElementById('modal-pedido-status');
    const modalItens = document.getElementById('modal-pedido-itens');
    const modalTotal = document.getElementById('modal-pedido-total');

    // Mapeamento de status
    const statusMap = {
        'em_preparo': { text: 'Na Cozinha', class: 'badge-cozinha' },
        'pronto': { text: 'Pronto', class: 'badge-servido' },
        'entregue': { text: 'Servido', class: 'badge-servido' },
        'pago': { text: 'Pago', class: 'badge-pago' }
    };
    
    function formatCurrency(value) {
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    // Função principal de atualização do Dashboard
    async function atualizarDashboard() {
        try {
            const response = await fetch('/api/admin/dashboard');
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message);

            const data = result.data;
            const metricas = data.metricas;
            faturamentoDiaEl.textContent = formatCurrency(metricas.faturamento_dia);
            pedidosAndamentoEl.textContent = metricas.pedidos_andamento;
            mesasOcupadasEl.textContent = `${metricas.mesas_ocupadas}/${metricas.total_mesas}`;

            const pedidos = data.pedidos_recentes;
            pedidosRecentesTbody.innerHTML = ''; 

            if (pedidos.length === 0) {
                pedidosRecentesTbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum pedido em andamento.</td></tr>';
            } else {
                pedidos.forEach(pedido => {
                    const statusInfo = statusMap[pedido.status] || { text: pedido.status, class: '' };
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${String(pedido.mesa_numero).padStart(2, '0')}</td>
                        <td>${pedido.garcom_nome}</td>
                        <td>${formatCurrency(parseFloat(pedido.valor_total))}</td>
                        <td><span class="badge ${statusInfo.class}">${statusInfo.text}</span></td>
                        <td>
                            <a href="#" class="action-link btn-ver-detalhes" data-id="${pedido.pedido_id}">
                                Ver Detalhes
                            </a>
                        </td>
                    `;
                    pedidosRecentesTbody.appendChild(tr);
                });
            }

        } catch (error) {
            console.error("Erro ao atualizar o dashboard:", error);
            pedidosRecentesTbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Erro ao carregar dados.</td></tr>`;
        }
    }

    // **** (NOVO) Função para abrir o Modal ****
    async function abrirModalDetalhes(pedidoId) {
        // Limpa o modal
        modalMesa.textContent = '--';
        modalStatus.innerHTML = '';
        modalItens.innerHTML = '<li>Carregando...</li>';
        modalTotal.textContent = formatCurrency(0);
        modal.classList.add('show');

        try {
            // 1. Busca os dados na nova API
            const response = await fetch(`/api/admin/pedidos/${pedidoId}`);
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message);

            const pedido = result.data;
            
            // 2. Preenche o modal
            modalMesa.textContent = String(pedido.mesa_numero).padStart(2, '0');
            modalTotal.textContent = formatCurrency(pedido.total);

            // Status
            const statusInfo = statusMap[pedido.status] || { text: pedido.status, class: '' };
            modalStatus.innerHTML = `<span class="badge ${statusInfo.class}">${statusInfo.text}</span>`;
            
            // Itens
            modalItens.innerHTML = '';
            pedido.itens.forEach(item => {
                modalItens.innerHTML += `
                    <li>
                        <span>${item.quantidade}x ${item.nome}</span>
                        <span>${formatCurrency(item.quantidade * item.preco_unitario)}</span>
                    </li>
                `;
            });

        } catch (error) {
            console.error("Erro ao buscar detalhes do pedido:", error);
            modalItens.innerHTML = `<li><span style="color: red;">Erro ao carregar itens.</span></li>`;
        }
    }

    // **** (NOVOS) Listeners para o Modal e Tabela ****

    // Listener na tabela para capturar cliques nos botões "Ver Detalhes"
    pedidosRecentesTbody.addEventListener('click', function(event) {
        event.preventDefault(); // Impede que o link (href="#") mude o URL
        const target = event.target.closest('.btn-ver-detalhes');
        
        if (target) {
            const pedidoId = target.dataset.id;
            abrirModalDetalhes(pedidoId);
        }
    });

    // Função para fechar o modal
    const fecharModal = () => modal.classList.remove('show');
    modalCloseBtn.addEventListener('click', fecharModal);
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            fecharModal();
        }
    });

    // Inicia o dashboard
    atualizarDashboard();
    setInterval(atualizarDashboard, 15000);
});
</script>

</body>
</html>