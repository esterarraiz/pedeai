# 🍽️ PedeAI  

## Universidade  
Universidade Federal do Tocantins  

## Curso  
Ciência da Computação  

## Disciplina  
Engenharia de Software  

## Semestre  
2º semestre de 2025  

## Professor  
Edeílson Milhomem  

## Integrantes do Projeto  
- Arthur Vinicíus de Oliveira Carvalho
- Ester Arraiz de Matos  
- Jorge Antônio Motta Braga  
- Matheus Henrique de Freitas
- Vitória Maria Reis Fontana

---

## 🎯 Escopo do Projeto  
O sistema de gerenciamento de pedidos **PedeAI** tem como objetivo **digitalizar e otimizar o processo de atendimento em restaurantes**, garantindo mais agilidade e eficiência na comunicação entre a equipe.  

O cliente **não interage diretamente com o sistema**, sendo atendido normalmente pelo garçom.  

O sistema é acessado apenas por perfis de usuários internos:  

- **Garçom**: anota os pedidos em um dispositivo móvel (tablet/smartphone) e os envia diretamente para a cozinha.  
- **Cozinha**: recebe os pedidos em tempo real em uma tela e atualiza o status de preparo.  
- **Caixa**: visualiza os pedidos finalizados por mesa para realizar o fechamento da conta.  
- **Administrador**: gerencia o cardápio, os usuários do sistema e acessa relatórios de vendas.  

---

## 🚀 Produto Mínimo Viável (MVP)  
Para validar o fluxo principal do sistema utilizando **PHP, HTML e CSS (sem frameworks)**, o MVP se concentrará nas funcionalidades essenciais para operar o ciclo de um pedido, desde a anotação até o pagamento.  

### Funcionalidades Essenciais  
- **Autenticação por Perfil**: Tela de login que redireciona o usuário (Garçom, Cozinha, Caixa, Admin) para sua respectiva interface.  
- **Gerenciamento de Cardápio**: Interface simples para o Administrador adicionar, editar ou remover itens do cardápio.  

### Fluxo do Garçom  
- Visualizar e selecionar mesas (livres/ocupadas).  
- Lançar pedidos para uma mesa a partir do cardápio digital.  
- Enviar o pedido para a cozinha.  

### Tela da Cozinha  
- Visualizar os pedidos recebidos em tempo real.  
- Marcar pedidos como "Prontos" para notificar o garçom.  

### Tela do Caixa  
- Visualizar os itens consumidos e o valor total por mesa.  
- Registrar o pagamento e liberar a mesa.   

---

## 📖 User Stories  

### Garçom  
- **US01**: Como garçom, quero logar no sistema para registrar e gerenciar os pedidos dos clientes.  
- **US02**: Como garçom, quero cadastrar o pedido do cliente de forma digital para que seja enviado instantaneamente à cozinha.  
- **US03**: Como garçom, quero visualizar o status do pedido (em preparo, pronto) para poder informar o cliente.  

### Cozinha  
- **US04**: Como cozinheiro, quero receber os pedidos em tempo real em uma tela para iniciar o preparo sem demora.  
- **US05**: Como cozinheiro, quero atualizar o status do pedido para que o garçom e o caixa saibam quando está pronto para ser entregue ou cobrado.  

### Caixa  
- **US06**: Como caixa, quero visualizar todos os pedidos finalizados de uma mesa para gerar a conta do cliente com precisão.  
- **US07**: Como caixa, quero registrar o pagamento (parcial ou total) para concluir o atendimento e fechar a conta.  

### Administrador  
- **US08**: Como administrador, quero gerenciar o cardápio (adicionar, editar, remover itens e preços) para manter as opções sempre atualizadas.  
- **US09**: Como administrador, quero gerenciar os usuários (criar, editar e desativar perfis) para controlar os acessos ao sistema.  
- **US10**: Como administrador, quero gerar relatórios de vendas para acompanhar o desempenho financeiro do restaurante.  


# Iterações

## Iteração 1: O Pedido Nasce
### Objetivo
Criar o fluxo mais essencial do sistema: o garçom faz um pedido e ele aparece na cozinha.

| Responsável | Missão da Iteração | Dependências |
|-------------|----------------------------------|--------------|
| - | **Setup e Estrutura (Enabler):** Criar Git, estrutura de pastas MVC, conexão com DB e tabelas iniciais. | Nenhuma. Esta é a tarefa inicial que bloqueia todas as outras. |
| - | **Feature: Gestão de Produtos (Base):** ProdutoModel (método getTodos) para listar produtos. | Setup e Estrutura: Precisa da tabela produtos e da conexão com o DB. |
| - | **Feature: Listagem de Mesas:** MesaModel, MesaController e a View que exibe as mesas. | Setup e Estrutura: Precisa da tabela mesas e da conexão com o DB. |
| - | **Feature: Lançar Pedido:** PedidoController e Model para criar um pedido, mais a View do formulário. | Setup e Estrutura, Gestão de Produtos, Listagem de Mesas: Precisa selecionar uma mesa e listar produtos para adicionar ao pedido. |
| - | **Feature: Tela da Cozinha:** CozinhaController e View que exibe os pedidos recém-criados. | Lançar Pedido: Precisa que a funcionalidade de criar pedidos exista para poder exibi-los. |

## Iteração 2: Controle de Acesso e Feedback 

### Objetivo
Implementar o Módulo de Login (requisito APG-2) e o fluxo de atualização de status do pedido.

| Responsável | Missão da Iteração | Dependências |
|-------------|----------------------------------|--------------|
| - | **Feature: Autenticação de Usuário:** Implementar todo o sistema de login (Model, View, Controller, Session). | Setup e Estrutura (It. 1): Precisa criar a tabela usuários. |
| - | **Feature: Controle de Acesso por Papel:** Lógica para proteger as rotas e redirecionar usuários com base em seu papel. | Autenticação de Usuário: Precisa que o usuário esteja logado e que sua $_SESSION contenha seu papel. |
| - | **Feature: Cozinha Atualiza Status:** Lógica e botão para a cozinha marcar o pedido como "Pronto". | Autenticação de Usuário: Apenas um usuário “Cozinha” logado pode fazer isso. Fluxo de Pedido (It. 1): Precisa de um pedido existente para atualizar. |
| - | **Feature: Garçom Visualiza Status:** Exibir o status atualizado na tela do garçom. | Cozinha Atualiza Status: Depende da funcionalidade de atualização para ter um status para exibir. Controle de Acesso: Apenas o garçom logado pode ver. |
| - | **CSS Básico e Layout:** Criar um arquivo CSS base para padronizar a aparência do sistema. | Nenhuma dependência direta, pode ser feito em paralelo, aplicando-se às features existentes. |

## 📱Protótipo
### Administrador  
<div style="display: flex; gap: 10px; margin-bottom: 10px;">
  <img src="prototipo/Home.png" alt="Home" width="300px">
  <img src="prototipo/Relatorios_Vendas.png" alt="Login" width="300px">
</div>
<div style="display: flex; gap: 10px; margin-bottom: 10px;">
  <img src="prototipo/Gerenciamento_Cardapio.png" alt="Home" width="300px">
  <img src="prototipo/Gerenciamento_Usuarios.png" alt="Login" width="300px">
</div>
<div style="display: flex; gap: 10px;">
  <img src="prototipo/Suporte.png" alt="Home" width="300px">
</div>
