<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Resumo da Conta' ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/caixa.css">
</head>
<body>
<div class="dashboard-container">
    <?php include_once __DIR__ . '/../partials/sidebar_caixa.php'; ?>
    <main class="main-content">
        <div class="caixa-card" id="caixa-card-container">
            <h1>Pedidos Finalizados por Mesa</h1>
            <div id="resumo-content">
                <p>Carregando detalhes da conta...</p>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mesaId = <?= $mesa['id'] ?>;
    const resumoContent = document.getElementById('resumo-content');

    async function carregarDetalhesConta() {
        try {
            const response = await fetch(`/api/caixa/conta/${mesaId}`);
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message);
            }
            
            const pedido = data.pedido;
            let itensHtml = '';
            pedido.itens.forEach(item => {
                itensHtml += `
                    <li>
                        <span>${item.quantidade}x ${item.nome}</span>
                        <span>R$ ${ (item.quantidade * item.preco_unitario).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }</span>
                    </li>
                `;
            });

            const totalFormatado = pedido.total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

            resumoContent.innerHTML = `
                <div class="resumo-mesa">
                    <h2>Mesa ${String(<?= $mesa['numero'] ?>).padStart(2, '0')}</h2>
                    <ul class="resumo-itens">${itensHtml}</ul>
                    <div class="resumo-total">
                        <span>Total</span>
                        <span>R$ ${totalFormatado}</span>
                    </div>
                </div>
                
                <form id="payment-form">
                    <div class="payment-section">
                         <div class="total-display">Registrar Pagamento</div>
                         <div class="valor-pago-input-wrapper">
                            <label for="valor_pago_display">Valor Pago</label>
                            <input type="text" id="valor_pago_display" value="R$ ${totalFormatado}">
                         </div>
                         <div class="payment-methods">
                            <button type="button" class="payment-btn active" data-method="Dinheiro">Dinheiro</button>
                            <button type="button" class="payment-btn" data-method="Cartão de Crédito">Crédito</button>
                            <button type="button" class="payment-btn" data-method="Cartão de Débito">Débito</button>
                            <button type="button" class="payment-btn" data-method="Pix">Pix</button>
                         </div>
                         <button type="submit" class="btn-fechar-conta">Fechar Conta</button>
                    </div>
                </form>
            `;
            
            // Adicionar listeners de evento APÓS o HTML ser inserido
            setupPaymentForm(pedido.total);

        } catch (error) {
            resumoContent.innerHTML = `<p style="color: var(--red-status-text);">${error.message}</p>`;
        }
    }

    function setupPaymentForm(totalValue) {
        const paymentForm = document.getElementById('payment-form');
        const paymentButtons = document.querySelectorAll('.payment-btn');
        let metodoPagamento = 'Dinheiro'; // Valor padrão

        paymentButtons.forEach(button => {
            button.addEventListener('click', function() {
                paymentButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                metodoPagamento = this.getAttribute('data-method');
            });
        });
        
        paymentForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const btnSubmit = this.querySelector('.btn-fechar-conta');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Processando...';

            const payload = {
                mesa_id: mesaId,
                valor_pago: totalValue,
                metodo_pagamento: metodoPagamento
            };

            try {
                const response = await fetch('/api/caixa/processar-pagamento', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                // NOVO: Captura a resposta como texto primeiro para depuração
                const responseText = await response.text();

                if (!response.ok) {
                    // Se a resposta não for OK, joga um erro com o texto que recebemos
                    throw new Error(`Erro do servidor (HTTP ${response.status}): ${responseText}`);
                }
                
                // Tenta analisar o texto como JSON
                const data = JSON.parse(responseText);

                if (!data.success) {
                    throw new Error(data.message || 'Falha ao processar pagamento.');
                }
                
                alert('Pagamento registrado com sucesso!');
                window.location.href = '/dashboard/caixa';

            } catch (error) {
                // MODIFICADO: Mostra um erro mais informativo e loga a resposta completa
                console.error("--- RESPOSTA COMPLETA DO SERVIDOR ---");
                console.error(error); // Isso irá imprimir o erro detalhado no console
                alert(`Ocorreu um erro. Por favor, abra o console do navegador (F12) para ver os detalhes.`);
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Fechar Conta';
            }
        });
    }

    carregarDetalhesConta();
});
</script>
</body>
</html>