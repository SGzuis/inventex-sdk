# Webhooks — documento técnico

Este documento descreve o formato, a assinatura e o ciclo de vida das
notificações de webhook enviadas pelo Inventex para o `webhook_url`
configurado em um aplicativo (Workspace → Integração → Aplicativos).

## Quando um webhook é disparado

Toda notificação nasce de um evento de domínio disparado no momento em que a
ação acontece, dentro da mesma requisição — a entrega HTTP em si é
assíncrona (fila), mas o disparo é sempre síncrono com a ação que o originou.
Não existem eventos de leitura/busca — só mudanças de estado reais.

| Evento             | Disparado quando…                                             |
|---------------------|----------------------------------------------------------------|
| `inventory.started`     | o inventário é iniciado (`state/start`)                    |
| `inventory.interrupted` | posições ativas do usuário são interrompidas (`state/interrupt`) |
| `inventory.concluded`   | a contagem é concluída (`state/conclude`)                   |
| `inventory.cancelled`   | o inventário é cancelado (`state/cancel`)                   |
| `position.started`      | uma posição é ocupada/iniciada                              |
| `position.interrupted`  | uma posição é interrompida                                  |
| `position.finished`     | uma posição é finalizada                                    |
| `item.counted`          | uma contagem de item é registrada                           |

Cada aplicativo com `webhook_url` configurado recebe **todos** os eventos do
workspace ao qual pertence — não há assinatura seletiva por tipo de evento
hoje. Se o consumidor só se interessa por alguns, filtre por `event` no
próprio handler.

## Formato da requisição

```
POST {webhook_url}
Content-Type: application/json
User-Agent: Inventex-Webhook/1.0
X-Signature: 9d53f1...
X-Timestamp: 1753196524
X-Event-Id: 8cb5d6b7-....-....-....-............
```

Corpo:

```json
{
  "event": "inventory.started",
  "event_id": "8cb5d6b7-....-....-....-............",
  "data": {
    "inventory_uuid": "b6f0...",
    "inventory_name": "Inventário Loja Centro",
    "status": "in_progress"
  }
}
```

`event_id` no corpo é sempre igual ao header `X-Event-Id` — use qualquer um
dos dois para deduplicação, o corpo existe para quando o consumidor só tem
acesso fácil ao payload (ex.: alguns frameworks de fila de terceiros).

### Payload por evento (campo `data`)

| Evento | Campos de `data` |
|---|---|
| `inventory.started` / `inventory.concluded` / `inventory.cancelled` | `inventory_uuid`, `inventory_name`, `status` |
| `inventory.interrupted` | `inventory_uuid`, `inventory_name`, `positions_interrupted` (quantidade) |
| `position.started` / `position.interrupted` / `position.finished` | `inventory_uuid`, `position` (código da posição) |
| `item.counted` | `inventory_uuid`, `position`, `product`, `counted_quantity`, `expected_quantity` |

## Assinatura (HMAC-SHA256)

O corpo é assinado com o **mesmo segredo do token do aplicativo**
(`signing_secret` — o mesmo usado para assinar as chamadas que você faz
*para* a API, ver `WebhookSignatureService`/`RequestSigner`). Isso é
proposital: token e assinatura são um par único por aplicativo, funcionando
nas duas direções.

```
assinatura = HMAC_SHA256(secret, "{timestamp}.{body}")
```

`{body}` é a string exata do corpo recebido (os bytes crus, antes de
qualquer parse) — nunca recodifique o JSON antes de calcular o hash, ou a
assinatura não vai bater.

Verificação (o SDK já faz isso por você, ver abaixo):

1. Rejeite se `X-Timestamp` estiver fora de uma janela de tolerância (o SDK
   usa 300s/5min por padrão).
2. Recalcule o HMAC com o corpo recebido e compare com `X-Signature` usando
   uma função resistente a timing attack (`hash_equals`), nunca `===`.
3. Só then processe o evento.

Webhook **sempre exige assinatura** no Inventex — não existe modo
"sem assinatura" para aplicativos com `webhook_url` configurado; o servidor
ativa a assinatura automaticamente ao salvar a URL, mesmo que o aplicativo
não tivesse assinatura antes.

## Verificando com o SDK

```php
use Bootstech\InventexSdk\InventexClient;
use Bootstech\InventexSdk\Exceptions\InvalidWebhookSignatureException;

$verifier = InventexClient::webhookVerifier('SUA_CHAVE_HMAC');

try {
    $event = $verifier->verify(
        $_SERVER['HTTP_X_SIGNATURE'],
        $_SERVER['HTTP_X_TIMESTAMP'],
        $_SERVER['HTTP_X_EVENT_ID'],
        file_get_contents('php://input')
    );
} catch (InvalidWebhookSignatureException $e) {
    http_response_code(401);
    exit;
}

switch ($event->name) {
    case 'inventory.concluded':
        minhaLogicaDeConclusao($event->data);
        break;
    case 'item.counted':
        minhaLogicaDeContagem($event->data);
        break;
    // demais eventos: ignore os que não te interessam
}
```

`$event->is('inventory.concluded')` é um atalho equivalente a
`$event->name === 'inventory.concluded'`.

### Em uma rota Laravel

```php
Route::post('/webhooks/inventex', function (Request $request) {
    $event = InventexClient::webhookVerifier(config('inventex-sdk.signing_secret'))->verify(
        $request->header('X-Signature'),
        $request->header('X-Timestamp'),
        $request->header('X-Event-Id'),
        $request->getContent(),
    );

    // ...
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
```

Exclua a rota do CSRF (é uma chamada servidor-a-servidor, sem sessão) e
**não** valide `X-Signature` manualmente em outro lugar — deixe só o
`WebhookVerifier` fazer isso, para não ter duas implementações divergentes.

## Reentrega e idempotência

A entrega é **at-least-once**: falhas de rede/timeout/5xx no seu endpoint
fazem o Inventex tentar novamente com backoff crescente (10s, 30s, 60s, 5min,
15min — 5 tentativas). Isso significa que o **mesmo `event_id` pode chegar
mais de uma vez**. Seu handler deve ser idempotente:

```php
if (WebhookEventLog::where('event_id', $event->eventId)->exists()) {
    return response()->noContent(); // já processado, ignora
}

// processa...

WebhookEventLog::create(['event_id' => $event->eventId]);
```

Responda **2xx o mais rápido possível** e faça o processamento pesado de
forma assíncrona (fila) no seu lado — um endpoint lento aumenta a chance de
timeout e reentrega desnecessária.

## Erros comuns

| Sintoma | Causa provável |
|---|---|
| `InvalidWebhookSignatureException: Timestamp do webhook inválido ou expirado.` | Relógio do servidor consumidor dessincronizado (NTP), ou o webhook chegou/foi processado com muito atraso |
| `InvalidWebhookSignatureException: Assinatura do webhook inválida.` | Segredo errado, ou o corpo foi alterado/re-serializado antes de assinar/comparar |
| `InvalidWebhookSignatureException: Corpo do webhook malformado.` | Algum proxy/middleware no seu lado alterou o `Content-Type` ou o corpo antes de chegar no seu handler |
