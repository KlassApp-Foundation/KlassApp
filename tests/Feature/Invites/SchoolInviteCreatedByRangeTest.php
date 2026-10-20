<?php

namespace Tests\Feature\Invites;

use Tests\TestCase;

class SchoolInviteCreatedByRangeTest extends TestCase
{
    public function test_out_of_range_created_by_is_rejected_before_the_foreign_key_is_dropped(): void
    {
        $source = file_get_contents(database_path('migrations/2026_10_19_000001_create_school_invites_table.php'));
        $start = strpos($source, 'function alignCreatedByWithUsersId');
        $end = strpos($source, 'function dropCreatedByForeignKey');

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $this->assertGreaterThan($start, $end);

        $method = substr($source, $start, $end - $start);
        $check = strpos($method, "where('created_by', '>', 4294967295)");
        $drop = strpos($method, '$this->dropCreatedByForeignKey()');

        $this->assertNotFalse($check);
        $this->assertNotFalse($drop);
        $this->assertLessThan(
            $drop,
            $check,
            'An out-of-range created_by must be rejected before the foreign key is dropped.'
        );
    }
}
