<?php

namespace Tests\Feature\StudentProfile;

use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/** The student photo on the ID card: JPG or PNG up to 2 MB, replaceable, removable, school scoped. */
class StudentPhotoTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('filesystems.default'));
        $this->buildStudentProfileFixture();
    }

    private function url(): string
    {
        return '/admin/students/'.$this->student->id.'/photo';
    }

    private function avatar(): ?string
    {
        return Userprofile::where('user_id', $this->student->id)->value('avatar');
    }

    public function test_a_photo_is_stored_in_the_schools_student_folder_and_shown_on_the_card(): void
    {
        $this->actingAs($this->admin)->post($this->url(), ['photo' => UploadedFile::fake()->image('a.jpg', 300, 200)])
            ->assertRedirect('/admin/students/'.$this->student->id);

        $path = $this->avatar();
        $this->assertStringStartsWith($this->school->fresh()->slug.'/student/avatar/', $path);
        Storage::disk(config('filesystems.default'))->assertExists($path);

        $this->actingAs($this->admin)->followingRedirects()->post($this->url(), ['photo' => UploadedFile::fake()->image('b.jpg')])->assertSee('Photo saved.');

        $html = $this->actingAs($this->admin)->get('/admin/students/'.$this->student->id)->getContent();
        $this->assertStringContainsString('data-testid="student-photo"', $html);
        $this->assertStringContainsString('alt="Amara Okoro"', $html);
        $this->assertStringContainsString('Change photo', $html);
    }

    public function test_without_a_photo_the_initials_stay(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/students/'.$this->student->id)->getContent();

        $this->assertStringContainsString('>AO</span>', $html);
        $this->assertStringContainsString('Add photo', $html);
    }

    public function test_replacing_a_photo_deletes_the_old_file_and_removing_clears_it(): void
    {
        $this->actingAs($this->admin)->post($this->url(), ['photo' => UploadedFile::fake()->image('a.png')]);
        $first = $this->avatar();
        $this->actingAs($this->admin)->post($this->url(), ['photo' => UploadedFile::fake()->image('b.jpg')]);
        $second = $this->avatar();

        $this->assertNotSame($first, $second);
        Storage::disk(config('filesystems.default'))->assertMissing($first);
        Storage::disk(config('filesystems.default'))->assertExists($second);

        $this->actingAs($this->admin)->delete($this->url())->assertRedirect('/admin/students/'.$this->student->id);
        $this->assertNull($this->avatar());
        Storage::disk(config('filesystems.default'))->assertMissing($second);
        $this->assertStringContainsString('>AO</span>', $this->actingAs($this->admin)->get('/admin/students/'.$this->student->id)->getContent());
    }

    public function test_size_and_type_limits(): void
    {
        $this->actingAs($this->admin)->from('/admin/students/'.$this->student->id)
            ->post($this->url(), ['photo' => UploadedFile::fake()->image('big.jpg')->size(2049)])
            ->assertSessionHasErrors('photo');
        $this->actingAs($this->admin)->post($this->url(), ['photo' => UploadedFile::fake()->image('ok.jpg')->size(2048)])
            ->assertSessionHasNoErrors();
        $stored = $this->avatar();

        $this->actingAs($this->admin)->post($this->url(), ['photo' => UploadedFile::fake()->image('a.gif')])->assertSessionHasErrors('photo');
        $this->actingAs($this->admin)->post($this->url(), ['photo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertSessionHasErrors('photo');
        $this->actingAs($this->admin)->post($this->url(), ['photo' => UploadedFile::fake()->create('fake.jpg', 10, 'text/plain')])->assertSessionHasErrors('photo');
        $this->actingAs($this->admin)->post($this->url(), [])->assertSessionHasErrors('photo');

        $this->assertSame($stored, $this->avatar());
    }

    public function test_another_schools_admin_gets_404_and_nothing_is_stored(): void
    {
        $this->actingAs($this->otherAdmin)->post($this->url(), ['photo' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();
        $this->actingAs($this->otherAdmin)->delete($this->url())->assertNotFound();
        $this->actingAs($this->admin)->post('/admin/students/'.$this->otherStudent->id.'/photo', ['photo' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();

        $this->assertNull($this->avatar());
        $this->assertNull(Userprofile::where('user_id', $this->otherStudent->id)->value('avatar'));
        $this->assertSame([], Storage::disk(config('filesystems.default'))->allFiles());
    }
}
