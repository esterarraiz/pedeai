<?php
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
        <div class="login-info">
            <img src="/images/pedeai-logo.png" alt="Logo PedeAI">
            <p>O controle total das suas mesas, na palma da sua mão.</p>
        </div>
        <div class="login-form">
            <h2 style="text-align: center;">LOGIN</h2>

            <div id="error-message" class="login-error" style="display: none;"></div>

            <form id="login-form-api">
                <div class="input-group">
                    <i class="fas fa-building"></i>
                    <input type="text" name="empresa_id" placeholder="ID da empresa" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-mail" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="senha" placeholder="Senha" required>
                </div>
                <a href="/esqueci-senha" class="forgot-password">Esqueci minha senha?</a>
                <button type="submit" id="login-button" class="btn-entrar">Entrar</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('login-form-api');
        const errorMessageDiv = document.getElementById('error-message');
        const loginButton = document.getElementById('login-button');

        form.addEventListener('submit', async function(event) {
            event.preventDefault(); // Impede o envio tradicional do formulário

            errorMessageDiv.style.display = 'none';
            loginButton.textContent = 'Aguarde...';
            loginButton.disabled = true;

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (response.ok) {
                    window.location.href = result.redirectTo;
                } else {
                    errorMessageDiv.textContent = result.message;
                    errorMessageDiv.style.display = 'block';
                    loginButton.textContent = 'Entrar';
                    loginButton.disabled = false;
                }
            } catch (error) {
                errorMessageDiv.textContent = 'Erro de comunicação com o servidor.';
                errorMessageDiv.style.display = 'block';
                loginButton.textContent = 'Entrar';
                loginButton.disabled = false;
            }
        });
    });
    </script>
</body>
</html>