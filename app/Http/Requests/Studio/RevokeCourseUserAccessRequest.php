<?php

namespace App\Http\Requests\Studio;

use App\Models\Course;
use App\Models\CourseUserAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevokeCourseUserAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course && (bool) $this->user()?->can('update', $course);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'users' => ['required', 'array', 'min:1'],
            'users.*' => [
                'integer',
                'distinct',
                Rule::exists(CourseUserAccess::class, 'user_id')
                    ->where('course_id', $this->course()->id),
            ],
        ];
    }

    public function course(): Course
    {
        return $this->route('course');
    }

    /**
     * @return array<int, int>
     */
    public function userIds(): array
    {
        return array_map('intval', $this->validated()['users']);
    }
}
