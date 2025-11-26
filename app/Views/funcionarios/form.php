<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Funcionário</title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>
        
        <main class="main-content">
            <header class="main-header">
                <!-- O ID 'form-title' será atualizado pelo JavaScript -->
                <h1 id="form-title">A carregar...</h1>
            </header>

            <div class="form-container">
                <div class="form-card">
                    <!-- O 'id' é usado pelo JS, e o 'data-id' armazena o ID do funcionário (se for edição) -->
                    <form id="funcionario-form" data-id="<?= htmlspecialchars($data['funcionario_id'] ?? '') ?>">
                        
                        <input type="hidden" name="id" value="<?= htmlspecialchars($data['funcionario_id'] ?? '') ?>">

                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="cargo_id">Função</label>
                            <select id="cargo_id" name="cargo_id" required>
                                <option value="">A carregar funções...</option>
                                <!-- Opções serão carregadas pelo JavaScript -->
                            </select>
                        </div>

                        <!-- O campo de senha agora só aparece no modo de CRIAÇÃO -->
                        <div class="form-group" id="senha-group" style="display: none;">
                            <label for="senha">Senha</label>
                            <input type="password" id="senha" name="senha">
                            <small id="senha-helper">Deixe em branco para não alterar a senha atual.</small>
                        </div>

                        <div class="form-actions">
                            <a href="/funcionarios" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success" id="btn-salvar">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript para a SPA do Formulário -->
    <script>
    document.addEventListener('DOMContentLoaded', async function() {
        const form = document.getElementById('funcionario-form');
        const formTitle = document.getElementById('form-title');
        const cargoSelect = document.getElementById('cargo_id');
        const senhaGroup = document.getElementById('senha-group');
        const senhaInput = document.getElementById('senha');
        const senhaHelper = document.getElementById('senha-helper');
        const btnSalvar = document.getElementById('btn-salvar');
        
        // Pega o ID do funcionário a partir do atributo data-id
        const funcionarioId = form.dataset.id;
        const isEditMode = !!funcionarioId; // Converte para booleano

        // --- Função Fetch Genérica ---
        const apiFetch = async (url, options = {}) => {
            try {
                const response = await fetch(url, options);
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Erro no servidor');
                }
                return data;
            } catch (error) {
                console.error('Erro na API Fetch:', error);
                alert(error.message);
                return null;
            }
        };

        // --- Carregar Dados ---
        async function carregarCargos(selectedCargoId = null) {
            const cargos = await apiFetch('/api/cargos');
            cargoSelect.innerHTML = '<option value="">Selecione um cargo...</option>';
            if (cargos && Array.isArray(cargos)) {
                cargos.forEach(cargo => {
                    const option = document.createElement('option');
                    option.value = cargo.id;
                    option.textContent = cargo.nome_cargo;
                    if (cargo.id == selectedCargoId) {
                        option.selected = true;
                    }
                    cargoSelect.appendChild(option);
                });
            }
        }

        async function carregarFormulario() {
            if (isEditMode) {
                formTitle.textContent = 'Editar Funcionário';
                senhaHelper.style.display = 'block';

                const funcionario = await apiFetch(`/api/funcionarios/${funcionarioId}`);
                if (funcionario) {
                    document.getElementById('nome').value = funcionario.nome;
                    document.getElementById('email').value = funcionario.email;
                    // Carrega os cargos e já seleciona o cargo do funcionário
                    await carregarCargos(funcionario.cargo_id);
                }
            } else {
                formTitle.textContent = 'Novo Funcionário';
                senhaGroup.style.display = 'block';
                senhaInput.required = true;
                senhaHelper.style.display = 'none';
                await carregarCargos();
            }
        }

        // --- Submissão do Formulário ---
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            btnSalvar.disabled = true;
            btnSalvar.textContent = 'A guardar...';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Define a URL da API e o método (Criar ou Atualizar)
            const url = isEditMode ? '/api/funcionarios/atualizar' : '/api/funcionarios';
            
            const response = await apiFetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (response && response.success) {
                alert(response.message);
                window.location.href = '/funcionarios';
            } else {
                btnSalvar.disabled = false;
                btnSalvar.textContent = 'Salvar';
            }
        });

        // --- Inicialização ---
        carregarFormulario();
    });
    </script>
</body>
</html>

