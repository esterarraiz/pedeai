# 🗂️ Planejamento das Iterações

## Iteração 1: Conexão Garçom–Cozinha (Pedido do Salão para a Cozinha)

[➡️ Ver registro de implementação desta iteração](features.md)

**Valor da Iteração:**  
Validar o fluxo mais crítico do negócio, digitalizando a comunicação entre o garçom e a cozinha. O objetivo é garantir que um pedido possa ser criado digitalmente e recebido instantaneamente para preparo, eliminando erros de ordem e tempo de deslocamento.

### Funcionalidades
- **Autenticação Básica (US01):** Criar uma tela de login simples que permita a autenticação do perfil de Garçom.  
- **Seleção de Mesas:** Implementar uma visualização básica de mesas para que o garçom possa selecionar onde o pedido será lançado.  
- **Lançamento de Pedido (US02):** Permitir que o garçom selecione itens de um cardápio (inicialmente pré-cadastrado no banco de dados) e os envie.  
- **Painel da Cozinha (US04):** Desenvolver uma tela para a Cozinha que exiba os pedidos recebidos em tempo real, mostrando a mesa e os itens solicitados.  

---


## Iteração 2: Consolidação Operacional

[➡️ Ver registro de implementação desta iteração](features.md)

**Valor da Iteração:**
Consolidar o sistema com as funcionalidades essenciais de **administração** (cardápio e usuários), fechar o **loop de comunicação** (notificação de pedido pronto) e implementar o **fluxo de caixa** completo (visualização da conta e registro do pagamento).

### Funcionalidades
- **Gerenciamento de Cardápio (US08):** Criar a interface para o Administrador gerenciar o cardápio, permitindo adicionar, editar e remover produtos, categorias e seus respectivos preços.
- **Gerenciamento de Usuários (US09):** Implementar a funcionalidade para o Administrador criar, editar e desativar os logins e perfis dos funcionários (Garçom, Cozinha, Caixa). Além disso, implementar controle de sessão e proteção de rotas, garantindo que apenas usuários autenticados possam acessar o sistema e que cada perfil tenha acesso apenas às funcionalidades correspondentes ao seu papel.
- **Ciclo de Notificação de Pedido Pronto (US05 + US03):** Implementar o fluxo de comunicação de duas vias: adicionar um botão no Painel da Cozinha para marcar pedidos como "Prontos" (US05) e exibir um alerta ou indicador visual na tela do Garçom quando um de seus pedidos estiver pronto para entrega (US03).
- **Visualização de Conta por Mesa (US06):** Desenvolver a tela para o perfil de Caixa, que permite selecionar uma mesa ocupada e visualizar todos os itens consumidos, com suas quantidades, preços e o valor total da conta.
- **Registro de Pagamento (US07):** Na tela do Caixa, implementar a funcionalidade para registrar o pagamento de uma conta. Após o registro, o sistema deve marcar a conta como "Paga" e liberar a mesa, alterando seu status para "Livre".


---
