<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the Vue student-add class dropdown against the #441 regression class:
 * a static value="" on the same <option> as v-bind:value / :value makes every
 * class option submit empty under @vue/compat (Vue 3), even when standardLinklist loads.
 */
class StudentCreateClassOptionBindingTest extends TestCase
{
    #[Test]
    public function create_vue_class_options_bind_id_without_static_empty_value(): void
    {
        $path = resource_path('assets/js/components/student/Create.vue');
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        $this->assertMatchesRegularExpression(
            '/v-for="standardLink in standardLinklist"[\s\S]*?:value="standardLink\.id"/',
            $source,
            'Class options must bind :value="standardLink.id"'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<option\s+value=""\s+v-for="standardLink in standardLinklist"/',
            $source,
            'Class options must not carry a static value="" alongside the v-for'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<option[^>]*value=""[^>]*v-for="standardLink in standardLinklist"/',
            $source
        );
    }
}
