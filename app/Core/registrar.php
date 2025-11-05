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
</head>
<body>
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
                        unset($_SESSION['form_error']); // Limpa a mensagem
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

                <button type="submit" class="login-button">Registrar</button>
                
                <p class="register-link">
                    Já tem uma conta? <a href="/login">Faça o login</a>
                </p>
            </form>
        </div>
    </div>
    
    </body>
</html>