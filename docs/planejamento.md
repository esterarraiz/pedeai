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

## Iteração 3: Refatoração Completa para Arquitetura Orientada a API

[➡️ Ver registro de implementação desta iteração](features.md)

**Valor da Iteração:**  
Transformar a arquitetura do projeto de uma aplicação monolítica renderizada no servidor para um sistema desacoplado, com um back-end PHP servindo uma API RESTful e um front-end dinâmico consumindo essa API. Esta mudança estabelece a base para futuras expansões, como aplicações mobile, e melhora drasticamente a interatividade e a performance da interface do usuário, eliminando a necessidade de recarregar a página para a maioria das ações.

### Funcionalidades

* **Refatoração da Autenticação para API:** Migrar o sistema de login e logout para uma arquitetura de API, transformando a página de login em uma **Single-Page Application (SPA)** para uma autenticação mais rápida e sem recarregamento de página.

* **Migração do Fluxo do Garçom para API:** Recriar todas as telas do perfil de Garçom (visualização de mesas, lançamento de pedidos, notificações) como **SPAs** que consomem a API, proporcionando uma experiência de usuário totalmente dinâmica e em tempo real.

* **Migração do Painel da Cozinha para API:** Refatorar o painel da Cozinha para uma **SPA**, permitindo que a lista de pedidos seja atualizada e os status sejam marcados como “Pronto” através da API, eliminando a necessidade de recarregar a página.

* **Migração do Fluxo do Caixa para API:** Transformar as telas do Caixa em **SPAs** que consomem a API para visualizar mesas ocupadas, consultar contas e registrar pagamentos de forma assíncrona.

* **Migração do Painel de Administração para API:** Desenvolver os **endpoints CRUD** da API para gerenciar usuários e o cardápio, e refatorar as respectivas páginas de administração para **SPAs**, tornando a gestão do sistema mais ágil e interativa.


---

