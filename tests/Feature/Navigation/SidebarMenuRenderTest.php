<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Guards the sidebar renderer against a class of bug that shipped once (#734):
 * a stray duplicated attribute fragment left AFTER a tag closed rendered as
 * literal visible text ("x-bind:class="{ 'sidebar-group-header--open': ... }">")
 * next to every group header — while link counts, active state and Alpine
 * (which reported no errors, because the fragment was plain text) all looked fine.
 *
 * So this test asserts on the RENDERED HTML as a document: no text node may
 * contain Alpine directive syntax, and every role must render its configured
 * number of links.
 */
namespace Tests\Feature\Navigation;

use Tests\TestCase;

class SidebarMenuRenderTest extends TestCase
{
    /** @return list<string> */
    private function roles(): array
    {
        return array_keys(config('navigation.roles'));
    }

    public function test_no_alpine_directive_text_leaks_into_the_rendered_sidebar(): void
    {
        $this->assertNotEmpty($this->roles());

        foreach ($this->roles() as $role) {
            $html = view('layouts.partials.sidebar-menu', ['role' => $role])->render();

            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML('<!DOCTYPE html><html><body>'.$html.'</body></html>');
            libxml_clear_errors();

            $leaks = [];
            foreach ((new \DOMXPath($doc))->query('//text()') as $node) {
                $text = trim($node->nodeValue ?? '');
                if ($text !== '' && preg_match('/x-(bind|on|data|show|model|collapse)\b|previewOpen|toggle\(\)/', $text)) {
                    $leaks[] = mb_substr($text, 0, 80);
                }
            }

            $this->assertSame([], $leaks, "Sidebar [{$role}] leaked Alpine directive text into the page.");
        }
    }

    public function test_role_renders_every_configured_link(): void
    {
        $this->assertNotEmpty($this->roles());

        foreach ($this->roles() as $role) {
            $html = view('layouts.partials.sidebar-menu', ['role' => $role])->render();

            $nav = config('navigation.roles.'.$role);

            $flat = collect($nav['items'] ?? []);
            $grouped = collect($nav['groups'] ?? [])->flatMap(fn ($g) => $g['items']);

            // every configured item, plus its nested submenu children
            $expected = $flat->merge($grouped)->sum(fn ($i) => 1 + count($i['children'] ?? []));

            // each collapsible group wrapper is itself an <li>
            $expected += count($nav['groups'] ?? []);

            // teacher: class-teacher-only items are hidden without an authenticated class teacher
            $expected -= $flat->where('condition', 'class_teacher')->count();

            $this->assertSame($expected, substr_count($html, '<li '), "Sidebar [{$role}] rendered the wrong number of list items.");
        }
    }
}
