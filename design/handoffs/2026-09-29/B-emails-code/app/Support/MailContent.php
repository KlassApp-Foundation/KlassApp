<?php

namespace App\Support;

/** Presentation-only clean-up of mailtemplates bodies. Never writes to the database. */
final class MailContent
{
    private const LEGACY_BUTTON = '/style="border:\s*none;\s*color:\s*white;\s*padding:\s*10px 15px;[^"]*background-color:\s*#008CBA;"/i';

    public static function normalise(string $content): string
    {
        $html = html_entity_decode($content);
        $html = preg_replace('/^[ \t]+/m', '', $html);          // no Markdown code blocks
        return preg_replace(self::LEGACY_BUTTON, 'class="button button-primary"', $html);
    }

    public static function toText(string $content): string
    {
        $html = html_entity_decode($content);
        $html = preg_replace('/<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', '$2: $1', $html);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = strip_tags($text);
        return trim(preg_replace('/^[ \t]+/m', '', $text));
    }
}
