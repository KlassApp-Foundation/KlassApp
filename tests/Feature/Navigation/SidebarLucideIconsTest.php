<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Icons PR 1 (sidebar only): every sidebar item resolves to a Lucide icon, the
 * <x-ka-icon> wrapper renders the expected SVG, and the sidebar's labels, routes,
 * URLs and grouping match the committed structure fixture. The fixture is
 * updated deliberately when the navigation is regrouped (PR1: the confirmed
 * People / Academics / Money / Messages / School groups).
 */
namespace Tests\Feature\Navigation;

use BladeUI\Icons\Factory;
use Tests\TestCase;

class SidebarLucideIconsTest extends TestCase
{
    /** @return list<array{role:string,label:string,icon:string}> */
    private function sidebarItems(): array
    {
        $out = [];
        $walk = function (string $role, array $items) use (&$out, &$walk): void {
            foreach ($items as $item) {
                if (array_key_exists('icon', $item)) {
                    $out[] = ['role' => $role, 'label' => $item['label'], 'icon' => (string) $item['icon']];
                }
                $walk($role, $item['children'] ?? []);
            }
        };
        foreach (config('navigation.roles') as $role => $nav) {
            $walk($role, $nav['items'] ?? []);
            foreach ($nav['groups'] ?? [] as $group) {
                $walk($role, $group['items'] ?? []);
            }
            foreach ($nav['sections'] ?? [] as $section) {
                $walk($role, $section['rows'] ?? []);
            }
        }

        return $out;
    }

    public function test_every_sidebar_item_maps_to_a_lucide_icon_that_exists(): void
    {
        $factory = app(Factory::class);
        $items = $this->sidebarItems();
        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $lucide = config('navigation.icons.'.$item['icon']);
            $this->assertIsString($lucide, "{$item['role']} / {$item['label']}: icon key [{$item['icon']}] has no entry in navigation.icons");
            $this->assertNotSame('', $lucide);
            $factory->svg('lucide-'.$lucide); // throws SvgNotFound if the name is wrong
        }

        foreach (config('navigation.group_icons') as $key => $lucide) {
            $factory->svg('lucide-'.$lucide);
            $this->assertNotEmpty($key);
        }
    }

    public function test_every_group_header_has_an_icon(): void
    {
        $groups = 0;
        foreach (config('navigation.roles') as $role => $nav) {
            foreach ($nav['groups'] ?? [] as $group) {
                $groups++;
                $key = $group['icon'] ?? $group['key'];
                $this->assertNotNull(config('navigation.group_icons.'.$key), "{$role} group [{$group['label']}] has no group icon");
            }
        }

        // Sidebar v3 replaced collapsible group headers with plain section labels.
        $this->assertSame(0, $groups);
    }

    public function test_students_and_teachers_do_not_share_an_icon_and_fees_is_a_banknote(): void
    {
        $this->assertNotSame(config('navigation.icons.students'), config('navigation.icons.teachers'));
        $this->assertSame('banknote', config('navigation.icons.fees'));

        // Notices, Visitors and Products-style items each have their own glyph.
        $this->assertCount(3, array_unique([
            config('navigation.icons.notices'),
            config('navigation.icons.visitors'),
            config('navigation.icons.reports'),
        ]));
    }

    public function test_sidebar_renders_lucide_svgs_with_no_legacy_icon_markup(): void
    {
        foreach (array_keys(config('navigation.roles')) as $role) {
            $html = view('layouts.partials.sidebar-menu', ['role' => $role])->render();
            $hasIcons = count(array_filter($this->sidebarItems(), fn ($i) => $i['role'] === $role)) > 0;

            if ($hasIcons) {
                $this->assertStringContainsString('ka-nav-icon', $html, "{$role}: no sidebar icon rendered");
                $this->assertStringContainsString('aria-hidden="true"', $html);
            }
            $this->assertStringNotContainsString('#94A3B8', $html, "{$role}: retired icon grey in sidebar markup");
            $this->assertStringNotContainsString('icon-fill-targets', $html, "{$role}: duotone fill layer is retired");
        }
    }

    public function test_sidebar_labels_routes_and_grouping_are_unchanged(): void
    {
        $strip = function (array $a) use (&$strip): array {
            $o = [];
            foreach ($a as $k => $v) {
                if ($k === 'icon') {
                    continue;
                }
                $o[$k] = is_array($v) ? $strip($v) : $v;
            }

            return $o;
        };

        $before = json_decode(file_get_contents(base_path('tests/fixtures/sidebar-nav-structure.json')), true);
        $after = json_decode(json_encode($strip(['roles' => config('navigation.roles')])), true);

        $this->assertSame($before['roles'], $after['roles']);
    }
}
