# Operadores externos (QR code de pareamento)

`$inventory->operators()` — `GET|POST|DELETE /inventories/{uuid}/operators/...`

Um operador externo conta **um único inventário** pelo próprio celular, sem
conta no Inventex. A sua plataforma exibe o QR, o operador aponta a câmera do
celular (abre o link direto no navegador) e vê um **código de conferência**;
você confere esse código com o `device_code` do pedido e aprova. Depois da
aprovação, o celular cai na tela de contagem daquele inventário. Ninguém
precisa estar logado no Inventex.

```php
$operators = $client->inventories()->find($uuid)->operators();

// 1. Exibir o QR (vale 24 h e pareia vários celulares)
$qr = $operators->qrCode();
echo '<img src="' . $qr->dataUri . '" alt="QR code de pareamento">';
// $qr->png() devolve os bytes do PNG; $qr->expiresAt a validade (ISO 8601)

// 2. Listar pedidos enquanto o QR está na tela (não há webhook: consulte a lista)
foreach ($operators->list()->data() as $operator) {
    $operator['id'];                        // uuid do pedido
    $operator['attributes']['device_code']; // código exibido no celular, ex.: "123-456"
    $operator['attributes']['status'];      // awaiting_approval|active|revoked|expired
}

// 3. Aprovar depois de conferir o código com o operador
$operators->approve($operatorUuid);

// Revogar um celular ativo (ou recusar um pedido pendente)
$operators->revoke($operatorUuid);
```

## QR code

`$operators->qrCode()` → `POST /inventories/{uuid}/operators/qr-code`.
Devolve `OperatorQrCode` com `dataUri` (PNG 320 px em data URI, pronto para
`<img src>`), `pairingUrl` (o link contido no QR) e `expiresAt`. Inventário
concluído ou cancelado → `InventexException` (HTTP 409).

`pairingUrl` carrega o token de registro: trate como segredo (não registre em
log nem envie por canais abertos). Qualquer pessoa com o QR consegue **pedir**
pareamento até expirar, mas só entra quem você aprovar — por isso confira o
código.

## Lista

`$operators->list($page = null)` → `GET /inventories/{uuid}/operators`,
15 por página, mais recentes primeiro. `attributes`: `label` (nome que o
operador informou ao escanear o QR), `device_code`, `status`, `created_at`, `activated_at`,
`revoked_at`.

## Aprovar e revogar

- `approve($operatorUuid)` → `POST .../operators/{operator_uuid}/approve`.
  Só pedidos `awaiting_approval` de inventário aberto; senão
  `InventexException` (HTTP 409).
- `revoke($operatorUuid)` → `DELETE .../operators/{operator_uuid}`. O celular
  perde o acesso na hora.
- Concluir, cancelar ou excluir o inventário expira os operadores
  automaticamente.
- Pedido de outro inventário → `NotFoundException`.
