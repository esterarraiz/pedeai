<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Relatórios') ?></title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard-admin.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        .filters-bar {
            display: flex;
            gap: 16px;
            align-items: center;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .filters-bar .form-group {
            margin-bottom: 0;
            flex-grow: 1;
        }
        .filters-bar label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-light);
        }
        .filters-bar input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: var(--blue-action);
            color: white;
            padding: 12px 20px;
        }
        .btn-primary:hover {
            background-color: #0ea5e9;
        }
        
        .report-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            align-items: flex-start;
        }
        
        .main-report-column { display: flex; flex-direction: column; gap: 24px; }
        .sidebar-report-column { position: sticky; top: 24px; }

        .report-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }
        .report-card h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        
        /* Estilos para o sumário */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .summary-card {
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
        }
        .summary-card h4 {
            margin: 0 0 8px 0;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .summary-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        /* Tabela de Transações */
        .data-table th, .data-table td { padding: 12px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }

        /* Lista de Itens Mais Vendidos */
        .top-items-list { list-style: none; padding: 0; }
        .top-items-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed var(--border-color);
        }
        .top-items-list li:last-child { border-bottom: none; }
        .top-items-list .item-name { font-weight: 500; }
        .top-items-list .item-count {
            font-weight: 700;
            background: var(--green-light);
            color: var(--green-dark);
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
            font-size: 1.2rem;
            color: var(--text-dark);
        }
        .report-card { position: relative; }
        
    </style>
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Relatórios de Vendas</h1>
            </header>

            <div classs="filters-bar">
                <form id="filter-form" class="filters-bar">
                    <div class="form-group">
                        <label for="data_inicio">Data de Início</label>
                        <input type="date" id="data_inicio" name="data_inicio">
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Data de Fim</label>
                        <input type="date" id="data_fim" name="data_fim">
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self: flex-end;">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>

            <div class="report-grid">
                
                <div class="main-report-column">
                    <div class="report-card" id="sumario-card">
                        <h2>Sumário do Período</h2>
                        <div classid="sumario-container" class="summary-grid">
                            <div class="summary-card">
                                <h4>Faturamento Total</h4>
                                <div class="value" id="sumario-faturamento">R$ 0,00</div>
                            </div>
                            <div class="summary-card">
                                <h4>Ticket Médio</h4>
                                <div class="value" id="sumario-ticket-medio">R$ 0,00</div>
                            </div>
                            <div class="summary-card">
                                <h4>Total de Pedidos</h4>
                                <div class="value" id="sumario-total-pedidos">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-card" id="transacoes-card">
                        <h2>Transações</h2>
                        <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th class="text-center">Pedido ID</th>
                                        <th class="text-center">Mesa</th>
                                        <th>Funcionário</th>
                                        <th>Método</th>
                                        <th class="text-end">Valor Pago</th>
                                    </tr>
                                </thead>
                                <tbody id="transacoes-table-body">
                                    <tr><td colspan="6" class="text-center">Nenhum dado encontrado.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-report-column">
                    <div class="report-card" id="top-itens-card">
                        <h2>Itens Mais Vendidos</h2>
                        <ul class="top-items-list" id="top-itens-list">
                            <li>Nenhum item vendido.</li>
                        </ul>
                    </div>
                </div>

            </div>
            
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('filter-form');
        const dataInicioInput = document.getElementById('data_inicio');
        const dataFimInput = document.getElementById('data_fim');

        // Elementos do Sumário
        const sumarioFaturamento = document.getElementById('sumario-faturamento');
        const sumarioTicketMedio = document.getElementById('sumario-ticket-medio');
        const sumarioTotalPedidos = document.getElementById('sumario-total-pedidos');

        // Elementos das Tabelas
        const transacoesTableBody = document.getElementById('transacoes-table-body');
        const topItensList = document.getElementById('top-itens-list');
        
        // Elementos dos Cards (para loading)
        const sumarioCard = document.getElementById('sumario-card');
        const transacoesCard = document.getElementById('transacoes-card');
        const topItensCard = document.getElementById('top-itens-card');

        // Define as datas padrão para hoje
        const hoje = new Date().toISOString().split('T')[0];
        dataInicioInput.value = hoje;
        dataFimInput.value = hoje;

        // --- FUNÇÕES DE RENDERIZAÇÃO ---
        
        function formatCurrency(value) {
            return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }
        
        function showLoading(cardElement) {
            const loading = document.createElement('div');
            loading.className = 'loading-overlay';
            loading.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp; Carregando...';
            cardElement.appendChild(loading);
        }
        
        function hideLoading(cardElement) {
            const loading = cardElement.querySelector('.loading-overlay');
            if (loading) {
                cardElement.removeChild(loading);
            }
        }

        function renderSumario(sumario) {
            sumarioFaturamento.textContent = formatCurrency(sumario.faturamento_total);
            sumarioTicketMedio.textContent = formatCurrency(sumario.ticket_medio);
            sumarioTotalPedidos.textContent = sumario.total_pedidos;
        }

        function renderTransacoes(transacoes) {
            transacoesTableBody.innerHTML = '';
            if (transacoes.length === 0) {
                transacoesTableBody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhuma transação encontrada para este período.</td></tr>';
                return;
            }
            
            transacoes.forEach(t => {
                const dataPagamento = new Date(t.data_pagamento);
                const dataFormatada = dataPagamento.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${dataFormatada}</td>
                    <td class="text-center">${t.pedido_id}</td>
                    <td class="text-center">${String(t.mesa_numero).padStart(2, '0')}</td>
                    <td>${t.funcionario_nome}</td>
                    <td>${t.metodo_pagamento}</td>
                    <td class="text-end">${formatCurrency(parseFloat(t.valor_pago))}</td>
                `;
                transacoesTableBody.appendChild(tr);
            });
        }
        
        function renderTopItens(itens) {
            topItensList.innerHTML = '';
            if (itens.length === 0) {
                topItensList.innerHTML = '<li>Nenhum item vendido neste período.</li>';
                return;
            }
            
            itens.forEach(item => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <span class="item-name">${item.item_nome}</span>
                    <span class="item-count">${item.total_vendido}x</span>
                `;
                topItensList.appendChild(li);
            });
        }
        
        // --- FUNÇÃO PRINCIPAL DE BUSCA ---
        
        async function fetchRelatorio() {
            const data_inicio = dataInicioInput.value;
            const data_fim = dataFimInput.value;
            
            if (!data_inicio || !data_fim) {
                alert('Por favor, selecione as duas datas.');
                return;
            }
            
            // Mostrar loading
            showLoading(sumarioCard);
            showLoading(transacoesCard);
            showLoading(topItensCard);
            
            try {
                const response = await fetch(`/api/relatorios/vendas?data_inicio=${data_inicio}&data_fim=${data_fim}`);
                const result = await response.json();
                
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Erro ao buscar dados da API.');
                }
                
                const data = result.data;
                
                // Renderizar os dados
                renderSumario(data.sumario);
                renderTransacoes(data.transacoes);
                renderTopItens(data.itens_mais_vendidos);
                
            } catch (error) {
                console.error('Erro ao buscar relatório:', error);
                alert('Falha ao carregar relatório: ' + error.message);
                // Limpar campos em caso de erro
                renderSumario({ faturamento_total: 0, ticket_medio: 0, total_pedidos: 0 });
                renderTransacoes([]);
                renderTopItens([]);
            } finally {
                // Esconder loading
                hideLoading(sumarioCard);
                hideLoading(transacoesCard);
                hideLoading(topItensCard);
            }
        }
        
        // --- EVENT LISTENERS ---
        
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchRelatorio();
        });
        
        // Carrega o relatório do dia ao iniciar a página
        fetchRelatorio();
    });
    </script>
</body>
</html>