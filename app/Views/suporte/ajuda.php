<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Suporte') ?> - PedeAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Estilo para a mensagem de feedback */
        .feedback-message {
            padding: 15px;
            margin-top: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
        }
        .feedback-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <header class="text-center mb-5">
            <h1><i class="fas fa-headset"></i> Central de Suporte e Ajuda</h1>
            <p class="lead">Seja bem-vindo! Encontre respostas rápidas ou entre em contato com nossa equipe.</p>
        </header>

        <div class="row justify-content-center">
            
            <div class="col-md-8">
                
                <div id="feedback-container"></div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title text-primary"><i class="fas fa-question-circle"></i> Perguntas Frequentes</h4>
                        <p>Aqui você pode listar as dúvidas mais comuns sobre login, pedidos e gerenciamento de cardápio.</p>
                        <a href="/suporte/faq" class="btn btn-outline-primary">Ver FAQs</a>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title text-success"><i class="fas fa-envelope"></i> Fale Conosco</h4>
                        <p>Se sua dúvida não foi respondida, utilize o formulário abaixo para enviar sua mensagem.</p>
                        
                        <form id="formSuporte" action="/api/suporte" method="POST">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Seu Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Seu E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="mensagem" class="form-label">Mensagem</label>
                                <textarea class="form-control" id="mensagem" name="mensagem" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i> Enviar Mensagem</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const formSuporte = document.getElementById('formSuporte');
        const feedbackContainer = document.getElementById('feedback-container');

        formSuporte.addEventListener('submit', function(e) {
            e.preventDefault(); // Impede o envio real do formulário

            // Captura os dados do formulário, se necessário (exemplo)
            const nome = document.getElementById('nome').value;

            // Simula o envio bem-sucedido e exibe feedback
            feedbackContainer.innerHTML = `
                <div class="feedback-message feedback-success">
                    <i class="fas fa-check-circle me-2"></i> Mensagem enviada com sucesso, ${nome}! Responderemos em breve.
                </div>
            `;

            // Limpa o formulário e remove o feedback após 5 segundos
            formSuporte.reset();
            setTimeout(() => {
                feedbackContainer.innerHTML = '';
            }, 5000);
        });
    });
    </script>
</body>
</html>