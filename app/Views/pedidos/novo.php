<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/style.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <title>Lançar Pedido - Mesa <?php echo htmlspecialchars($data['mesa_id']); ?></title>
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
            <h1>Lançar Pedido - Mesa <?php echo htmlspecialchars($data['mesa_id']); ?></h1>
        </header>

        <div class="order-layout">
            
            <section class="menu-section">
                <?php foreach ($data['cardapio'] as $categoria => $itens): ?>
                    <div class="category-group" style="margin-bottom: 24px;">
                        <h2><?php echo htmlspecialchars($categoria); ?></h2>
                        <div class="menu-item-list">
                            <?php foreach ($itens as $item): ?>
                                <div class="menu-item">
                                    <div class="item-icon" style="background-color: 
                                        <?php 
                                            // Lógica simples para cores baseada na categoria
                                            if (stripos($categoria, 'pizza') !== false) echo '#ff9800';
                                            elseif (stripos($categoria, 'porç') !== false) echo '#ffc107';
                                            else echo '#fbc02d'; // Cor padrão
                                        ?>;">
                                        <span>
                                            <?php 
                                                // Pega a primeira palavra da categoria para o ícone
                                                echo htmlspecialchars(explode(' ', $categoria)[0]); 
                                            ?>
                                        </span>
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
            </section>

            <aside class="order-summary">
                <h3>Resumo do Pedido</h3>

                <p class="empty-message">Nenhum item adicionado.</p>

                <div class="summary-content"></div>

                <div class="total">
                    <span>Total:</span>
                    <span>R$ 0,00</span>
                </div>
                <button class="btn-submit-order" type="submit">Fazer Pedido</button>
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

    // --- LÓGICA PARA ADICIONAR ITENS (JÁ ESTÁ OK) ---
    addButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id'); // <-- Captura o ID
            const name = this.getAttribute('data-nome');
            const price = parseFloat(this.getAttribute('data-preco'));

            if (emptyMessage && !emptyMessage.hidden) {
                emptyMessage.hidden = true;
            }

            if (orderItems[id]) { // Usando ID como chave para evitar nomes duplicados
                orderItems[id].quantity++;
                orderItems[id].element.querySelector('.item-quantity').textContent = `${orderItems[id].quantity}x`;
            } else {
                const summaryItem = document.createElement('div');
                summaryItem.classList.add('summary-item');
                summaryItem.innerHTML = `<span class="item-name">${name}</span><span class="item-quantity">1x</span>`;
                summaryContent.appendChild(summaryItem);

                orderItems[id] = {
                    quantity: 1,
                    price: price,
                    element: summaryItem
                };
            }

            totalPrice += price;
            totalElement.textContent = totalPrice.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        });
    });

    // --- NOVA LÓGICA PARA ENVIAR O PEDIDO ---
    submitButton.addEventListener('click', function() {
        if (Object.keys(orderItems).length === 0) {
            alert('Nenhum item foi adicionado ao pedido.');
            return;
        }

        // Formata o objeto de itens para o formato esperado pelo PHP: [item_id => quantidade]
        const itensParaEnviar = {};
        for (const itemId in orderItems) {
            itensParaEnviar[itemId] = orderItems[itemId].quantity;
        }

        const dadosDoPedido = {
            mesa_id: <?php echo htmlspecialchars($data['mesa_id']); ?>,
            itens: itensParaEnviar
        };
        
        // Envia os dados para o novo método no controller
        fetch('/pedidos/processar-ajax', { // <<< ROTA NOVA/AJAX
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(dadosDoPedido)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message); // Ex: "Pedido lançado com sucesso!"
                window.location.href = '/dashboard/garcom'; // Redireciona
            } else {
                alert('Erro: ' + data.message); // Ex: "Nenhum item foi adicionado."
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Ocorreu um erro de comunicação com o servidor.');
        });
    });
});
</script>

</body>
</html>