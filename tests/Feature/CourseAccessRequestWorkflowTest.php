<?php

use App\Enums\CourseAccessMode;
use App\Enums\CourseAccessRequestStatus;
use App\Http\Controllers\Studio\CourseAccessRequestController;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAccessRequest;
use App\Models\CourseUserAccess;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->creator = User::factory()->author()->create();
    $this->course = Course::factory()
        ->for($this->creator->author)
        ->for(Category::factory())
        ->create(['access_mode' => CourseAccessMode::RequestOnly]);
});

it('enforces one pending request while allowing repeated resolved history', function () {
    $learner = User::factory()->create();
    CourseAccessRequest::factory()->for($this->course)->for($learner)->denied()->count(2)->create();
    $pending = CourseAccessRequest::factory()->for($this->course)->for($learner)->create();

    expect(fn () => DB::transaction(fn () => CourseAccessRequest::factory()
        ->for($this->course)->for($learner)->create()))
        ->toThrow(UniqueConstraintViolationException::class);

    $this->actingAs($this->creator)
        ->post(route('studio.courses.access.requests.deny', [$this->course->uuid, $pending]))
        ->assertRedirect();

    $this->actingAs($learner)
        ->post(route('courses.access-requests.store', $this->course->uuid))
        ->assertRedirect();

    expect($this->course->accessRequests()->where('status', CourseAccessRequestStatus::Pending)->count())->toBe(1)
        ->and($this->course->accessRequests()->where('status', CourseAccessRequestStatus::Denied)->count())->toBe(3);
});

it('adds pending uniqueness to existing request history without changing its records', function () {
    $migration = require database_path('migrations/2026_11_17_075103_add_pending_uniqueness_to_course_access_requests_table.php');
    $migration->down();

    $learner = User::factory()->create();
    $history = CourseAccessRequest::factory()->for($this->course)->for($learner)->denied()->count(2)->create();
    $pending = CourseAccessRequest::factory()->for($this->course)->for($learner)->create();

    $migration->up();

    expect($this->course->accessRequests()->count())->toBe(3)
        ->and($pending->refresh()->status)->toBe(CourseAccessRequestStatus::Pending);

    foreach ($history as $request) {
        expect($request->refresh()->status)->toBe(CourseAccessRequestStatus::Denied);
    }

    expect(fn () => DB::transaction(fn () => CourseAccessRequest::factory()
        ->for($this->course)->for($learner)->create()))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('stops an upgrade with duplicate pending requests before changing the schema or history', function () {
    $migration = require database_path('migrations/2026_11_17_075103_add_pending_uniqueness_to_course_access_requests_table.php');
    $migration->down();
    $learner = User::factory()->create();
    CourseAccessRequest::factory()->for($this->course)->for($learner)->count(2)->create();

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'Resolve duplicate pending course access requests')
        ->and(Schema::hasColumn('course_access_requests', 'pending'))->toBeFalse()
        ->and($this->course->accessRequests()->count())->toBe(2);
});

it('reuses a competing pending request inserted after its initial lookup', function () {
    $learner = User::factory()->create();
    $inserted = false;

    DB::listen(function (QueryExecuted $query) use ($learner, &$inserted) {
        if ($inserted || ! str_starts_with($query->sql, 'select') || ! str_contains($query->sql, 'course_access_requests')) {
            return;
        }

        $inserted = true;
        CourseAccessRequest::factory()->for($this->course)->for($learner)->create();
    });

    $this->actingAs($learner)
        ->post(route('courses.access-requests.store', $this->course->uuid))
        ->assertRedirect();

    expect($inserted)->toBeTrue()
        ->and($this->course->accessRequests()->whereBelongsTo($learner)->count())->toBe(1);
});

it('rejects a stale decision after another request has already resolved it', function (string $first, string $second) {
    $pending = CourseAccessRequest::factory()->for($this->course)->create();
    $staleRequest = $pending->fresh();

    $this->actingAs($this->creator)
        ->post(route('studio.courses.access.requests.'.$first, [$this->course->uuid, $pending]))
        ->assertRedirect();

    try {
        app(CourseAccessRequestController::class)->{$second}($this->course, $staleRequest);
        $this->fail('A stale request must not overwrite the first decision.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(404);
    }

    expect($pending->refresh()->status)->toBe($first === 'approve'
        ? CourseAccessRequestStatus::Approved
        : CourseAccessRequestStatus::Denied)
        ->and($this->course->hasAccessGrantFor($pending->user))->toBe($first === 'approve');
})->with([
    ['approve', 'deny'],
    ['deny', 'approve'],
    ['approve', 'approve'],
    ['deny', 'deny'],
]);

it('does not resolve a request belonging to another course', function () {
    $request = CourseAccessRequest::factory()->create();

    $this->actingAs($this->creator)
        ->post(route('studio.courses.access.requests.approve', [$this->course->uuid, $request]))
        ->assertNotFound();

    expect($request->refresh()->status)->toBe(CourseAccessRequestStatus::Pending)
        ->and(CourseUserAccess::query()->exists())->toBeFalse();
});

it('rolls back approval if creating the access grant fails', function () {
    $request = CourseAccessRequest::factory()->for($this->course)->create();
    $this->actingAs($this->creator);

    Event::listen('eloquent.creating: '.CourseUserAccess::class, function () {
        throw new RuntimeException('Grant creation failed.');
    });

    expect(fn () => app(CourseAccessRequestController::class)->approve($this->course, $request))
        ->toThrow(RuntimeException::class, 'Grant creation failed.');

    expect($request->refresh()->status)->toBe(CourseAccessRequestStatus::Pending)
        ->and($request->resolved_by_id)->toBeNull()
        ->and($request->resolved_at)->toBeNull()
        ->and($this->course->hasAccessGrantFor($request->user))->toBeFalse();
});
