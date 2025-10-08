<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Gerenciar Cardápio') ?></title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/funcionarios.css">
    <link rel="stylesheet" href="/css/form.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
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
    </style>
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
            </header>

            <?php if (isset($_SESSION['feedback_success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['feedback_success']; ?><?php unset($_SESSION['feedback_success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['feedback_error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['feedback_error']; ?><?php unset($_SESSION['feedback_error']); ?></div>
            <?php endif; ?>

            <?php if (empty($cardapio)): ?>
                <div class="alert alert-info text-center mt-4">Nenhum item encontrado no cardápio.</div>
            <?php else: ?>
                <?php foreach ($cardapio as $categoriaNome => $itens): ?>
                    <h3 class="category-header"><?= htmlspecialchars($categoriaNome) ?></h3>
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
                                <?php foreach ($itens as $item): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($item['nome']) ?></strong></td>
                                        <td><?= htmlspecialchars($item['descricao'] ?? '') ?></td>
                                        <td class="text-end">R$ <?= number_format($item['preco'], 2, ',', '.') ?></td>
                                        <td class="actions-cell" style="justify-content: center;">
                                            <a class="action-icon btn-editar" title="Editar"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarItem"
                                                data-id="<?= $item['id'] ?>"
                                                data-nome="<?= htmlspecialchars($item['nome']) ?>"
                                                data-descricao="<?= htmlspecialchars($item['descricao'] ?? '') ?>"
                                                data-preco="<?= $item['preco'] ?>"
                                                data-categoria_id="<?= $item['categoria_id'] ?? '' ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="/dashboard/admin/cardapio/remover" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este item?');">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="action-icon" title="Remover" style="border:none; background:none; cursor:pointer;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <div class="modal fade" id="modalAdicionarItem" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <h3>Adicionar Novo Item</h3>
                <form action="/dashboard/admin/cardapio/adicionar" method="POST">
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
                        <select name="categoria_id" class="form-select" required>
                            <option value="" disabled selected>Selecione uma categoria...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
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
                <form action="/dashboard/admin/cardapio/editar" method="POST">
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
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
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
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEditar = document.getElementById('modalEditarItem');
        if(modalEditar) {
            modalEditar.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var nome = button.getAttribute('data-nome');
                var descricao = button.getAttribute('data-descricao');
                var preco = button.getAttribute('data-preco');
                var categoria_id = button.getAttribute('data-categoria_id');

                var modal = this;
                modal.querySelector('#edit-id').value = id;
                modal.querySelector('#edit-nome').value = nome;
                modal.querySelector('#edit-descricao').value = descricao;
                modal.querySelector('#edit-preco').value = String(preco).replace(',', '.');
                modal.querySelector('#edit-categoria_id').value = categoria_id;
            });
        }
    });
    </script>
</body>
</html>