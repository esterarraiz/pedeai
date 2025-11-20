document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('pedidos-container');

    container.addEventListener('click', (event) => {
        if (event.target && event.target.classList.contains('kitchen-btn-ready')) {
            const button = event.target;
            const pedidoId = button.dataset.id;
            
            const card = button.closest('.kitchen-order-card'); 

            if (!pedidoId) {
                console.error('ID do pedido não encontrado!');
                return;
            }
            
            button.disabled = true;
            button.textContent = 'Marcando...';

            fetch('/api/pedidos/marcar-pronto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ id: pedidoId })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Falha na resposta do servidor.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // MUDANÇA AQUI
                    card.classList.add('kitchen-fade-out');
                    
                    setTimeout(() => card.remove(), 500); 
                } else {
                    alert('Erro ao marcar pedido como pronto: ' + (data.message || 'Erro desconhecido.'));
                    button.disabled = false;
                    button.textContent = 'Pronto';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Ocorreu um erro de comunicação com o servidor.');
                button.disabled = false;
                button.textContent = 'Pronto';
            });
        }
    });

    async function atualizarDashboard() {
        try {
            const response = await fetch(window.location.href);
            if (!response.ok) {
                throw new Error('Falha ao buscar atualização de pedidos.');
            }
            const htmlText = await response.text();

            const parser = new DOMParser();
            const newDoc = parser.parseFromString(htmlText, 'text/html');
            
            const newContainer = newDoc.getElementById('pedidos-container');
            
            if (newContainer) {
                if (container.innerHTML !== newContainer.innerHTML) {
                    container.innerHTML = newContainer.innerHTML;
                }
            }
        } catch (error) {
            console.error('Erro ao atualizar dashboard:', error);
        }
    }

    setInterval(atualizarDashboard, 5000);

});