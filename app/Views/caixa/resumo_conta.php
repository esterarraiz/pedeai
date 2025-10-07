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
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="/images/pedeai-logo.png" alt="Logo PedeAi">
        </div>
        <ul class="sidebar-nav">
            <li><a href="#"><i class="fa-solid fa-home"></i><span>Home</span></a></li>
            <li><a href="/dashboard/caixa" class="active"><i class="fa-solid fa-chair"></i><span>Mesas e Pedidos</span></a></li>
            <li><a href="#"><i class="fa-solid fa-dollar-sign"></i><span>Pagamento</span></a></li>
            <li><a href="#"><i class="fa-solid fa-question-circle"></i><span>Suporte</span></a></li>
            <li><a href="/logout"><i class="fa-solid fa-sign-out-alt"></i><span>Sair</span></a></li>
        </ul>
        <div class="sidebar-user">
            <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?></h4>
            <span>Caixa</span>
        </div>
    </aside>
    <main class="main-content">
        <div class="caixa-card">
            <h1>Pedidos Finalizados por Mesa</h1>
            <?php if (!empty($pedido)): ?>
            <div class="resumo-mesa">
                <h2>Mesa <?= str_pad($mesa['numero'], 2, '0', STR_PAD_LEFT) ?></h2>
                <ul class="resumo-itens">
                    <?php foreach ($pedido['itens'] as $item): ?>
                    <li>
                        <span><?= $item['quantidade'] ?>x <?= htmlspecialchars($item['nome']) ?></span>
                        <span>R$ <?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="resumo-total">
                    <span>Total</span>
                    <span>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></span>
                </div>
            </div>
            
            <form action="/caixa/pagamento/processar" method="POST" id="payment-form">
                <input type="hidden" name="mesa_id" value="<?= $mesa['id'] ?>">
                <input type="hidden" name="valor_pago" value="<?= $pedido['total'] ?>">
                <input type="hidden" name="metodo_pagamento" id="metodo_pagamento_input" value="Dinheiro">
                
                <div class="payment-section">
                     <div class="total-display">
                        Registrar Pagamento
                     </div>
                     <div class="valor-pago-input-wrapper">
                        <label for="valor_pago_display">Valor Pago</label>
                        <input type="text" id="valor_pago_display" value="R$ <?= number_format($pedido['total'], 2, ',', '.') ?>">
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

            <?php else: ?>
            <p>Não foi possível carregar os detalhes do pedido para esta mesa.</p>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentButtons = document.querySelectorAll('.payment-btn');
        const hiddenInput = document.getElementById('metodo_pagamento_input');
        paymentButtons.forEach(button => {
            button.addEventListener('click', function() {
                paymentButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                hiddenInput.value = this.getAttribute('data-method');
            });
        });
    });
</script>
</body>
</html>