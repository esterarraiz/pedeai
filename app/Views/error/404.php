<?php
// Define o código de status HTTP 404
http_response_code(404);

// Define as variáveis de conteúdo
$titulo = "Ops! Página Não Encontrada";
$mensagem = "O conteúdo solicitado não foi localizado. Pode ter sido movido ou ainda estar em desenvolvimento.";
$link_volta = "/dashboard.php"; // Ajuste para a sua página inicial ou dashboard
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?php echo $titulo; ?></title>
    <link rel="icon" href="/favicon.ico"> <style>
        /* Estilos básicos para o layout */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff; /* Fundo branco como na imagem */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 900px;
            padding: 40px;
            /* Flex-direction: column no mobile */
            flex-direction: column;
        }

        .image-section {
            padding: 20px;
            flex-shrink: 0; /* Impede que a imagem encolha */
        }

        .image-section img {
            width: 350px; /* Ajuste o tamanho da imagem do personagem */
            height: auto;
        }

        .text-section {
            padding: 20px;
            text-align: left;
            margin-left: 40px; /* Espaçamento entre texto e imagem no desktop */
        }

        /* Estilos do conteúdo */
        .error-code {
            font-size: 8em;
            font-weight: bold;
            color: #FF5722; /* Cor laranja do PedeAI */
            line-height: 1;
        }

        h1 {
            font-size: 2.5em;
            color: #333;
            margin-top: 5px;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 30px;
        }

        .btn-voltar {
            /* Cor de fundo: MUDAR de #FF5722 para #4CAF50 */
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            /* ADICIONAR: Transformar o botão em um elemento de bloco para centralizar */
            display: inline-block; 
        }

        .btn-voltar:hover {
            /* Cor do hover: MUDAR de #E64A19 para um verde mais escuro (#43A047) */
            background-color: #43A047;
        }

        /* Footer (Logo PedeAI) */
        .footer-logo {
            position: absolute;
            bottom: 20px;
            right: 20px;
            opacity: 0.8;
        }
        
        .footer-logo img {
            width: 50px; /* Ajuste o tamanho do logo */
        }

        /* Media Query para telas maiores (Desktop) */
        /* ... dentro da sua Media Query (para telas maiores) ... */
        @media (min-width: 768px) {
            .container {
                flex-direction: row; 
            }
            .text-section {
                /* MUDAR: Centraliza o conteúdo dentro da seção de texto, incluindo o botão. */
                text-align: center;
                /* ADICIONAR: Garante que o texto está alinhado ao centro do espaço disponível */
                justify-content: center;
                align-items: center;
                display: flex; /* Necessário para alinhar o conteúdo */
                flex-direction: column; /* Coloca os elementos um abaixo do outro */
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="image-section">
            <img src="/images/boy_404.svg" alt="Personagem PedeAI Confuso">
        </div>

        <div class="text-section">
            <div class="error-code">404</div>
            <h1><?php echo $titulo; ?></h1>
            <p><?php echo $mensagem; ?></p>
            <div class="text-section">
                <a href="javascript:history.back()" class="btn-voltar">
                    Voltar
                </a>
            </div>
        </div>
    </div>
    
    <div class="footer-logo">
        <img src="/images/pedeai-logo.png" alt="Logo PedeAI"> 
    </div>

</body>
</html>