<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($data['funcionario']) ? 'Editar' : 'Novo' ?> Funcionário</title>
    
    <!-- CSS Global e do Formulário -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>
        
        <main class="main-content">
            <header class="main-header">
                <h1><?= isset($data['funcionario']) ? 'Editar Funcionário' : 'Novo Funcionário' ?></h1>
            </header>

            <div class="form-container">
                <div class="form-card">
                    <form action="<?= isset($data['funcionario']) ? '/funcionarios/atualizar' : '/funcionarios/criar' ?>" method="POST">
                        
                        <?php if (isset($data['funcionario'])): ?>
                            <input type="hidden" name="id" value="<?= $data['funcionario']['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($data['funcionario']['nome'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($data['funcionario']['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="cargo_id">Função</label>
                            <select id="cargo_id" name="cargo_id" required>
                                <option value="">Selecione um cargo...</option>
                                <?php foreach ($data['cargos'] as $cargo): ?>
                                    <option value="<?= $cargo['id'] ?>" <?= (($data['funcionario']['cargo_id'] ?? '') == $cargo['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cargo['nome_cargo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- O campo de senha agora só aparece no modo de CRIAÇÃO -->
                        <?php if (!isset($data['funcionario'])): ?>
                            <div class="form-group">
                                <label for="senha">Senha</label>
                                <input type="password" id="senha" name="senha" required>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <a href="/funcionarios" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

