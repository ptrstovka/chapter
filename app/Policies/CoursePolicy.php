<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin || $user->isAuthor();
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($author = $user->author) {
            return $course->author->is($author);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->is_admin || $user->isAuthor();
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($author = $user->author) {
            return $course->author->is($author);
        }

        return false;
    }

    public function delete(User $user, Course $course): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($author = $user->author) {
            return $course->author->is($author);
        }

        return false;
    }

    public function enroll(User $user, Course $course): bool
    {
        return true;
    }

    public function study(User $user, Course $course): bool
    {
        return $user->isEnrolledIn($course);
    }

    public function publish(User $user, Course $course): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($author = $user->author) {
            return $course->author->is($author);
        }

        return false;
    }

    public function unpublish(User $user, Course $course): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($author = $user->author) {
            return $course->author->is($author);
        }

        return false;
    }
}
