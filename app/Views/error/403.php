<?php
// Define o código de status HTTP 403
http_response_code(403);

// Variáveis de conteúdo
$titulo = "Acesso Negado";
$mensagem = "Você não tem permissão para acessar essa página.";
$link_volta = "javascript:history.back()";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - <?php echo $titulo; ?></title>
    <link rel="icon" href="/favicon.ico">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
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
            flex-direction: column; /* mobile */
        }

        .image-section {
            padding: 20px;
            flex-shrink: 0;
        }

        .image-section img {
            width: 350px;
            height: auto;
        }

        .text-section {
            padding: 20px;
            text-align: left;
            margin-left: 40px;
        }

        .error-code {
            font-size: 8em;
            font-weight: bold;
            color: #FF5722;
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
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-voltar:hover {
            background-color: #43A047;
        }

        .footer-logo {
            position: absolute;
            bottom: 20px;
            right: 20px;
            opacity: 0.8;
        }
        
        .footer-logo img {
            width: 50px;
        }

        @media (min-width: 768px) {
            .container {
                flex-direction: row;
            }
            .text-section {
                text-align: center;
                justify-content: center;
                align-items: center;
                display: flex;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="image-section">
            <img src="/images/boy_403.svg" alt="Acesso negado">
        </div>

        <div class="text-section">
            <div class="error-code">X</div>
            <h1><?php echo $titulo; ?></h1>
            <p><?php echo $mensagem; ?></p>

            <a href="<?php echo $link_volta; ?>" class="btn-voltar">
                Voltar
            </a>
        </div>
    </div>
    
    <div class="footer-logo">
        <img src="/images/pedeai-logo.png" alt="Logo PedeAI">
    </div>

</body>
</html>
