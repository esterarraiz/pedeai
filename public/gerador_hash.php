<?php

// --- FERRAMENTA PARA GERAR HASH DE SENHA ---
// Use este script para criar as senhas criptografadas 
// que você vai salvar no seu banco de dados Supabase.

// 1. Defina a senha que você quer criptografar aqui:
$senhaEmTextoPuro = 'joao123'; // Mude para a senha desejada

// 2. O PHP gera o hash seguro usando o algoritmo BCRYPT (o mais recomendado)
$hashDaSenha = password_hash($senhaEmTextoPuro, PASSWORD_BCRYPT);

// 3. Exibe o resultado na tela
echo "<h1>Gerador de Hash de Senha</h1>";
echo "<p><strong>Senha Original:</strong> " . htmlspecialchars($senhaEmTextoPuro) . "</p>";
echo "<p><strong>Hash Gerado (Copie e cole no Supabase):</strong></p>";
echo "<textarea rows='3' cols='70' readonly>" . htmlspecialchars($hashDaSenha) . "</textarea>";

// Exemplo de hash gerado: $2y$10$f0EA.p3w3bJ1Q5vY/A9Wz.N489vG0hJvJ18A.LHO/B52vN.J0gD9.
// Cada vez que você rodar o script, o hash será diferente, mas todos funcionarão
// com a mesma senha original para a função password_verify().

?>
