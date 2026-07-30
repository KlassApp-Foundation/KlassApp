<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Http\Request;
use Tests\TestCase;

class CountNullSafetyTest extends TestCase
{
    public function test_post_attachment_path_handles_null_attachment_file(): void
    {
        $post = new Post;
        $post->attachment_file = null;

        $this->assertSame([], $post->AttachmentPath);
    }

    public function test_post_attachment_path_handles_empty_array(): void
    {
        $post = new Post;
        $post->attachment_file = [];

        $this->assertSame([], $post->AttachmentPath);
    }

    public function test_null_query_string_is_count_safe_with_array_cast(): void
    {
        $request = Request::create('/admin/students/blockedstudents', 'GET');

        $this->assertNull($request->getQueryString());
        $this->assertSame(0, count((array) $request->getQueryString()));
    }

    public function test_present_query_string_counts_as_non_empty_with_array_cast(): void
    {
        $request = Request::create('/admin/students/blockedstudents?alphabet=A', 'GET');

        $this->assertSame('alphabet=A', $request->getQueryString());
        $this->assertSame(1, count((array) $request->getQueryString()));
    }
}
