<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - PedeAI</title>

    <!-- CSS GLOBAL -->
    <link rel="stylesheet" href="/css/style.css">

    <!-- CSS DO SUPORTE (AGORA SOBRESCREVE O ANTERIOR) -->
    <link rel="stylesheet" href="/css/suporte.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="page-suporte">

<div class="dashboard-container">

    <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

    <main class="main-content page-suporte-content">

        <header class="suporte-header">
            <h1><i class="fas fa-headset"></i> Central de Suporte e Ajuda</h1>
            <p class="lead">Seja bem-vindo! Encontre respostas ou entre em contato com nossa equipe.</p>
        </header>

        <div class="suporte-wrapper">

            <div id="feedback-container"></div>

            <div class="suporte-card">
                <h4 class="suporte-card-title">
                    <i class="fas fa-question-circle"></i> Perguntas Frequentes
                </h4>
                <p>Dúvidas comuns sobre login, pedidos e cardápio.</p>
                <a href="/suporte/faq" class="btn-support btn-support-outline">Ver FAQs</a>
            </div>

            <div class="suporte-card">
                <h4 class="suporte-card-title">
                    <i class="fas fa-envelope"></i> Fale Conosco
                </h4>

                <form id="formSuporte" class="support-form">
                    <div class="mb-3">
                        <label class="form-label">Seu Nome</label>
                        <input type="text" class="form-control" id="nome" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Seu Email</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mensagem</label>
                        <textarea id="mensagem" class="form-control" rows="4" required></textarea>
                    </div>

                    <button class="btn-support btn-support-primary">
                        <i class="fas fa-paper-plane"></i> Enviar Mensagem
                    </button>
                </form>
            </div>

        </div>

    </main>

</div>

<script>
document.getElementById("formSuporte").addEventListener("submit", e => {
    e.preventDefault();
    const nome = document.getElementById("nome").value;

    document.getElementById("feedback-container").innerHTML = `
        <div class="feedback-box feedback-success">
            <i class="fas fa-check-circle"></i> Mensagem enviada com sucesso, ${nome}! Responderemos em breve.
        </div>
    `;

    setTimeout(() => {
        document.getElementById("feedback-container").innerHTML = "";
    }, 4000);
});
</script>

</body>
</html>
