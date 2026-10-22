<?php

/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\DesignSystem;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/** <x-fold-list> keeps every row in the HTML and offers "Show N more" after the first five. */
class FoldListComponentTest extends TestCase
{
    public function test_more_than_five_items_shows_the_remaining_count(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-fold-list tag="ul" class="manual-wizard-bulk-list" data-testid="folded">
@for($i = 1; $i <= 17; $i++)
    <li data-note="a>b">Item {{ $i }} <span>meta</span><input type="text" value="v{{ $i }}"><button type="button">Remove</button></li>
@endfor
</x-fold-list>
BLADE);

        $this->assertStringContainsString('Show 12 more', $html);
        $this->assertStringContainsString('Show less', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('aria-controls="fold-folded"', $html);
        $this->assertStringContainsString('id="fold-folded"', $html);
        $this->assertStringContainsString('Item 1', $html);
        $this->assertStringContainsString('Item 17', $html);
        $this->assertStringContainsString('data-testid="folded"', $html);
        $this->assertStringContainsString('nth-child(n+6)', $html);
    }

    public function test_five_items_render_the_list_without_a_fold_control(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-fold-list tag="ul">
@for($i = 1; $i <= 5; $i++)
    <li>Item {{ $i }}</li>
@endfor
</x-fold-list>
BLADE);

        $this->assertStringNotContainsString('Show ', $html);
        $this->assertStringNotContainsString('aria-expanded', $html);
        $this->assertStringContainsString('Item 5', $html);
    }

    public function test_a_table_folds_body_rows_and_ignores_the_header(): void
    {
        $rows = '';
        for ($i = 1; $i <= 17; $i++) {
            $rows .= '<tr><td>Row '.$i.'</td><td><input type="text" value="'.$i.'"></td></tr>';
        }

        $html = Blade::render('<x-fold-list table><table><thead><tr><th>Name</th></tr></thead><tbody>'.$rows.'</tbody></table></x-fold-list>');

        $this->assertStringContainsString('Show 12 more', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('<th>Name</th>', $html);
        $this->assertStringContainsString('Row 17', $html);
        $this->assertStringContainsString('fold-list--table', $html);
    }
}
