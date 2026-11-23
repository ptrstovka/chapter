<?php

use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\User;
use App\Support\VideoSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defaults to original playback and keeps originals with one HLS quality', function () {
    Settings::clearMemory();
    expect(app(VideoSettings::class)->snapshot())->toBe(['hls' => false, 'keep_original' => true, 'qualities' => [1080]]);
});

it('validates video settings and preserves them on unrelated patches', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->patch(route('admin.settings.update'), [
        'enable_video_hls' => true, 'keep_original_videos' => false, 'video_qualities' => [720, 1080],
    ])->assertRedirect();
    expect(app(VideoSettings::class)->snapshot())->toBe(['hls' => true, 'keep_original' => false, 'qualities' => [720, 1080]]);
    $this->patch(route('admin.settings.update'), ['enable_explore_page' => false])->assertRedirect();
    expect(Settings::get(Preference::VideoQualities))->toBe([720, 1080]);
    $this->patch(route('admin.settings.update'), ['video_qualities' => []])->assertSessionHasErrors('video_qualities');
    $this->patch(route('admin.settings.update'), ['video_qualities' => [720, 720]])->assertSessionHasErrors('video_qualities.0');
    $this->patch(route('admin.settings.update'), ['video_qualities' => [999]])->assertSessionHasErrors('video_qualities.0');
});
