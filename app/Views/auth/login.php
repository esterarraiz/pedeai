<?php
// Em: app/Views/auth/login.php

$login_error = $data['login_error'] ?? null;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PedeAI - Login</title>
    <link rel="stylesheet" href="/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <div class="login-card">
        
        <!-- Lado Esquerdo: Info da Marca -->
        <div class="login-info">
            <img src="/images/pedeai-logo.png" alt="Logo PedeAI">
            <p>O controle total das suas mesas, na palma da sua mão.</p>
        </div>

        <!-- Lado Direito: Formulário de Login e Links -->
        <div class="login-form">
            <h2 style="text-align: center;">LOGIN</h2>

            <?php if ($login_error): ?>
                <div class="login-error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>

            <form action="/login/process" method="POST">
                
                <div class="input-group">
                    <i class="fas fa-building"></i>
                    <input type="text" name="empresa_id" placeholder="ID da empresa" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-mail" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-eye"></i> 
                    <input type="password" name="senha" placeholder="Senha" required>
                </div>
                <a href="/esqueci-senha" class="forgot-password">Esqueci minha senha?</a>

                <button type="submit" class="btn-entrar">Entrar</button>
                <p class="register-link">
                    Ainda não cadastrou sua empresa? <a href="/register">Faça isso aqui!</a>
                </p>
            </form>

        </div>
    </div>

</body>
</html>

