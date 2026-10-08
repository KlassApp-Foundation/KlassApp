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
        // PR1: Help moved from the sidebar footer into the School group — the
        // destination contract (klassapp.xyz/help, not the docs host) is unchanged.
        $school = collect(config('navigation.roles.admin.groups'))
            ->firstWhere('key', 'school');
        $this->assertIsArray($school);
        $help = collect($school['items'])->firstWhere('label', 'Help');

        $this->assertIsArray($help);
        $this->assertSame('https://klassapp.xyz/help', $help['external']);
        $this->assertStringNotContainsString('docs.klassapp.com', $help['external']);
    }

    public function test_live_docs_route_still_uses_docsify_controller(): void
    {
        $route = app('router')->getRoutes()->getByName('docs');

        $this->assertNotNull($route);
        $this->assertSame(\App\Http\Controllers\DocsController::class, $route->getAction('controller'));
    }
}
