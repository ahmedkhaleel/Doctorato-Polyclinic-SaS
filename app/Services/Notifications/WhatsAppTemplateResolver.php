<?php

namespace App\Services\Notifications;

use App\Models\WhatsappTemplate;

/**
 * Resolves a registered Meta WhatsApp template for a hub event and builds the
 * Cloud API meta payload (template_name + language + body components) from the
 * send data. Returns null when no active template maps to the event.
 */
class WhatsAppTemplateResolver
{
    /**
     * @return array{template_name:string,template_lang:string,template_components:array}|null
     */
    public function metaFor(string $eventKey, array $data): ?array
    {
        $tpl = WhatsappTemplate::where('event_key', $eventKey)->where('is_active', true)->first();
        if (! $tpl) {
            return null;
        }

        $params = [];
        foreach ($tpl->variables ?? [] as $key) {
            $value = $data[$key] ?? '';
            $params[] = ['type' => 'text', 'text' => (string) (is_scalar($value) ? $value : '')];
        }

        $components = $params
            ? [['type' => 'body', 'parameters' => $params]]
            : [];

        return [
            'template_name' => $tpl->name,
            'template_lang' => $tpl->language,
            'template_components' => $components,
        ];
    }
}
