<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Caixa - Contas Abertas</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_caixa.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Contas Abertas</h1>
            </header>

            <section class="tables-grid" id="mesas-container">
                <p style="text-align: center; color: var(--text-light); font-size: 1.1rem; margin-top: 40px;">Carregando mesas...</p>
            </section>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('mesas-container');

    async function carregarMesas() {
        try {
            const response = await fetch('/api/caixa/mesas-abertas');
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Falha ao carregar as mesas.');
            }

            container.innerHTML = ''; // Limpa a mensagem de "carregando"

            if (data.mesas.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--text-light); font-size: 1.1rem; margin-top: 40px;">Nenhuma mesa com conta aberta no momento.</p>';
                return;
            }

            data.mesas.forEach(mesa => {
                let statusClass = '';
                let statusText = '';
                if (mesa.status === 'ocupada') {
                    statusClass = 'status-ocupada';
                    statusText = 'Consumindo';
                } else if (mesa.status === 'aguardando_pagamento') {
                    statusClass = 'status-pagamento'; 
                    statusText = 'Aguardando Pagamento';
                }

                const mesaCardHtml = `
                    <a href="/caixa/conta/${mesa.id}" class="table-card-link">
                        <div class="table-card ${statusClass}">
                            <div>
                                <div class="table-number">
                                    ${String(mesa.numero).padStart(2, '0')}
                                </div>
                                <div class="table-label">Mesa</div>
                            </div>
                            <span class="status">${statusText}</span>
                        </div>
                    </a>
                `;
                container.innerHTML += mesaCardHtml;
            });

        } catch (error) {
            console.error('Erro:', error);
            container.innerHTML = `<p style="text-align: center; color: var(--red-status-text); font-size: 1.1rem; margin-top: 40px;">${error.message}</p>`;
        }
    }

    carregarMesas();
    // Opcional: Recarregar a lista a cada 15 segundos
    setInterval(carregarMesas, 15000); 
});
</script>
</body>
</html>