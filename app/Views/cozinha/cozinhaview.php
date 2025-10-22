<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Cozinha</title>
    
    <link rel="stylesheet" href="/css/style.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content" style="margin-left: 0; padding: 32px;">
            <header class="main-header">
                <h1>Painel da Cozinha</h1>
                
                <a href="/logout" class="btn btn-logout">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </header>

            <div id="pedidos-container" class="kitchen-grid">
                
                <?php if (empty($pedidos)): ?>
                    <p>Nenhum pedido em preparo no momento.</p>
                <?php else: ?>
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <h3><?= htmlspecialchars($pedido['mesa']) ?></h3>
                                <span><?= htmlspecialchars($pedido['hora']) ?></span>
                            </div>
                            <div class="order-card-body">
                                <ul>
                                    <?php foreach ($pedido['itens'] as $item): ?>
                                    <li>
                                        <span><?= htmlspecialchars($item['nome']) ?></span>
                                        <span class="quantity">x<?= htmlspecialchars($item['quantidade']) ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="order-card-footer">
                                <button class="btn-ready" data-id="<?= $pedido['id'] ?>">Marcar como Pronto</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('pedidos-container');

    // Usa delegação de eventos para capturar cliques nos botões
    container.addEventListener('click', (event) => {
        // Verifica se o elemento clicado é o nosso botão "Marcar como Pronto"
        if (event.target && event.target.classList.contains('btn-ready')) {
            const button = event.target;
            const pedidoId = button.dataset.id;
            const card = button.closest('.order-card'); // Pega o card do pedido

            if (!pedidoId) {
                console.error('ID do pedido não encontrado!');
                return;
            }
            
            button.disabled = true; // Desabilita o botão
            button.textContent = 'Marcando...';

            // ATUALIZAÇÃO DA URL: Aponta para a nova rota da API
            fetch('/api/pedidos/marcar-pronto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json' // Adicionado por segurança
                },
                body: JSON.stringify({ id: pedidoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animação de saída e remoção do card da tela
                    card.style.transition = 'opacity 0.5s ease';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 500);
                } else {
                    // Usa o alert personalizado (se você o tiver) ou o alert normal
                    alert('Erro ao marcar pedido como pronto: ' + data.message);
                    button.disabled = false; // Reabilita o botão em caso de erro
                    button.textContent = 'Marcar como Pronto';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Ocorreu um erro de comunicação com o servidor.');
                button.disabled = false; // Reabilita o botão em caso de erro
                button.textContent = 'Marcar como Pronto';
            });
        }
        setInterval(atualizarDashboard, 5000); 
    });
});
</script>

</body>
</html>
