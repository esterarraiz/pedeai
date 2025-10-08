<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Garçom - Mesas</title>
    
    <link rel="stylesheet" href="/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        #notificacao-pedidos-prontos {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-size: 1.1rem;
            font-weight: 500;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s, visibility 0.5s, transform 0.5s;
            transform: translateY(20px);
        }
        #notificacao-pedidos-prontos.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
            </div>
            <ul class="sidebar-nav">
                <li><a href="/dashboard/garcom" class="active"><i class="fa-solid fa-chair"></i><span>Mesas</span></a></li>
                <li><a href="/dashboard/pedidos"><i class="fa-solid fa-receipt"></i><span>Pedidos Atuais</span></a></li>
                <li><a href="/logout"><i class="fa-solid fa-sign-out-alt"></i><span>Sair</span></a></li>
            </ul>
            <div class="sidebar-user">
                <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?></h4>
                <span>Garçom</span>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1>Gerenciamento de Mesas</h1>
            </header>

            <section class="tables-grid">
                <?php if (empty($mesas)): ?>
                    <p>Nenhuma mesa cadastrada para esta empresa.</p>
                <?php else: ?>
                    <?php foreach ($mesas as $mesa): ?>
                        <?php
                            $statusClass = 'status-livre';
                            $statusText = 'Livre';

                            if ($mesa['status'] === 'ocupada') {
                                $statusClass = 'status-ocupada';
                                $statusText = 'Ocupada';
                            } elseif ($mesa['status'] === 'aguardando_pagamento') {
                                $statusClass = 'status-pagamento'; 
                                $statusText = 'Pagamento';
                            }
                            
                            if ($mesa['status'] === 'ocupada' || $mesa['status'] === 'aguardando_pagamento') {
                                $linkMesa = "/mesas/detalhes/" . $mesa['id'];
                            } else { // Se o status for 'livre'
                                $linkMesa = "/pedidos/novo/" . $mesa['id'];
                            }
                        ?>

                        <a href="<?= $linkMesa ?>" class="table-card-link" 
                           data-id="<?= $mesa['id'] ?>" 
                           data-status="<?= $mesa['status'] ?>">
                            <div class="table-card <?= $statusClass ?>">
                                
                                <div>
                                    <div class="table-number">
                                        <?= str_pad($mesa['numero'], 2, '0', STR_PAD_LEFT) ?>
                                    </div>
                                    <div class="table-label">Mesa</div>
                                </div>
                                <span class="status"><?= $statusText ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <div id="notificacao-pedidos-prontos"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificacaoDiv = document.getElementById('notificacao-pedidos-prontos');
            let ultimosPedidosProntos = ''; // Para evitar alertas repetidos

            async function verificarPedidosProntos() {
                try {
                    const response = await fetch('/pedidos/prontos');
                    if (!response.ok) {
                        console.error('Erro ao buscar pedidos:', response.statusText);
                        return;
                    }

                    const data = await response.json();

                    if (data.success && data.pedidos.length > 0) {
                        const mesas = data.pedidos.map(p => p.mesa_numero).sort((a,b) => a-b);
                        const pedidosIds = data.pedidos.map(p => p.pedido_id).sort((a,b) => a-b).join(',');

                        if (pedidosIds !== ultimosPedidosProntos) {
                            ultimosPedidosProntos = pedidosIds;
                            
                            const mesasUnicas = [...new Set(mesas)]; // Pega apenas números de mesa únicos
                            const mesasFormatadas = mesasUnicas.map(m => String(m).padStart(2, '0')).join(', ');
                            notificacaoDiv.textContent = `Pedidos prontos para as Mesas: ${mesasFormatadas}`;
                            notificacaoDiv.classList.add('show');

                            setTimeout(() => {
                                notificacaoDiv.classList.remove('show');
                            }, 10000); // Esconde depois de 10 segundos
                        }
                    } else {
                        ultimosPedidosProntos = '';
                        notificacaoDiv.classList.remove('show');
                    }
                } catch (error) {
                    console.error('Falha na requisição:', error);
                }
            }

            setInterval(verificarPedidosProntos, 5000); // Verifica a cada 5 segundos
            verificarPedidosProntos(); // Verifica assim que a página carrega
        });
    </script>
</body>
</html>