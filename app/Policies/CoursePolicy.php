<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Create a new policy instance.
     */
    public function update(User $user, Course $course)
    {
        return $user->id === $course->user_id;
    }

    public function delete(User $user, Course $course)
    {
        return $user->id === $course->user_id;
    }
}
