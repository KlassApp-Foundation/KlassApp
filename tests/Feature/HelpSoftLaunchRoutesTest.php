<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelpSoftLaunchRoutesTest extends TestCase
{
    public function test_help_hub_redirects_to_docs_preview_help(): void
    {
        $this->get('/help')
            ->assertRedirect('/docs-preview/help/');
    }

    public function test_help_subpath_redirects_to_docs_preview(): void
    {
        $this->get('/help/signup')
            ->assertRedirect('/docs-preview/help/signup');
    }

    public function test_admin_sidebar_help_footer_points_at_klassapp_help(): void
    {
        // Sidebar v3: Help lives in the account menu, not a sidebar row.
        // Destination stays klassapp.xyz/help, not the docs host.
        $dropdown = file_get_contents(resource_path('views/layouts/partials/profile-dropdown.blade.php'));
        $this->assertIsString($dropdown);
        $this->assertStringContainsString('https://klassapp.xyz/help', $dropdown);
        $this->assertStringNotContainsString('docs.klassapp.com', $dropdown);

        $labels = [];
        foreach (config('navigation.roles.admin.sections') as $section) {
            foreach ($section['rows'] as $row) {
                $labels[] = $row['label'];
                foreach ($row['children'] ?? [] as $child) {
                    $labels[] = $child['label'];
                }
            }
        }
        $this->assertNotContains('Help', $labels);
    }

    public function test_live_docs_route_still_uses_docsify_controller(): void
    {
        $route = app('router')->getRoutes()->getByName('docs');

        $this->assertNotNull($route);
        $this->assertSame(\App\Http\Controllers\DocsController::class, $route->getAction('controller'));
    }
}
