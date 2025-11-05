<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard-admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
                        <tr>
                            <td colspan="5" style="text-align: center;">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM a serem atualizados
    const faturamentoDiaEl = document.getElementById('faturamento-dia');
    const pedidosAndamentoEl = document.getElementById('pedidos-andamento');
    const mesasOcupadasEl = document.getElementById('mesas-ocupadas');
    const pedidosRecentesTbody = document.getElementById('pedidos-recentes-tbody');

    // Mapeamento de status para classes de badge
    const statusMap = {
        'em_preparo': { text: 'Na Cozinha', class: 'badge-cozinha' },
        'pronto': { text: 'Pronto', class: 'badge-servido' },
        'entregue': { text: 'Servido', class: 'badge-servido' },
        'pago': { text: 'Pago', class: 'badge-pago' }
    };
    
    // Função para formatar valores monetários
    function formatCurrency(value) {
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    // Função principal para buscar dados da API e atualizar a página
    async function atualizarDashboard() {
        try {
            const response = await fetch('/api/admin/dashboard');
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Erro ao buscar dados.');
            }

            const data = result.data;

            // 1. Atualiza as Métricas (Cards)
            const metricas = data.metricas;
            faturamentoDiaEl.textContent = formatCurrency(metricas.faturamento_dia);
            pedidosAndamentoEl.textContent = metricas.pedidos_andamento;
            mesasOcupadasEl.textContent = `${metricas.mesas_ocupadas}/${metricas.total_mesas}`;

            // 2. Atualiza a Tabela de Pedidos Recentes
            const pedidos = data.pedidos_recentes;
            pedidosRecentesTbody.innerHTML = ''; // Limpa a tabela

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
                        <td><a href="#" class="action-link">Ver Detalhes</a></td>
                    `;
                    pedidosRecentesTbody.appendChild(tr);
                });
            }

        } catch (error) {
            console.error("Erro ao atualizar o dashboard:", error);
            // Poderia adicionar uma notificação de erro na tela aqui
        }
    }

    // Chama a função pela primeira vez ao carregar a página
    atualizarDashboard();

    // Configura o intervalo para auto-atualização a cada 15 segundos
    setInterval(atualizarDashboard, 15000);
});
</script>

</body>
</html>