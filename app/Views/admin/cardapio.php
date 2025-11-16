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
    
    
    

    </head>


<body>
    <div class="dashboard-container">
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Editar Cardápio</h1>
                <button class="btn btn-success" style="margin-left: auto;" data-bs-toggle="modal" data-bs-target="#modalAdicionarItem">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>
                <button class="btn btn-amber" style="margin-left: 15px;"
                        data-bs-toggle="modal" 
                        data-bs-target="#cardapioOpcoesModal">
                    <i class="fas fa-qrcode"></i> Cardápio Público
                </button>

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
                <form id="formAdicionarItem">
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
                <form id="formEditarItem">
                    <input type="hidden" name="id" id="edit-id">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    
    <script>
        function mostrarFeedback(mensagem, tipo = 'success') {
            const feedbackContainer = document.getElementById('feedback-container'); 
            if (!feedbackContainer) return; 
            
            const alertClass = (tipo === 'success') ? 'alert-success' : 'alert-danger';
            feedbackContainer.innerHTML = `<div class="alert ${alertClass}">${mensagem}</div>`;
            setTimeout(() => { feedbackContainer.innerHTML = ''; }, 5000);
        }
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- CONSTANTES GLOBAIS ---
            const apiBaseUrl = '/api/admin/cardapio';
            const cardapioContainer = document.getElementById('cardapio-container');
            const feedbackContainer = document.getElementById('feedback-container');
            
            // Objetos JS do Bootstrap para controlar os modais
            const modalAdicionar = new bootstrap.Modal(document.getElementById('modalAdicionarItem'));
            const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarItem'));

            // *** CORREÇÃO APLICADA AQUI ***
            // Elemento HTML do modal (para adicionar listeners)
            const modalEditarElement = document.getElementById('modalEditarItem'); 

            // Elementos dos formulários
            const formAdicionar = document.getElementById('formAdicionarItem');
            const formEditar = document.getElementById('formEditarItem');

            // Selects de categoria
            const selectAddCategoria = document.getElementById('add-categoria_id');
            const selectEditCategoria = document.getElementById('edit-categoria_id');

            // --- FUNÇÕES DE RENDERIZAÇÃO ---

            /**
             * Preenche os <select> de categoria nos modais.
             */
            function renderizarCategorias(categorias) {
                const selects = [selectAddCategoria, selectEditCategoria];
                selects.forEach(select => {
                    const placeholder = select.options[0];
                    select.innerHTML = '';
                    select.appendChild(placeholder);
                    
                    if (categorias && categorias.length > 0) {
                        placeholder.text = 'Selecione uma categoria...';
                        categorias.forEach(cat => {
                            const option = new Option(cat.nome, cat.id);
                            select.add(option);
                        });
                    } else {
                        placeholder.text = 'Nenhuma categoria encontrada';
                    }
                });
            }

            /**
             * Constrói as tabelas de cardápio dinamicamente no container.
             */
            function renderizarCardapio(cardapioAgrupado) {
                cardapioContainer.innerHTML = ''; // Limpa o "Carregando..."

                if (!cardapioAgrupado || Object.keys(cardapioAgrupado).length === 0) {
                    cardapioContainer.innerHTML = '<div class="alert alert-info text-center mt-4">Nenhum item encontrado no cardápio.</div>';
                    return;
                }

                let html = '';
                for (const [categoriaNome, itens] of Object.entries(cardapioAgrupado)) {
                    
                    let itensHtml = '';
                    itens.forEach(item => {
                        const precoFormatado = parseFloat(item.preco).toFixed(2).replace('.', ',');
                        itensHtml += `
                            <tr>
                                <td><strong>${escapeHTML(item.nome)}</strong></td>
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
                                        data-categoria_id="${item.categoria_id || ''}">
                                        <i class="fas fa-edit"></i>
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
             * Busca os dados da API (GET) e chama as funções de renderização.
             */
            async function carregarDados() {
                try {
                    const response = await fetch(apiBaseUrl); // GET /api/admin/cardapio
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        renderizarCategorias(data.categorias);
                        renderizarCardapio(data.cardapio);
                    } else {
                        mostrarFeedback(data.message || 'Não foi possível carregar os dados.', 'danger');
                    }
                } catch (error) {
                    console.error('Erro ao carregar dados:', error);
                    mostrarFeedback('Erro de comunicação com o servidor. (verifique F12 > Rede)', 'danger');
                }
            }


            /**
             * Evita XSS (Cross-Site Scripting) ao inserir dados no HTML.
             */
            function escapeHTML(str) {
                if (typeof str !== 'string') return '';
                return str.replace(/[&<>"']/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m]));
            }

            // --- EVENT LISTENERS (Ações do Usuário) ---

            // (ADICIONAR) Envio do formulário
            formAdicionar.addEventListener('submit', async function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const dados = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch(apiBaseUrl, { // POST /api/admin/cardapio
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(dados)
                    });
                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        modalAdicionar.hide(); // Usa o objeto Bootstrap
                        mostrarFeedback(result.message);
                        carregarDados(); // Recarrega a lista
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
            // *** CORREÇÃO APLICADA AQUI ***
            // Usamos 'modalEditarElement' (o elemento HTML) para o listener
            modalEditarElement.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;
                this.querySelector('#edit-id').value = button.getAttribute('data-id');
                this.querySelector('#edit-nome').value = button.getAttribute('data-nome');
                this.querySelector('#edit-descricao').value = button.getAttribute('data-descricao');
                this.querySelector('#edit-preco').value = String(button.getAttribute('data-preco')).replace(',', '.');
                this.querySelector('#edit-categoria_id').value = button.getAttribute('data-categoria_id');
            });

            // (EDITAR) Enviar o formulário
            formEditar.addEventListener('submit', async function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const dados = Object.fromEntries(formData.entries());
                const id = dados.id;

                try {
                    const response = await fetch(`${apiBaseUrl}/${id}`, { // PUT /api/admin/cardapio/{id}
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(dados)
                    });
                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        modalEditar.hide(); // Usa o objeto Bootstrap
                        mostrarFeedback(result.message);
                        carregarDados(); // Recarrega a lista
                    } else {
                        mostrarFeedback(result.message || 'Erro ao atualizar.', 'danger');
                    }
                } catch (error) {
                    console.error('Erro ao editar:', error);
                    mostrarFeedback('Erro de comunicação ao atualizar.', 'danger');
                }
            });

            // (REMOVER) Delegação de evento (escuta cliques no container principal)
            cardapioContainer.addEventListener('click', async function(e) {
                const btnRemover = e.target.closest('.btn-remover');
                if (btnRemover) {
                    const id = btnRemover.dataset.id;
                    const nome = btnRemover.dataset.nome;
                    
                    if (confirm(`Tem certeza que deseja remover o item "${nome}"?`)) {
                        try {
                            const response = await fetch(`${apiBaseUrl}/${id}`, { method: 'DELETE' }); // DELETE /api/admin/cardapio/{id}
                            const result = await response.json();

                            if (response.ok && result.status === 'success') {
                                mostrarFeedback(result.message);
                                carregarDados(); // Recarrega a lista
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

            // --- INICIALIZAÇÃO ---
            carregarDados(); // Carrega o cardápio ao iniciar a página
            
            const qrCodeContainer = document.getElementById('qrcode-image-container');
            let qrCodeGerado = false; 

            // -----------------------------
            //  GERAR QR CODE
            // -----------------------------
            document.getElementById('btnGerarQrCode').addEventListener('click', function () {

                const url = document.getElementById('cardapio-public-url').value;
                const qrArea = document.getElementById('qrCodeArea');
                const imgElement = document.getElementById('qrCodeImage');

                if (!url || url.trim() === "") {
                    alert("Nenhum link disponível para gerar o QR Code!");
                    return;
                }

                // Gera o QR Code temporário em um container invisível
                const tempContainer = document.createElement("div");

                const qr = new QRCode(tempContainer, {
                    text: url,
                    width: 180,
                    height: 180
                });

                // Aguarda a imagem ser gerada (QRCode.js faz isso assincronamente)
                setTimeout(() => {
                    const img = tempContainer.querySelector("img");

                    if (img) {
                        imgElement.src = img.src; // coloca o QR gerado na tag <img>
                        qrArea.style.display = "block"; // exibe área
                    } else {
                        alert("Erro ao gerar o QR Code!");
                    }
                }, 200);
            });
            // BOTÃO DE COPIAR DENTRO DO DOMContentLoaded
            document.getElementById("copyCardapioUrl").addEventListener("click", function () {
                const input = document.getElementById("cardapio-public-url");
                if (!input.value) {
                    alert("Nenhum link disponível para copiar.");
                    return;
                }

                navigator.clipboard.writeText(input.value)
                    .then(() => alert("Link copiado!"))
                    .catch(() => alert("Não foi possível copiar."));
            });
            document.getElementById('btnImprimirQr').addEventListener('click', async function () {
            const qrBase64 = document.getElementById('qrCodeImage').src;
            const companyName = "<?= addslashes($empresa['nome_empresa'] ?? ''); ?>"; // se o PHP renderizar a página
            const titulo = "Escaneie o QR code e confira nosso cardápio";

            if (!qrBase64 || !qrBase64.startsWith('data:image')) {
                alert('Gere o QR Code primeiro.');
                return;
            }

            try {
                const res = await fetch('/admin/cardapio/gerar-qrcode-pdf', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ qr: qrBase64, titulo, companyName })
                });

                if (!res.ok) throw new Error('Erro ao gerar PDF');

                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${(companyName || 'qrcode_cardapio').replace(/\s+/g,'_')}.pdf`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (err) {
                console.error(err);
                alert('Falha ao gerar o PDF.');
            }
        });

       });

       

    </script>
    <!-- MODAL COM OPÇÕES DO CARDÁPIO -->
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
</body>
</html>
