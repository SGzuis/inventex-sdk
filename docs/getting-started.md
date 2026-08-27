# Getting Started

Visão geral de onde este SDK se encaixa e o que resolver antes de instalar
qualquer coisa.

## O que é

O Inventex SDK é o cliente PHP oficial para **integrar um sistema externo
com a API do Inventex** — o SaaS de gerenciamento de inventário/contagem de
estoque. Ele existe pra quem precisa, de fora do Inventex, criar
inventários, consultar status, alimentar o catálogo de itens em lote, ou
autenticar um usuário via API — sem montar requisições HTTP, assinatura HMAC
e tratamento de erro na mão.

> Hoje a API só expõe autenticação e o CRUD de inventários (criar, listar,
> consultar, atualizar/mudar status, excluir, itens em lote). Fluxo de
> contagem física, posições, operadores externos e importação por planilha
> são operados só pelo app Web do Inventex — não têm endpoint na API, então
> também não têm builder no SDK.

## Antes de instalar

Você vai precisar de duas coisas que só existem **dentro do Inventex**, não
no SDK:

1. **Uma conta/workspace no Inventex** — se ainda não tem, é lá que os
   inventários existem; o SDK só fala com uma instância já rodando.
2. **Um aplicativo de integração** — em **Workspace → Integração →
   Aplicativos**, crie um aplicativo. Você recebe, uma única vez (não fica
   mais visível depois):
   - um **token Bearer** — autentica as chamadas;
   - uma **chave de assinatura HMAC** — opcional, mas é o padrão do sistema
     assinar cada requisição; sem ela o aplicativo pode não ser aceito
     dependendo da configuração de segurança do workspace.

   Guarde as duas em algum cofre de segredo do seu projeto (`.env`, secret
   manager) — não tem como recuperá-las depois, só gerar novas.

Com isso em mãos, o próximo passo depende de como seu projeto é estruturado:

- **Projeto PHP puro (sem framework)** → [`installation.md`](./installation.md)
- **Aplicação Laravel** → depois de instalar (mesmo passo de `composer
  require`), a configuração é diferente da forma manual — vá direto para
  [`laravel.md`](./laravel.md)

## Depois de configurado

Com o `InventexClient` (ou a facade, no Laravel) em mãos, o fluxo típico de
uso é:

1. [`inventory/create.md`](./inventory/create.md) — criar um inventário
   (com ou sem os itens do catálogo já no mesmo request).
2. [`inventory/items.md`](./inventory/items.md) — se o catálogo for grande
   (20k+ itens), alimentar em lotes separados depois da criação.
3. [`inventory/update.md`](./inventory/update.md) — mudar o `status` pra
   avançar o inventário (iniciar, concluir, cancelar, recontagem) — é assim
   que o ciclo de vida acontece via API, não existe um método `start()`
   dedicado.
4. [`inventory/list.md`](./inventory/list.md) / [`inventory/show.md`](./inventory/show.md) —
   acompanhar o andamento.

Veja [`README.md`](./README.md) para o índice completo (todas as ações de
inventário, webhooks, formato de resposta).
