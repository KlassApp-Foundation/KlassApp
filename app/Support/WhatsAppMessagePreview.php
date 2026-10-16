<?php

namespace App\Support;

/**
 * Render a WhatsApp message the way the chat does: *text* is bold, a fenced
 * block is a fixed-width table, and the fences and asterisks are not shown.
 * The string that is actually sent is left unchanged.
 */
class WhatsAppMessagePreview
{
    public static function html(string $message): string
    {
        $parts = explode('```', $message);
        $html = '';
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $html .= '<pre class="mr-wa-pre">'.e(trim($part, "\n")).'</pre>';

                continue;
            }
            $bold = preg_replace('/\*([^*]+)\*/', '<strong>$1</strong>', e($part)) ?? e($part);
            $html .= nl2br(str_replace('*', '', $bold), false);
        }

        return $html;
    }
}
