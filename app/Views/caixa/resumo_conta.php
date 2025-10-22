<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo da Conta - Mesa <?= htmlspecialchars($data['mesa']['numero']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/caixa.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Estilos adicionados para esta página */
        .account-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: var(--shadow-sm); max-width: 800px; margin: 0 auto; }
        .account-header { text-align: center; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 20px; }
        .item-list { list-style: none; padding: 0; }
        .item-list li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed var(--border-color); }
        .item-list li span:first-child { font-weight: 500; }
        .item-list .quantity { color: var(--text-light); }
        .total-section { text-align: right; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--text-dark); }
        .total-section h3 { font-size: 1.8rem; margin: 0; }
        .actions { text-align: right; margin-top: 30px; }

        /* --- CORREÇÃO: Estilos para seleção de pagamento --- */
        .payment-methods .btn {
            background-color: #f0f2f5; /* Cor de fundo inativa (cinza claro) */
            color: #333; /* Cor do texto inativo */
            border: 1px solid #ddd;
            font-weight: 600;
        }
        
        .payment-methods .btn.active {
            background-color: #28a745; /* CORRIGIDO: Cor verde (hardcoded) */
            color: #ffffff;           /* CORRIGIDO: Cor do texto (hardcoded) */
            border-color: #28a745;     /* CORRIGIDO: Cor da borda (hardcoded) */
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        /* --- FIM DA CORREÇÃO --- */

        /* --- MELHORIA: Botão Fechar Conta com mais destaque --- */
        #btn-fechar-conta {
            width: 100%; /* CORRIGIDO: Ocupa 100% da largura */
            background-color: #28a745; /* CORRIGIDO: Cor verde (hardcoded) */
            color: #ffffff;           /* CORRIGIDO: Cor do texto (hardcoded) */
            border: none;              /* CORRIGIDO: Remove borda */

            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            padding: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease-in-out;
            margin-top: 10px; /* Adiciona um espaço */
        }
        #btn-fechar-conta:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            background-color: #218838; /* Verde um pouco mais escuro no hover */
        }
        #btn-fechar-conta:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        /* --- FIM DA MELHORIA --- */

        /* --- Estilos do Alert (Sem alteração) --- */
        .custom-alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        .custom-alert-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .custom-alert-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 400px;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.2s ease;
        }
        .custom-alert-overlay.show .custom-alert-box {
            transform: scale(1);
        }
        .custom-alert-box h4 {
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .custom-alert-box p {
            margin-bottom: 25px;
            font-size: 1rem;
            color: var(--text-light);
        }
        .custom-alert-box .btn {
            width: 100%;
            padding: 12px;
        }
        /* --- FIM --- */

    </style>
</head>
<body>

<!-- HTML do Alert Personalizado (Sem alteração) -->
<div id="custom-alert-overlay" class="custom-alert-overlay">
    <div class="custom-alert-box">
        <h4 id="custom-alert-title"></h4>
        <p id="custom-alert-message"></p>
        <button id="custom-alert-ok" class="btn btn-primary">OK</button>
    </div>
</div>
<!-- FIM -->

<div class="dashboard-container">
    <?php include_once __DIR__ . '/../partials/sidebar_caixa.php'; ?>
    <main class="main-content">
        <header class="main-header">
             <a href="/dashboard/caixa" class="btn btn-secondary" style="margin-left: -10px;"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Voltar</a>
        </header>

        <div class="account-card" id="caixa-card-container">
            <div class="account-header">
                <h2>Pedidos Finalizados por Mesa</h2>
                <h3>Mesa <?= str_pad(htmlspecialchars($data['mesa']['numero']), 2, '0', STR_PAD_LEFT) ?></h3>
            </div>
            <div id="resumo-content">
                <p>Carregando detalhes da conta...</p>
            </div>
        </div>
    </main>
</div>

<!-- Script do Alert Personalizado (Sem alteração) -->
<script>
    const alertOverlay = document.getElementById('custom-alert-overlay');
    const alertTitle = document.getElementById('custom-alert-title');
    const alertMessage = document.getElementById('custom-alert-message');
    const alertOkBtn = document.getElementById('custom-alert-ok');
    let alertRedirectUrl = null;

    function showAlert(message, type = 'error', redirectUrl = null) {
        alertTitle.textContent = (type === 'success') ? 'Sucesso!' : 'Ocorreu um Erro';
        alertMessage.textContent = message;
        
        alertOkBtn.className = 'btn'; 
        if (type === 'success') {
            alertOkBtn.classList.add('btn-success');
        } else {
            alertOkBtn.classList.add('btn-danger'); 
        }

        alertRedirectUrl = redirectUrl; 
        alertOverlay.classList.add('show');
    }

    alertOkBtn.addEventListener('click', () => {
        alertOverlay.classList.remove('show');
        if (alertRedirectUrl) {
            window.location.href = alertRedirectUrl;
        }
    });
</script>
<!-- FIM -->


<script>
document.addEventListener('DOMContentLoaded', function() {
    const mesaId = <?= $data['mesa']['id'] ?>;
    const resumoContent = document.getElementById('resumo-content');

    async function carregarDetalhesConta() {
        try {
            const response = await fetch(`/api/caixa/conta/${mesaId}`);
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Falha ao carregar detalhes da conta.');
            }
            
            const pedidos = data.pedidos;
            let itensHtml = '';
            let valorTotalMesa = 0;

            if (!pedidos || pedidos.length === 0) {
                resumoContent.innerHTML = '<p>Nenhum item consumido encontrado para esta mesa.</p>';
                return;
            }

            pedidos.forEach(pedido => {
                pedido.itens.forEach(item => {
                    const subtotalItem = (item.quantidade * item.preco_unitario);
                    valorTotalMesa += subtotalItem;
                    itensHtml += `
                        <li>
                            <span>${item.quantidade}x ${item.nome}</span>
                            <span>R$ ${ subtotalItem.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }</span>
                        </li>
                    `;
                });
            });

            const totalFormatado = valorTotalMesa.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

            // --- ALTERAÇÃO NO INNERHTML ---
            resumoContent.innerHTML = `
                <div class="resumo-mesa">
                    <ul class="item-list">${itensHtml}</ul>
                    <div class="total-section">
                        <h3>Total: R$ ${totalFormatado}</h3>
                    </div>
                </div>
                
                <form id="payment-form">
                    <div class="payment-section" style="margin-top: 30px;">
                        
                        <!-- CORRIGIDO: Adicionado 'justify-content: center;' para centralizar os botões -->
                        <div class="payment-methods" style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
                            <button type="button" class="btn payment-btn" data-method="Dinheiro">Dinheiro</button>
                            <button type="button" class="btn payment-btn" data-method="Cartão de Crédito">Crédito</button>
                            <button type="button" class="btn payment-btn" data-method="Cartão de Débito">Débito</button>
                            <button type="button" class="btn payment-btn" data-method="Pix">Pix</button>
                        </div>
                        
                        <!-- CORRIGIDO: Removida a classe 'btn-success' para evitar conflito. O ID já estiliza. -->
                        <button type="submit" class="btn" id="btn-fechar-conta">
                            <i class="fas fa-check-circle"></i>
                            Fechar Conta
                        </button>
                    </div>
                </form>
            `;
            // --- FIM DA ALTERAÇÃO ---
            
            setupPaymentForm(valorTotalMesa);

        } catch (error) {
            showAlert(error.message, 'error');
            resumoContent.innerHTML = `<p style="color: var(--red-status-text);">Erro ao carregar. Tente novamente.</p>`;
        }
    }

    function setupPaymentForm(totalValue) {
        const paymentForm = document.getElementById('payment-form');
        const paymentButtons = document.querySelectorAll('.payment-btn');
        const btnSubmit = document.getElementById('btn-fechar-conta'); 
        let metodoPagamento = null; 

        if(paymentButtons.length > 0) {
            paymentButtons[0].classList.add('active');
            metodoPagamento = paymentButtons[0].getAttribute('data-method');
        }

        paymentButtons.forEach(button => {
            button.addEventListener('click', function() {
                paymentButtons.forEach(btn => btn.classList.remove('active')); 
                this.classList.add('active');
                metodoPagamento = this.getAttribute('data-method');
            });
        });
        
        paymentForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            if (!metodoPagamento) {
                showAlert('Por favor, selecione um método de pagamento.', 'error');
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...'; 

            const payload = {
                mesa_id: mesaId,
                valor_pago: totalValue,
                metodo_pagamento: metodoPagamento
            };

            try {
                const response = await fetch('/api/caixa/pagamento', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Falha ao processar pagamento.');
                }
                
                showAlert('Pagamento registrado com sucesso!', 'success', '/dashboard/caixa');

            } catch (error) {
                console.error("Erro ao processar pagamento:", error);
                showAlert(error.message, 'error');
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-check-circle"></i> Fechar Conta';
            }
        });
    }

    carregarDetalhesConta();
});
</script>
</body>
</html>

