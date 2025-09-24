<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Garçom - Mesas</title>
    
    <link rel="stylesheet" href="/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);
            justify-content: center; align-items: center;
        }
        .modal-content {
            background-color: #fff; padding: 25px; border-radius: 8px;
            width: 90%; max-width: 450px; text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .close-button {
            color: #aaa; float: right; font-size: 28px;
            font-weight: bold; cursor: pointer;
        }
        /* Você pode customizar os botões no seu style.css principal */
        .btn-confirmar { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-cancelar { background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px; }
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
                            
                            $linkMesa = "/pedidos/novo/" . $mesa['id'];
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

    <div id="modal-pagamento" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Finalizar e Efetuar Pagamento</h2>
            <p>Confirmar o pagamento e liberar a <strong id="modal-mesa-numero">Mesa X</strong>?</p>
            <button id="btn-confirmar-pagamento" class="btn-confirmar">Sim, Pagamento Efetuado</button>
            <button class="btn-cancelar">Cancelar</button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const modal = document.getElementById("modal-pagamento");
            const modalMesaNumero = document.getElementById("modal-mesa-numero");
            const btnConfirmar = document.getElementById("btn-confirmar-pagamento");
            const closeButton = modal.querySelector(".close-button");
            const btnCancelar = modal.querySelector(".btn-cancelar");

            let mesaIdParaAtualizar = null;
            let linkParaAtualizar = null; // Guardará o elemento <a>

            // Adiciona um listener de clique na área das mesas
            document.querySelector(".tables-grid").addEventListener("click", function(event) {
                const linkDaMesa = event.target.closest(".table-card-link");

                if (!linkDaMesa) return; // Se não clicou em um link de mesa, ignora

                const status = linkDaMesa.dataset.status;

                // Apenas intercepta o clique se a mesa estiver ocupada ou aguardando pagamento
                if (status === "ocupada" || status === "aguardando_pagamento") {
                    event.preventDefault(); // Impede o link de navegar para a outra página!

                    mesaIdParaAtualizar = linkDaMesa.dataset.id;
                    linkParaAtualizar = linkDaMesa; // Salva a referência ao link
                    const numeroMesa = linkDaMesa.querySelector(".table-number").textContent.trim();
                    
                    modalMesaNumero.textContent = `Mesa ${numeroMesa}`;
                    modal.style.display = "flex";
                }
            });

            const fecharModal = () => { modal.style.display = "none"; };

            closeButton.addEventListener("click", fecharModal);
            btnCancelar.addEventListener("click", fecharModal);

            // Confirma o pagamento e envia a requisição para o backend
            btnConfirmar.addEventListener("click", function() {
                if (!mesaIdParaAtualizar) return;

                fetch('/mesas/liberar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mesa_id: mesaIdParaAtualizar })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cardDiv = linkParaAtualizar.querySelector('.table-card');
                        const statusSpan = linkParaAtualizar.querySelector('.status');
                        
                        // Atualiza a interface
                        cardDiv.className = 'table-card status-livre'; // Reseta as classes
                        statusSpan.textContent = 'LIVRE';
                        linkParaAtualizar.dataset.status = 'livre'; // Atualiza o status no data attribute

                        fecharModal();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Não foi possível comunicar com o servidor.');
                });
            });
        });
    </script>
</body>
</html>