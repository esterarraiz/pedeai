<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <title>Lançar Pedido - Mesa <?php echo htmlspecialchars($data['mesa_id']); ?></title>
    <!-- Adicionar estilos específicos se necessário -->
    <style>
        .summary-item { display: flex; justify-content: space-between; padding: 5px 0;}
        .summary-item .item-quantity { font-weight: bold; margin-left: 10px; color: var(--text-light); }
    </style>
</head>
<body>

<div class="dashboard-container">
        
        <!-- Sidebar do Garçom -->
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
            <h1>Lançar Pedido - Mesa <?php echo htmlspecialchars($data['mesa_id']); ?></h1>
        </header>

        <div class="order-layout">
            
            <section class="menu-section">
                <?php if (empty($data['cardapio'])): ?>
                    <p>Cardápio não encontrado ou vazio.</p>
                <?php else: ?>
                    <?php foreach ($data['cardapio'] as $categoria => $itens): ?>
                        <div class="category-group" style="margin-bottom: 24px;">
                            <h2><?php echo htmlspecialchars($categoria); ?></h2>
                            <div class="menu-item-list">
                                <?php foreach ($itens as $item): ?>
                                    <div class="menu-item">
                                        <div class="item-icon" style="background-color: 
                                            <?php 
                                                if (stripos($categoria, 'pizza') !== false) echo '#ff9800';
                                                elseif (stripos($categoria, 'porç') !== false) echo '#ffc107';
                                                else echo '#fbc02d'; 
                                            ?>;">
                                            <span><?php echo htmlspecialchars(explode(' ', $categoria)[0]); ?></span>
                                        </div>
                                        <div class="item-details">
                                            <h4><?php echo htmlspecialchars($item['nome']); ?></h4>
                                            <span class="price">R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></span>
                                        </div>
                                        <button 
                                            class="btn-add" 
                                            data-id="<?php echo $item['id']; ?>"
                                            data-nome="<?php echo htmlspecialchars($item['nome']); ?>" 
                                            data-preco="<?php echo $item['preco']; ?>">
                                            +
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <aside class="order-summary">
                <h3>Resumo do Pedido</h3>
                <p class="empty-message">Nenhum item adicionado.</p>
                <div class="summary-content"></div>
                <div class="total">
                    <span>Total:</span>
                    <span>R$ 0,00</span>
                </div>
                <button class="btn-submit-order" type="button">Fazer Pedido</button>
            </aside>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addButtons = document.querySelectorAll('.btn-add');
    const summaryContent = document.querySelector('.summary-content');
    const emptyMessage = document.querySelector('.empty-message');
    const totalElement = document.querySelector('.order-summary .total span:last-child');
    const submitButton = document.querySelector('.btn-submit-order');

    let orderItems = {};
    let totalPrice = 0.0;

    addButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id'); 
            const name = this.getAttribute('data-nome');
            const price = parseFloat(this.getAttribute('data-preco'));

            if (emptyMessage && !emptyMessage.hidden) {
                emptyMessage.hidden = true;
            }

            if (orderItems[id]) { 
                orderItems[id].quantity++;
                orderItems[id].element.querySelector('.item-quantity').textContent = `${orderItems[id].quantity}x`;
            } else {
                const summaryItem = document.createElement('div');
                summaryItem.classList.add('summary-item');
                summaryItem.innerHTML = `<span class="item-name">${name}</span><span class="item-quantity">1x</span>`;
                summaryContent.appendChild(summaryItem);

                orderItems[id] = { quantity: 1, price: price, element: summaryItem };
            }

            totalPrice += price;
            totalElement.textContent = totalPrice.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        });
    });

    submitButton.addEventListener('click', async function() { // Tornar a função async
        if (Object.keys(orderItems).length === 0) {
            alert('Nenhum item foi adicionado ao pedido.');
            return;
        }

        const itensParaEnviar = {};
        for (const itemId in orderItems) {
            itensParaEnviar[itemId] = orderItems[itemId].quantity;
        }

        const dadosDoPedido = {
            mesa_id: <?php echo htmlspecialchars($data['mesa_id']); ?>,
            itens: itensParaEnviar
        };
        
        // Desativa o botão e mostra "Enviando..."
        submitButton.disabled = true;
        submitButton.textContent = 'Enviando...';

        try {
            // CORREÇÃO: Chama o endpoint correto da API '/api/pedidos'
            const response = await fetch('/api/pedidos', { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(dadosDoPedido)
            });

            const data = await response.json(); // Tenta ler a resposta JSON

            if (response.ok) { // Verifica se o status HTTP é 2xx
                alert(data.message || "Pedido lançado com sucesso!"); 
                window.location.href = '/dashboard/garcom'; // Redireciona
            } else {
                // Mostra a mensagem de erro da API ou uma genérica
                alert('Erro: ' + (data.message || 'Falha ao processar o pedido.')); 
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            alert('Ocorreu um erro de comunicação com o servidor.');
        } finally {
             // Reativa o botão independentemente do resultado
             submitButton.disabled = false;
             submitButton.textContent = 'Fazer Pedido';
        }
    });
});
</script>

</body>
</html>
