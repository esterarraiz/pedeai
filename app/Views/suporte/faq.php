<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - PedeAI</title>

    <!-- CSS DO FAQ (moderno) -->
    <link rel="stylesheet" href="/css/faq.css">

    <!-- style.css por último -->
    <link rel="stylesheet" href="/css/style.css">

    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Bootstrap (necessário para accordion funcionar) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

    <!-- Conteúdo -->
    <main class="main-content page-suporte">

        <!-- Cabeçalho -->
        <header class="suporte-header text-center mb-5">
            <h1><i class="fas fa-question-circle"></i> Dúvidas Frequentes (FAQ)</h1>
            <p class="lead">Respostas rápidas sobre as principais funcionalidades.</p>
        </header>

        <!-- Wrapper -->
        <div class="suporte-wrapper">

            <!-- Accordion -->
            <div class="accordion faq-accordion" id="faqAccordion">

                <!-- ITEM 1 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseOne"
                                aria-expanded="false"
                                aria-controls="collapseOne">
                            <i class="fas fa-user-lock me-2"></i> Acesso e Permissões
                        </button>
                    </h2>

                    <div id="collapseOne"
                         class="accordion-collapse collapse"
                         aria-labelledby="headingOne"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">
                            <strong>1. Por que recebo "ID da empresa, e-mail ou senha incorretos"?</strong>
                            <p>Verifique se o ID da Empresa, o seu E-mail de Login e sua Senha estão corretos. Lembre-se que cada funcionário possui um único e-mail vinculado ao seu cargo. Se for o seu primeiro login como administrador, verifique o e-mail cadastrado durante a criação da conta.</p>
                            <p>Se o problema persistir, e você for o proprietário, entre em contato com o suporte para redefinição manual da senha ou verificação dos dados cadastrais.</p>
                        </div>
                    </div>
                </div>

                <!-- ITEM 2 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo"
                                aria-expanded="false"
                                aria-controls="collapseTwo">
                            <i class="fas fa-utensils me-2"></i> Cardápio e Produtos
                        </button>
                    </h2>

                    <div id="collapseTwo"
                         class="accordion-collapse collapse"
                         aria-labelledby="headingTwo"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">
                             <strong>1. Como gerenciar e criar categorias?</strong>
                                <p>No Dashboard, acesse a página de Editar Cardápio. Clique em "Gerenciar Categorias", digite o nome da nova categoria e clique em Criar.</p>
                                
                                <strong>2. Como funciona o upload de imagens?</strong>
                                <p>Ao adicionar ou editar um item, você pode fazer o upload de uma imagem. O sistema salva o arquivo em uma pasta segura (public/images/Cardapio). Se a imagem falhar ao carregar, o sistema usa automaticamente uma imagem placeholder para evitar erros visuais. Verifique as permissões de escrita (0777) na pasta de upload.</p>
                                
                                <strong>3. Por que não consigo remover uma categoria?</strong>
                                <p>O sistema impede a remoção de categorias que ainda tenham produtos vinculados para proteger a integridade dos seus dados. Para deletar a categoria, você deve primeiro remover todos os itens pertencentes a ela ou movê-los para outra categoria.</p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- ITEM 3 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseThree"
                                aria-expanded="false"
                                aria-controls="collapseThree">
                            <i class="fas fa-clipboard-list me-2"></i> Fluxo de Pedidos
                        </button>
                    </h2>

                    <div id="collapseThree"
                         class="accordion-collapse collapse"
                         aria-labelledby="headingThree"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">
                            <strong>1. Qual é o fluxo de trabalho dos pedidos?</strong>
                                <p>O fluxo segue estes passos:</p>
                                <ol>
                                    <li>O Garçom lança o pedido (Status: Em Preparo).</li>
                                    <li>A Cozinha visualiza o pedido em seu painel e marca como Pronto.</li>
                                    <li>O Garçom recebe a notificação, entrega o pedido na mesa e marca como Entregue.</li>
                                    <li>O Caixa recebe o comando de pagamento.</li>
                                </ol>
                                
                                <strong>2. Como a Cozinha e o Garçom se comunicam?</strong>
                                <p>A comunicação é feita pela mudança de status: a Cozinha visualiza pedidos em 'Em Preparo' e os move para 'Pronto', que é o sinal para o Garçom fazer a entrega.</p>
                        </div>
                    </div>
                </div>

                <!-- ITEM 4 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseFour"
                                aria-expanded="false"
                                aria-controls="collapseFour">
                            <i class="fas fa-cash-register me-2"></i> Fechamento de Conta (Caixa)
                        </button>
                    </h2>

                    <div id="collapseFour"
                         class="accordion-collapse collapse"
                         aria-labelledby="headingFour"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">
                             <strong>1. Como fechar uma conta e liberar a mesa?</strong>
                                <p>Acesse o Dashboard do Caixa e encontre a mesa desejada. Na tela de Resumo da Conta, confira todos os itens lançados e o valor total. Após o cliente pagar, clique em Processar Pagamento (e insira o método). Isso marca todos os pedidos da mesa como 'Pago' e muda o status da mesa para 'Disponível'.</p>
                                
                                <strong>2. Posso ver o histórico de vendas?</strong>
                                <p>Sim. Apenas o administrador (ou gerente) tem acesso à seção de Relatórios de Vendas, onde pode visualizar o faturamento do dia, pedidos recentes e outras métricas importantes.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- BOTÃO VOLTAR -->
            <div class="text-center mt-4">
                <a href="/suporte" class="btn btn-secondary">Voltar ao Suporte</a>
            </div>

        </div>

    </main>

</div>

<!-- Bootstrap JS (obrigatório para accordion abrir/fechar) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
