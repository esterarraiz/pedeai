<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo da Conta - Mesa <?= htmlspecialchars($data['mesa']['numero']) ?></title>
    
    <link rel="stylesheet" href="/css/style.css"> 
    <link rel="stylesheet" href="/css/caixa.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    </head>
<body>

<div id="custom-alert-overlay" class="custom-alert-overlay">
    <div class="custom-alert-box">
        <h4 id="custom-alert-title"></h4>
        <p id="custom-alert-message"></p>
        <button id="custom-alert-ok" class="btn btn-primary">OK</button>
    </div>
</div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mesaId = <?= $data['mesa']['id'] ?>;
    const resumoContent = document.getElementById('resumo-content');
    let globalTotalValue = 0; 

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

            globalTotalValue = valorTotalMesa; 
            const totalFormatado = valorTotalMesa.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

            resumoContent.innerHTML = `
                <div class="resumo-mesa">
                    <ul class="item-list">${itensHtml}</ul>
                    <div class="total-section">
                        <h3>Total: R$ ${totalFormatado}</h3>
                    </div>
                </div>
                
                <div class="split-section">
                    <label for="split-count">Dividir por:</label>
                    <input type="number" id="split-count" min="1" value="1">
                    <h4 id="split-total"></h4>
                </div>
                
                <form id="payment-form">
                    <div class="payment-section" style="margin-top: 20px;">
                        
                        <div class="payment-methods" style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
                            <button type="button" class="btn payment-btn" data-method="Dinheiro">Dinheiro</button>
                            <button type="button" class="btn payment-btn" data-method="Cartão de Crédito">Crédito</button>
                            <button type="button" class="btn payment-btn" data-method="Cartão de Débito">Débito</button>
                            <button type="button" class="btn payment-btn" data-method="Pix">Pix</button>
                        </div>
                        
                        <button type="button" class="btn btn-print" id="btn-imprimir">
                            <i class="fas fa-print"></i>
                            Imprimir Conta
                        </button>
                        
                        <button type="submit" class="btn" id="btn-fechar-conta">
                            <i class="fas fa-check-circle"></i>
                            Fechar Conta
                        </button>
                    </div>
                </form>
            `;
            setupPaymentForm(); 

        } catch (error) {
            showAlert(error.message, 'error');
            resumoContent.innerHTML = `<p style="color: var(--red-status-text);">Erro ao carregar. Tente novamente.</p>`;
        }
    }

    function setupPaymentForm() {
        const paymentForm = document.getElementById('payment-form');
        const paymentButtons = document.querySelectorAll('.payment-btn');
        const btnSubmit = document.getElementById('btn-fechar-conta'); 
        
        const btnImprimir = document.getElementById('btn-imprimir');
        if (btnImprimir) {
            btnImprimir.addEventListener('click', () => {
                window.print();
            });
        }
        
        const splitInput = document.getElementById('split-count');
        const splitTotalDisplay = document.getElementById('split-total');
        if (splitInput && splitTotalDisplay) {
            splitInput.addEventListener('input', () => {
                const count = parseInt(splitInput.value, 10);
                if (count > 1) {
                    const individualTotal = globalTotalValue / count;
            splitTotalDisplay.textContent = `Valor por pessoa: R$ ${individualTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                } else {
                    splitTotalDisplay.textContent = '';
                }
            });
        }
        
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
                valor_pago: globalTotalValue,
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