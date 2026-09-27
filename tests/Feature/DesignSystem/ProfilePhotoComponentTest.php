<?php

namespace Tests\Feature\DesignSystem;

use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * <x-profile-photo> — the single profile-photo frame (2026-09-27 handoff,
 * item 6, option b: circle only for the 32px nav trigger).
 */
class ProfilePhotoComponentTest extends TestCase
{
    private function user(?string $avatar, string $first = 'Ann', string $last = 'Kato'): User
    {
        $user = new User(['name' => 'ann.kato']);
        $user->setRelation('userprofile', new Userprofile(['avatar' => $avatar, 'firstname' => $first, 'lastname' => $last]));

        return $user;
    }

    private function render(string $tag, array $data = []): string
    {
        return Blade::render($tag, $data);
    }

    public function test_user_with_avatar_renders_that_photo_with_display_name_alt(): void
    {
        $html = $this->render('<x-profile-photo :user="$u" size="xl" />', ['u' => $this->user('avatars/ann.jpg')]);

        $this->assertStringContainsString('avatars/ann.jpg', $html);
        $this->assertStringContainsString('alt="ANN KATO"', $html);
        $this->assertStringContainsString('width="192" height="192"', $html);
        $this->assertStringContainsString('aspect-ratio: 1', $html);
        $this->assertStringContainsString('object-fit: cover', $html);
        $this->assertStringContainsString('border-radius: var(--d-radius-xl, 12px)', $html);
        $this->assertStringContainsString('box-shadow: 0 0 0 1px var(--d-border, #E2E8F0)', $html);
    }

    /**
     * Userprofile::AvatarPath returns '' (not null) with no avatar, so a
     * `?? default` fallback would emit src="". The component must use the
     * dropdown's `avatar != null` test.
     */
    public function test_user_without_avatar_gets_the_default_image_not_an_empty_src(): void
    {
        $html = $this->render('<x-profile-photo :user="$u" size="lg" />', ['u' => $this->user(null)]);

        $this->assertStringContainsString('uploads/user/avatar/default-user.jpg', $html);
        $this->assertStringNotContainsString('src=""', $html);
        $this->assertStringContainsString('width="128" height="128"', $html);
    }

    public function test_null_user_renders_default_with_empty_alt(): void
    {
        $html = $this->render('<x-profile-photo :user="null" size="md" />');

        $this->assertStringContainsString('uploads/user/avatar/default-user.jpg', $html);
        $this->assertStringContainsString('alt=""', $html);
        $this->assertStringContainsString('width="64" height="64"', $html);
    }

    public function test_sizes_and_circle_nav_trigger(): void
    {
        foreach (['sm' => 40, 'md' => 64, 'lg' => 128, 'xl' => 192, 'xs' => 32] as $size => $px) {
            $html = $this->render('<x-profile-photo :user="null" size="'.$size.'" />');
            $this->assertStringContainsString('width="'.$px.'" height="'.$px.'"', $html, $size);
        }

        $circle = $this->render('<x-profile-photo :user="null" size="xs" shape="circle" />');
        $this->assertStringContainsString('border-radius: 50%', $circle);
        $this->assertStringContainsString('var(--d-avatar-ring, rgba(34,197,94,0.3))', $circle);
    }

    public function test_avatar_ring_token_exists_and_views_use_the_component(): void
    {
        $this->assertStringContainsString('--d-avatar-ring: rgba(34, 197, 94, 0.3);', file_get_contents(public_path('css/dashboard-refresh.css')));

        $dropdown = file_get_contents(resource_path('views/layouts/partials/profile-dropdown.blade.php'));
        $this->assertStringContainsString('<x-profile-photo :user="Auth::user()" size="xs" shape="circle"', $dropdown);
        $this->assertSame(2, substr_count($dropdown, '<x-profile-photo :user="Auth::user()" size="sm"'));
        $this->assertStringNotContainsString('rgba(34,197,94,0.3)', $dropdown);
        $this->assertStringNotContainsString('AvatarPath', $dropdown);

        foreach (['admin/member/show', 'admin/teacher/show', 'admin/staff/show', 'teacher/student/show'] as $view) {
            $src = file_get_contents(resource_path('views/'.$view.'.blade.php'));
            $this->assertStringContainsString('<x-profile-photo :user="$user" size="xl"', $src, $view);
            $this->assertStringNotContainsString('AvatarPath', $src, $view);
        }
        $this->assertStringContainsString('<x-profile-photo :user="$feedback->parent" size="md" />', file_get_contents(resource_path('views/admin/feedbacks/view.blade.php')));

        // Print templates keep fixed px (PDF renderer), radius only -> 12px.
        foreach (['admin/id-card/id-card-new', 'admin/id-card/idcard-print', 'admin/buspass/bus_pass'] as $view) {
            $src = file_get_contents(resource_path('views/'.$view.'.blade.php'));
            $this->assertStringContainsString('border-radius: 12px;', $src, $view);
            $this->assertStringNotContainsString('<x-profile-photo', $src, $view);
        }
        // The commented-out bus-pass print avatar is deliberately untouched.
        $this->assertStringContainsString('<!-- <span><img src="{{ $student->userprofile->AvatarPath }}" style="width: 100px;height: 100px;border-radius: 10px;"></span> -->', file_get_contents(resource_path('views/admin/buspass/print.blade.php')));
    }
}
