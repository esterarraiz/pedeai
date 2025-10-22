<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Garçom - Mesas</title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --cor-primaria: #2c3e50; --cor-secundaria: #3498db; --cor-sucesso: #2ecc71;
            --cor-aviso: #f1c40f; --cor-perigo: #e74c3c; --cor-fundo: #ecf0f1;
            --cor-texto: #333; --sombra: 0 4px 8px rgba(0,0,0,0.1);
        }
        #pedidos-prontos-container { background: white; padding: 20px 25px; border-radius: 8px; box-shadow: var(--sombra); margin-bottom: 30px; }
        #pedidos-prontos-container h2 { margin-top: 0; color: var(--cor-primaria); margin-bottom: 20px; }
        .pedido-pronto { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border: 1px solid #d1fae5; background-color: #f0fdf4; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); transition: box-shadow 0.2s ease; }
        .pedido-pronto:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .pedido-pronto:last-child { border-bottom: 1px solid #d1fae5; margin-bottom: 0; }
        .pedido-pronto span { font-weight: 500; color: #065f46; }
        .pedido-pronto span strong { color: #047857; font-weight: 700; }
        .btn-entregar { background-color: var(--cor-sucesso); color: white; border: none; padding: 9px 14px; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: background-color 0.2s ease; }
        .btn-entregar:hover { background-color: #27ae60; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background-color: #fefefe; padding: 30px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: #333; }
        #detalhes-pedido-itens { list-style: none; padding: 0; }
        #detalhes-pedido-itens li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        #pedido-total { text-align: right; font-weight: bold; font-size: 1.2rem; margin-top: 15px; }
        #form-novo-pedido #cardapio-categorias { margin-top: 15px; max-height: 40vh; overflow-y: auto; padding-right: 10px; }
        .categoria-bloco { margin-bottom: 15px; }
        .categoria-titulo { font-weight: bold; color: var(--cor-primaria); border-bottom: 2px solid var(--cor-secundaria); padding-bottom: 5px; margin-bottom: 10px; }
        .item-cardapio { display: flex; justify-content: space-between; align-items: center; padding: 5px; }
        .item-cardapio label { flex-grow: 1; margin-right: 10px; }
        .item-cardapio input[type="number"] { width: 60px; text-align: center; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .modal-footer { text-align: right; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .btn-primary { background-color: var(--cor-secundaria); color: white; }
        .btn-success { background-color: var(--cor-sucesso); color: white; }
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

            <div id="pedidos-prontos-container">
                <h2>Pedidos Prontos para Entrega</h2>
                <div id="lista-pedidos-prontos"><p>Carregando...</p></div>
            </div>

            <section class="tables-grid" id="mesas-grid">
                <p>Carregando mesas...</p> 
            </section>
        </main>
    </div>

    <!-- Modal para Detalhes e Novo Pedido -->
    <div id="modal-mesa" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-titulo"></h2>
                <span class="close-btn">&times;</span>
            </div>
            <div id="conteudo-detalhes" style="display: none;">
                <h4>Pedido Atual</h4>
                <ul id="detalhes-pedido-itens"></ul>
                <p id="pedido-total"></p>
                <div class="modal-footer">
                    <button id="btn-adicionar-itens" class="btn btn-primary">Adicionar Itens</button>
                </div>
            </div>
            <div id="conteudo-novo-pedido" style="display: none;">
                <form id="form-novo-pedido">
                    <input type="hidden" id="form-mesa-id" name="mesa_id">
                    <div id="cardapio-categorias"></div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Lançar Pedido</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mesasGrid = document.getElementById('mesas-grid');
            const modal = document.getElementById('modal-mesa');
            const closeModalBtn = modal.querySelector('.close-btn');
            const modalTitulo = document.getElementById('modal-titulo');
            const conteudoDetalhes = document.getElementById('conteudo-detalhes');
            const conteudoNovoPedido = document.getElementById('conteudo-novo-pedido');
            const formNovoPedido = document.getElementById('form-novo-pedido');
            const formMesaIdInput = document.getElementById('form-mesa-id');
            const btnAdicionarItens = document.getElementById('btn-adicionar-itens');
            const pedidosProntosContainer = document.getElementById('lista-pedidos-prontos');
            let cardapioCache = null;

            const apiFetch = async (url, options = {}) => {
                let responseData = null;
                try {
                    const response = await fetch(url, options);
                    responseData = await response.json(); 
                    if (!response.ok) {
                        throw new Error(responseData?.message || `Erro ${response.status} (${response.statusText})`);
                    }
                    return responseData; 
                } catch (error) {
                    console.error("Erro na API:", url, error);
                    // Retorna um objeto indicando falha e a mensagem para tratamento
                    return { success: false, message: error.message }; 
                }
            };

            const carregarMesas = async () => {
                const response = await apiFetch('/api/garcom/mesas');
                // CORREÇÃO: A sua API /api/garcom/mesas retorna {success: true, data: [...] }
                const mesas = response?.data ?? []; 

                if (response?.success === false || !Array.isArray(mesas)) {
                        mesasGrid.innerHTML = `<p>Erro ao carregar mesas: ${response?.message || 'Falha na comunicação'}</p>`;
                        return;
                }
                
                mesasGrid.innerHTML = ''; 
                
                if (mesas.length === 0) {
                        mesasGrid.innerHTML = '<p>Nenhuma mesa encontrada.</p>';
                        return;
                }

                mesas.forEach(mesa => {
                    // CORREÇÃO: O status 'disponivel' deve ser usado, não 'livre'.
                    let statusClass = 'status-livre'; let statusText = 'Livre';
                    let linkHref = `/pedidos/novo/${mesa.id}`; 
                    if (mesa.status === 'ocupada') { statusClass = 'status-ocupada'; statusText = 'Ocupada'; linkHref = `/mesas/detalhes/${mesa.id}`; } 
                    else if (mesa.status === 'aguardando_pagamento') { statusClass = 'status-pagamento'; statusText = 'Pagamento'; linkHref = `/mesas/detalhes/${mesa.id}`; }
                    else if (mesa.status === 'disponivel') { /* já é o padrão */ }

                    const linkMesa = document.createElement('a');
                    linkMesa.className = 'table-card-link';
                    linkMesa.dataset.id = mesa.id;
                    linkMesa.dataset.status = mesa.status;
                    linkMesa.href = linkHref; 

                    // --- LÓGICA DO LISTENER DE CLIQUE ---
                    linkMesa.addEventListener('click', (e) => {
                        e.preventDefault(); // sempre previne o comportamento padrão

                        const mesaId = linkMesa.dataset.id;
                        const mesaNumero = linkMesa.querySelector('.table-number').textContent.trim();
                        const mesaStatus = linkMesa.dataset.status;

                        if (mesaStatus === 'disponivel') {
                            // Redireciona para novo pedido
                            // ESTA É A OUTRA PÁGINA, SE O BUG ESTIVER LÁ, PRECISAMOS VER ESSE ARQUIVO
                            window.location.href = `/pedidos/novo/${mesaId}`;
                        } else {
                            // Abre modal com detalhes do pedido atual
                            abrirModalMesa(mesaId, mesaNumero, mesaStatus);
                        }
                    });


                    linkMesa.innerHTML = `<div class="table-card ${statusClass}"><div><div class="table-number">${String(mesa.numero).padStart(2, '0')}</div><div class="table-label">Mesa</div></div><span class="status">${statusText}</span></div>`;
                    mesasGrid.appendChild(linkMesa);
                });
            };

            const carregarPedidosProntos = async () => {
                const response = await apiFetch('/api/garcom/pedidos/prontos');
                const pedidos = response?.data ?? [];

                // ----> ADICIONE ESTA LINHA <----
                console.log('API /api/garcom/pedidos/prontos retornou:', JSON.stringify(pedidos));
                // ----> FIM DA LINHA ADICIONADA <----

                if (response?.success === false || !Array.isArray(pedidos)) {
                    pedidosProntosContainer.innerHTML = `<p>Erro ao carregar pedidos prontos: ${response?.message || 'Falha na comunicação'}</p>`;
                    return;
                }
                
                pedidosProntosContainer.innerHTML = ''; 
                if (pedidos.length === 0) {
                    pedidosProntosContainer.innerHTML = '<p>Nenhum pedido pronto no momento.</p>';
                    return;
                }
                
                pedidos.forEach(mesaInfo => { 
                    const pedidoDiv = document.createElement('div');
                    pedidoDiv.className = 'pedido-pronto';
                    pedidoDiv.innerHTML = `
                        <span>Pedido da <strong>Mesa ${String(mesaInfo.mesa_numero).padStart(2, '0')}</strong> está pronto!</span>
                        <button class="btn-entregar" data-mesa-id="${mesaInfo.mesa_id}">Marcar Entregue</button> 
                    `; 
                    pedidosProntosContainer.appendChild(pedidoDiv);
                });
            };

            // Event listener para marcar como entregue (já estava correto)
            pedidosProntosContainer.addEventListener('click', async (event) => {
                if (event.target.classList.contains('btn-entregar')) {
                    const mesaId = event.target.dataset.mesaId; 
                    if (!mesaId) {
                        console.error('Mesa ID não encontrado no botão!'); 
                        return;
                    }

                    const response = await apiFetch('/api/garcom/pedidos/marcar-entregue', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ mesa_id: mesaId }) 
                    });
                    
                    if (response && response.success) {
                        atualizarDashboard(); 
                    } else {
                        alert(response?.message || 'Erro ao marcar como entregue.');
                    }
                }
            });


            const abrirModalMesa = async (mesaId, mesaNumero, mesaStatus) => {
                modalTitulo.textContent = `Mesa ${mesaNumero}`;
                formMesaIdInput.value = mesaId; 
                conteudoDetalhes.style.display = 'block';
                conteudoNovoPedido.style.display = 'none';
                conteudoDetalhes.querySelector('ul').innerHTML = '<li>Carregando detalhes...</li>';
                conteudoDetalhes.querySelector('p').textContent = '';

                // A API /api/garcom/mesas/{id} retorna { success: true, data: { mesa: ..., pedido: ... } }
                const response = await apiFetch(`/api/garcom/mesas/${mesaId}`); 
                if (response?.success && response.data?.pedido) { 
                    renderizarDetalhesPedido(response.data.pedido);
                } else {
                    // Se não há pedido, oferece para criar um novo
                    conteudoDetalhes.querySelector('ul').innerHTML = '<li>Nenhum pedido ativo.</li>';
                    conteudoDetalhes.querySelector('p').textContent = '';
                }
                modal.style.display = 'flex'; 
            };

            const renderizarDetalhesPedido = (pedido) => {
                const itensList = document.getElementById('detalhes-pedido-itens');
                const totalP = document.getElementById('pedido-total');
                itensList.innerHTML = '';
                if (pedido?.itens?.length > 0) {
                    let total = 0;
                    pedido.itens.forEach(item => {
                        const quantidade = parseFloat(item.quantidade) || 0;
                        // CORREÇÃO: O seu JSON usa 'preco_unitario_momento' ou 'preco_unitario'
                        const preco = parseFloat(item.preco_unitario_momento || item.preco_unitario) || 0; 
                        const itemTotal = quantidade * preco;
                        total += itemTotal;
                        itensList.innerHTML += `<li><span>${quantidade}x ${item.nome}</span> <span>R$ ${itemTotal.toFixed(2)}</span></li>`;
                    });
                    totalP.textContent = `Total: R$ ${total.toFixed(2)}`;
                } else {
                    itensList.innerHTML = '<li>Nenhum item lançado neste pedido.</li>';
                    totalP.textContent = 'Total: R$ 0.00';
                }
            };

            // --- CÓDIGO CORRIGIDO/ADICIONADO ---
            const carregarCardapio = async () => {
                if (cardapioCache) return cardapioCache; // Usa cache
                
                // API /api/garcom/cardapio retorna { success: true, data: [...] }
                const response = await apiFetch('/api/garcom/cardapio');
                if (response?.success && response.data) {
                    cardapioCache = response.data;
                    return cardapioCache;
                } else {
                    alert('Erro ao carregar o cardápio.');
                    return null;
                }
            };
            
            // --- CÓDIGO CORRIGIDO/ADICIONADO ---
            const renderizarCardapio = (categorias) => {
                const container = document.getElementById('cardapio-categorias');
                container.innerHTML = '';
                if (!categorias) {
                    container.innerHTML = '<p>Erro ao carregar cardápio.</p>';
                    return;
                }
                for (const [categoria, itens] of Object.entries(categorias)) {
                    const bloco = document.createElement('div');
                    bloco.className = 'categoria-bloco';
                    let itensHtml = '';
                    itens.forEach(item => {
                        itensHtml += `
                            <div class="item-cardapio">
                                <label for="item-${item.id}">${item.nome} (R$ ${parseFloat(item.preco).toFixed(2)})</label>
                                <input type="number" id="item-${item.id}" name="itens[${item.id}]" min="0" value="0" data-id="${item.id}">
                            </div>
                        `;
                    });
                    bloco.innerHTML = `<h4 class="categoria-titulo">${categoria}</h4>${itensHtml}`;
                    container.appendChild(bloco);
                }
            };
            
            // --- CÓDIGO CORRIGIDO/ADICIONADO (ESTE É O BUG) ---
            formNovoPedido.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btnSubmit = e.target.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Enviando...';

                const formData = new FormData(formNovoPedido);
                const mesaId = formData.get('mesa_id');
                const itens = {};
                
                // Coleta apenas itens com quantidade > 0
                formData.forEach((value, key) => {
                    if (key.startsWith('itens[') && value > 0) {
                        // Extrai o ID do item
                        const id = key.match(/\[(\d+)\]/)[1];
                        itens[id] = parseInt(value, 10);
                    }
                });

                if (Object.keys(itens).length === 0) {
                    alert('Nenhum item selecionado.');
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Lançar Pedido';
                    return;
                }

                // A ROTA CORRETA é /api/pedidos (do PedidoController)
                const response = await apiFetch('/api/pedidos', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        mesa_id: mesaId,
                        itens: itens
                    })
                });

                if (response && response.success) {
                    // *** A CORREÇÃO ESTÁ AQUI ***
                    // NÃO mexemos na lista de "Pedidos Prontos" manualmente.
                    // Apenas fechamos o modal e chamamos a função que 
                    // recarrega TUDO do servidor.
                    fecharModal();
                    atualizarDashboard(); // <-- ISTO É O CORRETO A FAZER
                    
                } else {
                    alert(response?.message || 'Erro ao lançar pedido.');
                }
                
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Lançar Pedido';
            });
            
            // --- FIM DA CORREÇÃO ---


            const fecharModal = () => {
                modal.style.display = 'none';
                conteudoDetalhes.style.display = 'none';
                conteudoNovoPedido.style.display = 'none';
            };

            closeModalBtn.addEventListener('click', fecharModal);
            window.addEventListener('click', (event) => { if (event.target == modal) fecharModal(); });
            
            // --- CÓDIGO CORRIGIDO/ADICIONADO ---
            btnAdicionarItens.addEventListener('click', async () => {
                // Carrega o cardápio (do cache ou da API)
                const cardapio = await carregarCardapio();
                if (cardapio) {
                    // Renderiza o cardápio no formulário
                    renderizarCardapio(cardapio);
                    // Alterna a visão do modal
                    conteudoDetalhes.style.display = 'none';
                    conteudoNovoPedido.style.display = 'block';
                }
            });
            
            // Função central para atualizar tudo
            const atualizarDashboard = () => { 
                carregarMesas(); 
                carregarPedidosProntos(); 
            };
            
            // Carga inicial
            atualizarDashboard();
            
            // Polling (busca atualizações) a cada 15 segundos
            setInterval(atualizarDashboard, 5000); 
        });
    </script>
</body>
</html>
