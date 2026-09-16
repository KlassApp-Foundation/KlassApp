<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsTreeServeTest extends TestCase
{
    public function test_hub_index_is_served(): void
    {
        $this->get('/docs/')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('KlassApp Docs', false);
    }

    public function test_shared_theme_css_is_served_with_css_mime(): void
    {
        $this->get('/docs/shared/docsify-klassapp.css')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=utf-8')
            ->assertSee('--d-canvas', false);
    }

    public function test_community_index_and_markdown_still_work(): void
    {
        $this->get('/docs/community/')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('KlassApp Community', false);

        $this->get('/docs/community/README.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertSee('KlassApp Community', false);
    }

    public function test_dev_index_and_shared_css_relative_path_work(): void
    {
        $this->get('/docs/dev/')
            ->assertOk()
            ->assertSee('Developer Docs', false);

        $this->get('/docs/shared/docsify-klassapp.css')->assertOk();
    }

    public function test_canonical_roadmap_and_architecture_markdown_are_public(): void
    {
        $this->get('/docs/roadmap.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertSee('Roadmap', false);

        $this->get('/docs/architecture.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertSee('Architecture', false);
    }

    public function test_sensitive_docs_paths_are_not_public(): void
    {
        $this->get('/docs/evidence/staging-docs-serve-gap/REPORT.json')->assertNotFound();
        $this->get('/docs/legacy-portal-idor.md')->assertNotFound();
        $this->get('/docs/toshi-whatsapp-channel-audit.md')->assertNotFound();
        $this->get('/docs/internal/anything.md')->assertNotFound();
        $this->get('/docs/od-mocks/klassapp-landing-v3-hero-role-rotate.html')->assertNotFound();
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->get('/docs/community/../../.env')->assertNotFound();
        $this->get('/docs/shared/../../../composer.json')->assertNotFound();
    }

    public function test_community_spa_fallback_serves_index_for_unknown_paths(): void
    {
        $this->get('/docs/community/does-not-exist-page')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('KlassApp Community', false);
    }
}
