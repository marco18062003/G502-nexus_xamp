<?php
/**
 * notify_whatsapp.php - daily alert (run once a day with cron).
 *
 * A daily message that nobody started must use an APPROVED TEMPLATE.
 * Suggested template (category Utility, language Spanish), name: alerta_vencimientos
 *   Body: "Alerta de vencimientos: {{1}} vencidos y {{2}} por vencer.
 *          Responde vencidos o proximos para ver el detalle."
 * When the person replies, a 24h window opens and the webhook answers freely.
 */
require_once __DIR__ . '/whatsapp_lib.php';

try {
    $vencidos = count(listaVencidos()) + count(listaHoy());
    $proximos = count(listaProximos()) - count(listaHoy()); // listaProximos includes today
} catch (Throwable $e) {
    logMsg('notify ERROR: ' . $e->getMessage());
    exit(1);
}

if ($vencidos + $proximos === 0) exit; // nothing to report today

foreach (WHATSAPP_ALLOWED as $to) {
    apiPost([
        'messaging_product' => 'whatsapp',
        'to'   => $to,
        'type' => 'template',
        'template' => [
            'name'     => WHATSAPP_TEMPLATE_NAME,
            'language' => ['code' => WHATSAPP_TEMPLATE_LANG],
            'components' => [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => (string)$vencidos],
                    ['type' => 'text', 'text' => (string)$proximos],
                ],
            ]],
        ],
    ]);
}