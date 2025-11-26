<?php
// Inicia a sessão para que o JS possa limpar a msg de erro antiga, se houver
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
            z-index: 10; /* Garante que fique acima de outros elementos */
        }
        .back-link:hover {
            background-color: rgba(0, 0, 0, 0.4); /* Escurece no hover */
        }
        .back-link i {
            margin-right: 5px; /* Espaço entre o ícone e o texto */
        }
        
        /* =====================================
         * CORREÇÃO PARA A PÁGINA ROLAR
         * =====================================
        */
        body {
            /* Remove o alinhamento vertical que impede a rolagem */
            align-items: flex-start; 
            
            /* Garante que o body possa crescer */
            height: auto;
            min-height: 100vh;
            
             /* Permite rolagem vertical */
            overflow-y: auto;
            
            /* Adiciona um 'respiro' no topo e embaixo para a rolagem */
            padding-top: 4rem;
            padding-bottom: 4rem;
        }
        
        .login-card {
           /* Garante que o card não seja cortado 
              e se centralize se houver espaço */
           margin: auto; 
        }
        /* ===================================== */

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

            <!--
              Div de Erro: Agora controlada pelo JavaScript.
              O PHP só mostra o erro se o JS falhar totalmente.
            -->
            <div class="login-error" id="form-error-msg" style="<?php echo isset($_SESSION['form_error']) ? 'display: block;' : 'display: none;'; ?>">
                <?php 
                    if (isset($_SESSION['form_error'])) {
                        echo htmlspecialchars($_SESSION['form_error']);
                        unset($_SESSION['form_error']); // Limpa após exibir
                    }
                ?>
            </div>
            
            <!-- 
              Formulário agora chama o JS (via ID) e não tem mais 'action'
            -->
            <form id="form-registrar">
                
                <div class="input-group">
                    <i class="fas fa-building"></i>
                    <input type="text" name="cnpj" placeholder="CNPJ" required>
                </div>
                
                <!-- =====================================
                 * NOVO CAMPO ADICIONADO
                 * =====================================
                -->
                <div class="input-group">
                    <i class="fas fa-store"></i> <!-- Ícone de loja/restaurante -->
                    <input type="text" name="nome_estabelecimento" placeholder="Nome do Estabelecimento" required>
                </div>
                <!-- ===================================== -->
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nome_proprietario" placeholder="Seu Nome Completo" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-mail (será seu login)" required>
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
                    <input type="password" name="senha" placeholder="Senha (mín. 6 caracteres)" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_senha" placeholder="Confirme a Senha" required>
                </div>

                <!-- Botão de submit com 'spinner' de loading -->
                <button type="submit" class="btn-entrar" id="btn-submit-registrar">
                    <span class="btn-text">Registrar</span>
                    <i class="fas fa-spinner fa-spin" style="display: none;"></i>
                </button>
                
                <p style="text-align: center; margin-top: 15px;">
                    Já tem uma conta? 
                    <a href="/login" style="color: #007bff; font-weight: bold; text-decoration: none;">Faça o login</a>
                </p>
            </form>
        </div>
    </div>

    <!-- 
      =====================================
      == JAVASCRIPT PARA CHAMAR A API ==
      =====================================
    -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('form-registrar');
        const errorMsgDiv = document.getElementById('form-error-msg');
        const submitBtn = document.getElementById('btn-submit-registrar');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnSpinner = submitBtn.querySelector('.fa-spinner');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // 1. Mostrar loading e limpar erros
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
            submitBtn.disabled = true;
            errorMsgDiv.style.display = 'none';
            errorMsgDiv.innerText = '';

            // 2. Coletar dados do formulário
            const formData = new FormData(form);
            const dados = Object.fromEntries(formData.entries());

            try {
                // 3. Enviar para a API
                const response = await fetch('/api/registrar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Insere o ID no modal
                    document.getElementById("empresa-id-box").innerText = "ID: " + result.empresa_id;

                    // Mostra o modal
                    document.getElementById("success-modal").style.display = "flex";

                    // Botão "OK"
                    document.getElementById("modal-ok-btn").onclick = () => {
                        window.location.href = "/login";
                    };

                    return; // Não deixa o código continuarr a janela

                } else {
                    // 5. Erro da API (ex: "Email já existe")
                    throw new Error(result.message || 'Erro desconhecido. Tente novamente.');
                }

            } catch (error) {
                // 6. Erro de rede ou o 'throw' acima
                errorMsgDiv.innerText = error.message;
                errorMsgDiv.style.display = 'block';
                
                // 7. Parar loading
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
                submitBtn.disabled = false;
            }
        });
    });
    </script>
    <div id="success-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h2 class="modal-title">🎉 Cadastro realizado com sucesso!</h2>

            <p class="modal-text">
                Sua conta foi criada com sucesso!<br><br>
                <strong style="color:#ff4757;">Atenção:</strong> anote o ID da sua empresa — ele será necessário para fazer login.
            </p>

            <div class="empresa-id-box" id="empresa-id-box">
                <!-- O ID será inserido aqui -->
            </div>

            <button class="modal-button" id="modal-ok-btn">Entendi</button>
        </div>
    </div>

</body>
</html>