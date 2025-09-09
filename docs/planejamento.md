# 🗂️ Planejamento das Iterações

## Iteração 1: Conexão Garçom–Cozinha (Pedido do Salão para a Cozinha)

**Valor da Iteração:**  
Validar o fluxo mais crítico do negócio, digitalizando a comunicação entre o garçom e a cozinha. O objetivo é garantir que um pedido possa ser criado digitalmente e recebido instantaneamente para preparo, eliminando erros de ordem e tempo de deslocamento.

### Funcionalidades
- **Autenticação Básica (US01):** Criar uma tela de login simples que permita a autenticação do perfil de Garçom.  
- **Seleção de Mesas:** Implementar uma visualização básica de mesas para que o garçom possa selecionar onde o pedido será lançado.  
- **Lançamento de Pedido (US02):** Permitir que o garçom selecione itens de um cardápio (inicialmente pré-cadastrado no banco de dados) e os envie.  
- **Painel da Cozinha (US04):** Desenvolver uma tela para a Cozinha que exiba os pedidos recebidos em tempo real, mostrando a mesa e os itens solicitados.  

---

## Iteração 2: Administração e Controle (Gestão de Cardápio e Usuários)

**Valor da Iteração:**  
Dar autonomia ao administrador do restaurante, permitindo que ele gerencie os pilares do sistema: o cardápio e os acessos da equipe. Isso torna o PedeAI adaptável à realidade do negócio sem depender de suporte técnico para alterações simples.

### Funcionalidades
- **Autenticação por Perfil (Refinamento):** Expandir a tela de login para redirecionar corretamente os perfis de Administrador, Caixa e Cozinha.  
- **Gerenciamento de Cardápio (US08):** Criar a interface do Administrador para adicionar, editar e remover produtos e categorias, incluindo seus preços.  
- **Gerenciamento de Usuários (US09):** Implementar a funcionalidade para o Administrador criar, editar e desativar os logins dos funcionários (Garçom, Cozinha, Caixa).  
