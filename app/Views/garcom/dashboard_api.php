<?php
// Salve este arquivo em: app/Views/garcom/dashboard_api.php

// Cabeçalho básico para a página
$pageTitle = "Dashboard do Garçom";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
    <style>
        /* CSS para um visual limpo e moderno */
        :root {
            --cor-primaria: #2c3e50;
            --cor-secundaria: #3498db;
            --cor-sucesso: #2ecc71;
            --cor-aviso: #f1c40f;
            --cor-perigo: #e74c3c;
            --cor-fundo: #ecf0f1;
            --cor-texto: #333;
            --sombra: 0 4px 8px rgba(0,0,0,0.1);
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: var(--cor-primaria);
        }
        .logout-btn {
            background-color: var(--cor-perigo);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        /* Grid das Mesas */
        #mesas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .mesa {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: var(--sombra);
        }
        .mesa:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .mesa[data-status="disponivel"] { background-color: var(--cor-sucesso); }
        .mesa[data-status="ocupada"] { background-color: var(--cor-perigo); }
        .mesa[data-status="aguardando_pagamento"] { background-color: var(--cor-aviso); color: #333; }

        /* Notificações de Pedidos Prontos */
        #pedidos-prontos-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--sombra);
        }
        #pedidos-prontos-container h2 { margin-top: 0; color: var(--cor-primaria); }
        .pedido-pronto {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .pedido-pronto:last-child { border-bottom: none; }
        .pedido-pronto button {
            background-color: var(--cor-secundaria);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Estilos do Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .close-btn {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover { color: #333; }
        
        #detalhes-pedido-itens { list-style: none; padding: 0; }
        #detalhes-pedido-itens li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        #pedido-total { text-align: right; font-weight: bold; font-size: 1.2rem; margin-top: 15px; }

        #form-novo-pedido #cardapio-categorias { margin-top: 15px; max-height: 40vh; overflow-y: auto; padding-right: 10px; }
        .categoria-bloco { margin-bottom: 15px; }
        .categoria-titulo { font-weight: bold; color: var(--cor-primaria); border-bottom: 2px solid var(--cor-secundaria); padding-bottom: 5px; margin-bottom: 10px; }
        .item-cardapio { display: flex; justify-content: space-between; align-items: center; padding: 5px; }
        .item-cardapio input[type="number"] { width: 60px; text-align: center; }

        .modal-footer { text-align: right; margin-top: 30px; }
        .btn { padding: 12px 20px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; }
        .btn-primary { background-color: var(--cor-secundaria); color: white; }
        .btn-success { background-color: var(--cor-sucesso); color: white; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Dashboard do Garçom</h1>
            <a href="/logout" class="logout-btn">Sair</a>
        </div>

        <div id="pedidos-prontos-container">
            <h2>Pedidos Prontos para Entrega</h2>
            <div id="lista-pedidos-prontos"><p>Nenhum pedido pronto no momento.</p></div>
        </div>

        <h2 style="margin-top: 30px; color: var(--cor-primaria);">Mesas</h2>
        <div id="mesas-grid"></div>
    </div>

    <div id="modal-mesa" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-titulo"></h2>
                <span class="close-btn">&times;</span>
            </div>
            
            <div id="conteudo-detalhes">
                <h4>Pedido Atual</h4>
                <ul id="detalhes-pedido-itens"></ul>
                <p id="pedido-total"></p>
                <div class="modal-footer">
                    <button id="btn-adicionar-itens" class="btn btn-primary">Adicionar Itens</button>
                </div>
            </div>

            <div id="conteudo-novo-pedido">
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
                try {
                    const response = await fetch(url, options);
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || `Erro HTTP: ${response.status}`);
                    }
                    return response.json();
                } catch (error) {
                    console.error("Erro na API:", error);
                    alert(`Erro de comunicação: ${error.message}`);
                    return null;
                }
            };

            const carregarMesas = async () => {
                const response = await apiFetch('/api/garcom/mesas');
                if (!response?.success) return;
                mesasGrid.innerHTML = '';
                response.data.forEach(mesa => {
                    const mesaDiv = document.createElement('div');
                    mesaDiv.className = 'mesa';
                    mesaDiv.dataset.id = mesa.id;
                    mesaDiv.dataset.status = mesa.status;
                    mesaDiv.textContent = `Mesa ${mesa.numero}`;
                    mesaDiv.addEventListener('click', () => abrirModalMesa(mesa.id, mesa.numero, mesa.status));
                    mesasGrid.appendChild(mesaDiv);
                });
            };

            const carregarPedidosProntos = async () => {
                const response = await apiFetch('/api/garcom/pedidos/prontos');
                if (!response?.success) return;
                pedidosProntosContainer.innerHTML = '';
                if (response.data.length === 0) {
                    pedidosProntosContainer.innerHTML = '<p>Nenhum pedido pronto no momento.</p>';
                    return;
                }
                response.data.forEach(pedido => {
                    const pedidoDiv = document.createElement('div');
                    pedidoDiv.className = 'pedido-pronto';
                    pedidoDiv.innerHTML = `<span>Pedido da <strong>Mesa ${pedido.mesa_numero}</strong> está pronto!</span>`;
                    const btn = document.createElement('button');
                    btn.textContent = 'Marcar como Entregue';
                    btn.onclick = () => marcarEntregue(pedido.pedido_id);
                    pedidoDiv.appendChild(btn);
                    pedidosProntosContainer.appendChild(pedidoDiv);
                });
            };

            const abrirModalMesa = async (mesaId, mesaNumero, mesaStatus) => {
                modalTitulo.textContent = `Mesa ${mesaNumero}`;
                formMesaIdInput.value = mesaId;
                
                if (mesaStatus === 'ocupada') {
                    conteudoDetalhes.style.display = 'block';
                    conteudoNovoPedido.style.display = 'none';
                    const response = await apiFetch(`/api/garcom/mesas/${mesaId}`);
                    if (response?.success) renderizarDetalhesPedido(response.data.pedido);
                } else {
                    conteudoDetalhes.style.display = 'none';
                    conteudoNovoPedido.style.display = 'block';
                    await carregarCardapio();
                }
                modal.style.display = 'block';
            };

            const renderizarDetalhesPedido = (pedido) => {
                const itensList = document.getElementById('detalhes-pedido-itens');
                const totalP = document.getElementById('pedido-total');
                itensList.innerHTML = '';
                if (pedido?.itens?.length > 0) {
                    let total = 0;
                    pedido.itens.forEach(item => {
                        const itemTotal = item.quantidade * item.preco_unitario_momento;
                        total += itemTotal;
                        itensList.innerHTML += `<li><span>${item.quantidade}x ${item.item_nome}</span> <span>R$ ${itemTotal.toFixed(2)}</span></li>`;
                    });
                    totalP.textContent = `Total: R$ ${total.toFixed(2)}`;
                } else {
                    itensList.innerHTML = '<li>Nenhum item lançado neste pedido.</li>';
                    totalP.textContent = 'Total: R$ 0.00';
                }
            };

            const carregarCardapio = async () => {
                if (cardapioCache) {
                    renderizarCardapio(cardapioCache);
                    return;
                }
                const response = await apiFetch('/api/garcom/cardapio');
                if (response?.success) {
                    cardapioCache = response.data;
                    renderizarCardapio(cardapioCache);
                }
            };

            const renderizarCardapio = (categorias) => {
                const container = document.getElementById('cardapio-categorias');
                container.innerHTML = '';
                for (const categoriaNome in categorias) {
                    const bloco = document.createElement('div');
                    bloco.className = 'categoria-bloco';
                    bloco.innerHTML = `<h5 class="categoria-titulo">${categoriaNome}</h5>`;
                    categorias[categoriaNome].forEach(item => {
                        bloco.innerHTML += `
                            <div class="item-cardapio" data-id="${item.id}">
                                <span>${item.nome} - R$ ${parseFloat(item.preco).toFixed(2)}</span>
                                <input type="number" min="0" value="0" name="itens[${item.id}]">
                            </div>`;
                    });
                    container.appendChild(bloco);
                }
            };

            const marcarEntregue = async (pedidoId) => {
                const response = await apiFetch('/api/garcom/pedidos/marcar-entregue', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: pedidoId })
                });
                if (response?.success) {
                    alert('Pedido entregue com sucesso!');
                    atualizarDashboard();
                }
            };

            formNovoPedido.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formNovoPedido);
                const mesaId = formData.get('mesa_id');
                const itens = [];
                formData.forEach((value, key) => {
                    const quantidade = parseInt(value);
                    if (quantidade > 0 && key.startsWith('itens[')) {
                        const id = key.match(/\[(\d+)\]/)[1];
                        itens.push({ id: parseInt(id), quantidade: quantidade });
                    }
                });

                if (itens.length === 0) {
                    alert("Por favor, selecione pelo menos um item.");
                    return;
                }

                const response = await apiFetch('/api/garcom/pedidos', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ mesa_id: mesaId, itens: itens })
                });

                if (response?.success) {
                    alert('Pedido lançado com sucesso!');
                    fecharModal();
                    atualizarDashboard();
                }
            });

            const fecharModal = () => {
                modal.style.display = 'none';
                formNovoPedido.reset();
            };
            
            closeModalBtn.onclick = fecharModal;
            window.onclick = (event) => { if (event.target == modal) fecharModal(); };
            btnAdicionarItens.onclick = async () => {
                conteudoDetalhes.style.display = 'none';
                conteudoNovoPedido.style.display = 'block';
                await carregarCardapio();
            };

            const atualizarDashboard = () => {
                carregarMesas();
                carregarPedidosProntos();
            };
            
            // Carregamento inicial e atualização periódica
            atualizarDashboard();
            setInterval(atualizarDashboard, 15000); // Atualiza a cada 15 segundos
        });
    </script>
</body>
</html>