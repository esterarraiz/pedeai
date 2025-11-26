<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Gerenciar Cardápio') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link rel="stylesheet" href="/css/admin_cardapio.css">
    <link rel="stylesheet" href="/css/style.css">
    
    <style>
        .category-header {
            font-size: 1.5rem; font-weight: 600; color: var(--text-dark);
            padding-bottom: 10px; border-bottom: 2px solid var(--border-color);
            margin-top: 40px; margin-bottom: 20px;
        }
        .category-header:first-of-type { margin-top: 10px; }

        .modal-content { text-align: left; }
        .modal-content h3 {
            text-align: center; font-size: 1.5rem; font-weight: 600;
            color: var(--text-dark); margin-bottom: 25px;
        }
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        .item-cell-content {
            display: flex;
            align-items: center;
        }
        /* CSS para a lista de categorias no modal */
        .list-group-item {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex; /* Adicionado para o layout da lista */
            justify-content: space-between;
            align-items: center;
        }
        .btn-remover-categoria {
            margin-left: 10px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

        <main class="main-content">
            <header class="main-header cardapio-header">
                <div class="header-left">
                    <h1>Editar Cardápio</h1>
                </div>

                <div class="header-actions">
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalGerenciarCategorias">
                        <i class="fas fa-layer-group"></i> Gerenciar Categorias
                    </button>

                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAdicionarItem">
                        <i class="fas fa-plus"></i> Adicionar Item
                    </button>

                    <button class="btn btn-amber" data-bs-toggle="modal" data-bs-target="#cardapioOpcoesModal">
                        <i class="fas fa-qrcode"></i> Cardápio Público
                    </button>
                </div>
            </header>




            <div id="feedback-container"></div>
            
            <?php if (isset($_SESSION['feedback_success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['feedback_success']; ?><?php unset($_SESSION['feedback_success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['feedback_error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['feedback_error']; ?><?php unset($_SESSION['feedback_error']); ?></div>
            <?php endif; ?>

            <div id="cardapio-container">
                <p>Carregando cardápio...</p>
            </div>
        </main>
    </div>

    <div class="modal fade" id="modalAdicionarItem" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <h3>Adicionar Novo Item</h3>
                <form id="formAdicionarItem" enctype="multipart/form-data"> 
                    <div class="form-group">
                        <label>Nome do Item</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Preço</label>
                        <input type="text" name="preco" class="form-control" required placeholder="Ex: 15.50 ou 15,50">
                    </div>
                    
                    <div class="form-group">
                        <label>Imagem (Upload)</label>
                        <input type="file" name="imagem" id="add-imagem" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="categoria_id" id="add-categoria_id" class="form-select" required>
                            <option value="" disabled selected>Carregando categorias...</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarItem" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <h3>Editar Item</h3>
                <form id="formEditarItem" enctype="multipart/form-data"> 
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="imagem_url_atual" id="edit-imagem_url_atual"> 
                    <div class="form-group">
                        <label>Nome do Item</label>
                        <input type="text" id="edit-nome" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea id="edit-descricao" name="descricao" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Preço</label>
                        <input type="text" id="edit-preco" name="preco" class="form-control" required placeholder="Ex: 15.50 ou 15,50">
                    </div>
                    
                    <div class="form-group">
                        <label>Nova Imagem (Upload - opcional)</label>
                        <input type="file" name="imagem" id="edit-imagem" class="form-control" accept="image/*">
                        <small class="form-text text-muted">Deixe em branco para manter a imagem atual.</small>
                    </div>
                    <div class="form-group">
                        <label>Categoria</label>
                        <select id="edit-categoria_id" name="categoria_id" class="form-select" required>
                            <option value="" disabled>Selecione uma categoria...</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGerenciarCategorias" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <h3>Gerenciar Categorias</h3>
                
                <form id="formAdicionarCategoria" class="mb-4">
                    <div class="form-group d-flex">
                        <input type="text" name="nome" class="form-control" placeholder="Novo nome da categoria" required style="margin-right: 10px;">
                        <button type="submit" class="btn btn-primary flex-shrink-0">Criar</button>
                    </div>
                    <div id="feedback-categoria" class="mt-2"></div>
                </form>

                <div id="lista-categorias-container">
                    <ul id="lista-categorias" class="list-group">
                        </ul>
                </div>

                <div class="form-actions mt-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="cardapioOpcoesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mx-auto">
            <div class="modal-content p-3">

                <h3 class="text-center mb-3">Cardápio Digital Público</h3>

                <div class="mb-4">
                    <label class="form-label"><strong>Link Público do Cardápio:</strong></label>
                    <div class="input-group">
                        
                        <input id="cardapio-public-url" type="text" class="form-control" readonly 
                            value="<?= isset($empresa['link_publico']) ? htmlspecialchars($empresa['link_publico']) : ''; ?>">
                        
                        <button class="btn btn-outline-secondary" id="copyCardapioUrl">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2">

                    <a href="<?= isset($empresa['link_pdf']) ? htmlspecialchars($empresa['link_pdf']) : ''; ?>"
                        class="btn btn-green"
                        target="_blank">
                            <i class="fas fa-file-pdf"></i> Baixar PDF do Cardápio
                    </a>


                    <button id="btnGerarQrCode" class="btn btn-dark">
                        <i class="fas fa-qrcode"></i> Gerar QR Code
                    </button>

                    <button id="btnImprimirQr" class="btn btn-amber">
                        <i class="fas fa-print"></i> Imprimir QR Code
                    </button>

                </div>

                <div id="qrCodeArea" class="text-center mt-4" style="display:none;">
                    <h5>QR Code do Cardápio</h5>
                    <img id="qrCodeImage" src="" style="max-width:180px;">

                </div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- CONSTANTES GLOBAIS ---
        const apiBaseUrl = '/api/admin/cardapio';
        const cardapioContainer = document.getElementById('cardapio-container');
        const feedbackContainer = document.getElementById('feedback-container');
        const defaultImageUrl = '/images/placeholder.png'; 
        
        // Objetos JS do Bootstrap para controlar os modais
        const modalAdicionar = new bootstrap.Modal(document.getElementById('modalAdicionarItem'));
        const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarItem'));
        const modalGerenciar = new bootstrap.Modal(document.getElementById('modalGerenciarCategorias')); 

        // Elemento HTML do modal (para adicionar listeners)
        const modalEditarElement = document.getElementById('modalEditarItem'); 
        const modalGerenciarElement = document.getElementById('modalGerenciarCategorias'); 

        // Elementos dos formulários
        const formAdicionar = document.getElementById('formAdicionarItem');
        const formEditar = document.getElementById('formEditarItem');
        const formAdicionarCategoria = document.getElementById('formAdicionarCategoria'); 
        const listaCategorias = document.getElementById('lista-categorias'); 
        const feedbackCategoria = document.getElementById('feedback-categoria'); 

        // Selects de categoria
        const selectAddCategoria = document.getElementById('add-categoria_id');
        const selectEditCategoria = document.getElementById('edit-categoria_id');

        // --- FUNÇÕES DE RENDERIZAÇÃO E HELPERS ---

        function mostrarFeedback(mensagem, tipo = 'success') {
            const alertClass = (tipo === 'success') ? 'alert-success' : 'alert-danger';
            feedbackContainer.innerHTML = `<div class="alert ${alertClass}">${mensagem}</div>`;
            setTimeout(() => { feedbackContainer.innerHTML = ''; }, 5000);
        }

        function mostrarFeedbackCategoria(mensagem, tipo = 'success') {
            const alertClass = (tipo === 'success') ? 'alert-success' : 'alert-danger';
            feedbackCategoria.innerHTML = `<div class="alert ${alertClass}">${mensagem}</div>`;
            setTimeout(() => { feedbackCategoria.innerHTML = ''; }, 5000);
        }

        function escapeHTML(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/[&<>"']/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m]));
        }

        /**
         * Preenche os <select> de categoria nos modais de item.
         */
        function renderizarCategorias(categorias) {
            const selects = [selectAddCategoria, selectEditCategoria];
            selects.forEach(select => {
                // Removendo a lógica de placeholder complexa, garantindo limpeza e adição
                select.innerHTML = ''; 
                
                const defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.textContent = categorias && categorias.length > 0 ? 'Selecione uma categoria...' : 'Nenhuma categoria encontrada';
                defaultOption.disabled = true;
                defaultOption.selected = true;
                select.appendChild(defaultOption);
                
                if (categorias && categorias.length > 0) {
                    categorias.forEach(cat => {
                        const option = new Option(cat.nome, cat.id);
                        select.add(option);
                    });
                }
            });
        }
        
        /**
         * Constrói a lista de categorias no modal de gerenciamento.
         */
        function renderizarListaCategorias(categorias) {
            listaCategorias.innerHTML = '';
            if (categorias.length === 0) {
                listaCategorias.innerHTML = '<li class="list-group-item">Nenhuma categoria cadastrada.</li>';
                return;
            }

            categorias.forEach(cat => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <span>${escapeHTML(cat.nome)}</span>
                    <button class="btn btn-sm btn-danger btn-remover-categoria" 
                            data-id="${cat.id}" data-nome="${escapeHTML(cat.nome)}">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                listaCategorias.appendChild(li);
            });
        }

        function renderizarCardapio(cardapioAgrupado) {
            cardapioContainer.innerHTML = ''; 

            if (!cardapioAgrupado || Object.keys(cardapioAgrupado).length === 0) {
                cardapioContainer.innerHTML = '<div class="alert alert-info text-center mt-4">Nenhum item encontrado no cardápio.</div>';
                return;
            }

            let html = '';
            for (const [categoriaNome, itens] of Object.entries(cardapioAgrupado)) {
                
                let itensHtml = '';
                itens.forEach(item => {
                    const precoFormatado = parseFloat(item.preco).toFixed(2).replace('.', ',');
                    
                    const imageUrl = item.imagem_url ? escapeHTML(item.imagem_url) : defaultImageUrl;
                    
                    itensHtml += `
                        <tr>
                            <td>
                                <div class="item-cell-content">
                                    <img src="${imageUrl}" alt="${escapeHTML(item.nome)}" class="item-image" onerror="this.onerror=null;this.src='${defaultImageUrl}';" />
                                    <strong>${escapeHTML(item.nome)}</strong>
                                </div>
                            </td>
                            <td>${escapeHTML(item.descricao || '')}</td>
                            <td class="text-end">R$ ${precoFormatado}</td>
                            <td class="actions-cell" style="justify-content: center;">
                                <a class="action-icon btn-editar" title="Editar"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalEditarItem"
                                    data-id="${item.id}"
                                    data-nome="${escapeHTML(item.nome)}"
                                    data-descricao="${escapeHTML(item.descricao || '')}"
                                    data-preco="${item.preco}"
                                    data-categoria_id="${item.categoria_id || ''}"
                                    data-imagem_url="${escapeHTML(item.imagem_url || '')}"> <i class="fas fa-edit"></i>
                                </a>
                                <button class="action-icon btn-remover" title="Remover" data-id="${item.id}" data-nome="${escapeHTML(item.nome)}" style="border:none; background:none; cursor:pointer;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += `
                    <h3 class="category-header">${escapeHTML(categoriaNome)}</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th class="text-end">Preço</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itensHtml}
                            </tbody>
                        </table>
                    </div>
                `;
            }
            cardapioContainer.innerHTML = html;
        }

        // --- FUNÇÃO PRINCIPAL DE CARREGAMENTO ---

        /**
         * Retorna a promessa de dados (data) em caso de sucesso.
         */
        async function carregarDados() {
            try {
                const response = await fetch(apiBaseUrl); 
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderizarCategorias(data.categorias);
                    renderizarCardapio(data.cardapio);
                    return data; // Retorna os dados para serem usados no modal de categoria
                } else {
                    mostrarFeedback(data.message || 'Não foi possível carregar os dados.', 'danger');
                    return null;
                }
            } catch (error) {
                console.error('Erro ao carregar dados:', error);
                mostrarFeedback('Erro de comunicação com o servidor. (verifique F12 > Rede)', 'danger');
                return null;
            }
        }

        // --- EVENT LISTENERS (Ações do Usuário) ---

        // (ADICIONAR) Envio do formulário
        formAdicionar.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch(apiBaseUrl, { 
                    method: 'POST',
                    body: formData 
                });
                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    modalAdicionar.hide(); 
                    mostrarFeedback(result.message);
                    carregarDados(); 
                    this.reset();
                } else {
                    mostrarFeedback(result.message || 'Erro ao adicionar.', 'danger');
                }
            } catch (error) {
                console.error('Erro ao adicionar:', error);
                mostrarFeedback('Erro de comunicação ao adicionar.', 'danger');
            }
        });

        // (EDITAR) Preencher o modal
        modalEditarElement.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;
            this.querySelector('#edit-id').value = button.getAttribute('data-id');
            this.querySelector('#edit-nome').value = button.getAttribute('data-nome');
            this.querySelector('#edit-descricao').value = button.getAttribute('data-descricao');
            this.querySelector('#edit-preco').value = String(button.getAttribute('data-preco')).replace(',', '.');
            this.querySelector('#edit-categoria_id').value = button.getAttribute('data-categoria_id');
            
            this.querySelector('#edit-imagem_url_atual').value = button.getAttribute('data-imagem_url') || '';

            this.querySelector('#edit-imagem').value = null; 
        });

        // (EDITAR) Enviar o formulário
        formEditar.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const id = formData.get('id'); 
            
            try {
                const response = await fetch(`${apiBaseUrl}/${id}`, { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    modalEditar.hide(); 
                    mostrarFeedback(result.message);
                    carregarDados(); 
                } else {
                    mostrarFeedback(result.message || 'Erro ao atualizar.', 'danger');
                }
            } catch (error) {
                console.error('Erro ao editar:', error);
                mostrarFeedback('Erro de comunicação ao atualizar.', 'danger');
            }
        });

        // (REMOVER ITEM) Delegação de evento 
        cardapioContainer.addEventListener('click', async function(e) {
            const btnRemover = e.target.closest('.btn-remover');
            if (btnRemover) {
                const id = btnRemover.dataset.id;
                const nome = btnRemover.dataset.nome;
                
                if (confirm(`Tem certeza que deseja remover o item "${nome}"?`)) {
                    try {
                        const response = await fetch(`${apiBaseUrl}/${id}`, { method: 'DELETE' }); 
                        const result = await response.json();

                        if (response.ok && result.status === 'success') {
                            mostrarFeedback(result.message);
                            carregarDados(); 
                        } else {
                            mostrarFeedback(result.message || 'Erro ao remover.', 'danger');
                        }
                    } catch (error) {
                        console.error('Erro ao remover:', error);
                        mostrarFeedback('Erro de comunicação ao remover.', 'danger');
                    }
                }
            }
        });
        
        // =========================================================
        // == EVENT LISTENERS PARA GERENCIAMENTO DE CATEGORIAS (NOVO) ==
        // =========================================================

        // 1. Listener para carregar categorias ao abrir o modal de gerenciamento
        modalGerenciarElement.addEventListener('show.bs.modal', async function() {
            // Garante que a lista de categorias no modal esteja atualizada
            const data = await carregarDados(); 
            if (data && data.categorias) {
                renderizarListaCategorias(data.categorias);
            }
        });

        // 2. (CRIAR CATEGORIA) Envio do formulário
        formAdicionarCategoria.addEventListener('submit', async function(e) {
            e.preventDefault();
            const nomeInput = this.querySelector('input[name="nome"]');
            const nome = nomeInput.value;
            
            if (!nome) {
                mostrarFeedbackCategoria('O nome da categoria é obrigatório.', 'danger');
                return;
            }

            try {
                const response = await fetch(`${apiBaseUrl}/categorias`, { // POST /api/admin/cardapio/categorias
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome: nome })
                });
                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    mostrarFeedbackCategoria(result.message);
                    formAdicionarCategoria.reset();
                    
                    // Recarrega TUDO e atualiza as duas listas (dropdown e gerenciamento)
                    carregarDados().then(data => {
                        if (data && data.categorias) renderizarListaCategorias(data.categorias);
                    }); 
                } else {
                    mostrarFeedbackCategoria(result.message || 'Erro ao criar categoria.', 'danger');
                }
            } catch (error) {
                mostrarFeedbackCategoria('Erro de comunicação ao criar.', 'danger');
            }
        });

        // 3. (REMOVER CATEGORIA) Delegação de evento
        listaCategorias.addEventListener('click', async function(e) {
            const btnRemoverCat = e.target.closest('.btn-remover-categoria');
            if (!btnRemoverCat) return;
            
            const id = btnRemoverCat.dataset.id;
            const nome = btnRemoverCat.dataset.nome;

            if (confirm(`Tem certeza que deseja remover a categoria "${nome}"? Isso só será possível se não houver itens vinculados.`)) {
                try {
                    const response = await fetch(`${apiBaseUrl}/categorias/${id}`, { method: 'DELETE' }); // DELETE /api/admin/cardapio/categorias/{id}
                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        mostrarFeedbackCategoria(result.message);
                        
                        // Recarrega TUDO
                        carregarDados().then(data => {
                            if (data && data.categorias) renderizarListaCategorias(data.categorias);
                        });
                    } else {
                        mostrarFeedbackCategoria(result.message || 'Erro ao remover.', 'danger');
                    }
                } catch (error) {
                    mostrarFeedbackCategoria('Erro de comunicação ao remover.', 'danger');
                }
            }
        });


        // 4. Lógica do QR Code (Ajuste para funcionar dentro do DOMContentLoaded)

        // Event listener para o botão Gerar QR Code
        // Event listener para o botão Gerar QR Code (versão substituta, mais robusta)
        document.getElementById('btnGerarQrCode').addEventListener('click', function () {
            const url = document.getElementById('cardapio-public-url').value;
            const qrArea = document.getElementById('qrCodeArea');

            if (!url || url.trim() === "") {
                alert("Nenhum link disponível para gerar o QR Code!");
                return;
            }

            // Limpa a área e cria um container direto visível para o QR
            qrArea.innerHTML = '<h5>QR Code do Cardápio</h5><div id="qrContainer" style="display:inline-block;margin-top:10px;"></div>';
            const qrContainer = document.getElementById('qrContainer');

            // Remove QR anterior (se existir)
            qrContainer.innerHTML = '';

            // Cria novo QR (QRCode.js)
            try {
                new QRCode(qrContainer, {
                    text: url,
                    width: 180,
                    height: 180
                });
            } catch (err) {
                console.error('Erro ao instanciar QRCode:', err);
                alert('Erro ao gerar o QR Code. Veja o Console (F12).');
                return;
            }

            // Se quisermos sempre ter um <img id="qrCodeImage"> com base64 (para enviar ao servidor),
            // aguardamos brevemente e então transformamos canvas em dataURL quando necessário.
            setTimeout(() => {
                // Busca img ou canvas dentro do qrContainer
                const img = qrContainer.querySelector('img');
                const canvas = qrContainer.querySelector('canvas');
                let dataUrl = '';

                if (img && img.src) {
                    dataUrl = img.src;
                } else if (canvas) {
                    try {
                        dataUrl = canvas.toDataURL('image/png');
                    } catch (e) {
                        console.error('Erro ao converter canvas para dataURL:', e);
                    }
                }

                // Coloca a imagem final no elemento visível #qrCodeImage (se existir)
                const qrImgEl = document.getElementById('qrCodeImage');
                if (qrImgEl) {
                    if (dataUrl) {
                        qrImgEl.src = dataUrl;
                    } else {
                        // Caso não consiga dataURL, tentamos usar a imagem criada diretamente (caso exista)
                        if (img && img.src) qrImgEl.src = img.src;
                    }
                }

                // Mostra a área do QR
                qrArea.style.display = 'block';
            }, 150); // 150ms deve ser suficiente; é pequeno, mas mais seguro que 50ms
        });


        // Event listener para o botão Copiar URL
           document.addEventListener("click", async function (e) {
            const btn = e.target.closest("#btnImprimirQr");
            if (!btn) return;

            console.log("Botão imprimir foi clicado!");

            // Captura o QR onde ele realmente está
            const qrContainer = document.querySelector("#qrContainer img, #qrContainer canvas");

            if (!qrContainer) {
                alert("Gere o QR Code primeiro.");
                return;
            }

            let qrBase64 = "";

            if (qrContainer.tagName === "IMG") {
                qrBase64 = qrContainer.src;
            } else {
                qrBase64 = qrContainer.toDataURL("image/png");
            }

            if (!qrBase64.startsWith("data:image")) {
                alert("QR inválido. Gere novamente.");
                return;
            }

            const companyName = "<?= addslashes($empresa['nome_empresa'] ?? ''); ?>";
            const titulo = "Escaneie o QR code e confira nosso cardápio";

            try {
                const res = await fetch("/admin/cardapio/gerar-qrcode-pdf", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ qr: qrBase64, titulo, companyName })
                });

                const blob = await res.blob();
                const url = URL.createObjectURL(blob);

                const a = document.createElement("a");
                a.href = url;
                a.download = "qrcode_cardapio.pdf";
                a.click();

                URL.revokeObjectURL(url);

            } catch (err) {
                console.error("Erro ao gerar PDF:", err);
                alert("Falha ao gerar PDF.");
            }
        });





        // --- INICIALIZAÇÃO ---
        carregarDados(); 
    });
    </script>
</body>

</html>