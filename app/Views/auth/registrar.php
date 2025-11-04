<?php
// Inicia a sessão para que possamos ler as mensagens de erro/sucesso
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PedeAI - Criar Conta</title>
    
    <link rel="stylesheet" href="/css/login.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .back-link {
            position: absolute; /* Flutua sobre a página */
            top: 20px;          /* 20px do topo */
            left: 20px;         /* 20px da esquerda */
            color: #ffffff;     /* Cor do texto branca */
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;   /* Um pouco mais de destaque (bold) */
            padding: 8px 12px;
            background-color: rgba(0, 0, 0, 0.2); /* Fundo escuro sutil */
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background-color: rgba(0, 0, 0, 0.4); /* Escurece no hover */
        }
        .back-link i {
            margin-right: 5px; /* Espaço entre o ícone e o texto */
        }
    </style>
</head>
<body>

    <a href="/" class="back-link">
        <i class="fas fa-arrow-left"></i> Voltar à página inicial
    </a>


    <div class="login-card">
        <div class="login-info">
            <img src="/images/pedeai-logo.png" alt="Logo PedeAI">
            <p>O controle total das suas mesas, na palma da sua mão.</p>
        </div>
        
        <div class="login-form">

            <h2 style="text-align: center;">CRIAR CONTA</h2>

            <?php if (isset($_SESSION['form_error'])): ?>
                <div class="login-error" style="display: block;">
                    <?php 
                        echo htmlspecialchars($_SESSION['form_error']);
                        unset($_SESSION['form_error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form action="/registrar" method="POST">
                
                <div class="input-group">
                    <i class="fas fa-building"></i>
                    <input type="text" name="cnpj" placeholder="CNPJ" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nome_proprietario" placeholder="Seu Nome Completo" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-mail" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="telefone" placeholder="Telefone / Celular">
                </div>
                <div class="input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" name="endereco" placeholder="Endereço Completo">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="senha" placeholder="Senha" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_senha" placeholder="Confirme a Senha" required>
                </div>

                <button type="submit" class="btn-entrar">Registrar</button>
                
                <p style="text-align: center; margin-top: 15px;">
                    Já tem uma conta? 
                    <a href="/login" style="color: #007bff; font-weight: bold; text-decoration: none;">Faça o login</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>