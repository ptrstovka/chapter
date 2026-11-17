<?php

namespace App\Http\Requests\Studio;

use App\Enums\AccessGroupVisibility;
use App\Models\AccessGroup;
use App\Models\Course;
use App\Models\CourseGroupAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class RevokeCourseGroupAccessRequest extends FormRequest
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
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => [
                'integer',
                'distinct',
                $this->visibleGroupRule(),
                Rule::exists(CourseGroupAccess::class, 'access_group_id')
                    ->where('course_id', $this->course()->id),
            ],
        ];
    }

    public function course(): Course
    {
        return $this->route('course');
    }

    public function visibleGroupRule(): Exists
    {
        $rule = Rule::exists(AccessGroup::class, 'id');

        if (! $this->user()?->is_admin) {
            $rule->where('visibility', AccessGroupVisibility::Public->value);
        }

        return $rule;
    }

    /**
     * @return array<int, int>
     */
    public function groupIds(): array
    {
        return array_map('intval', $this->validated()['groups']);
    }
}
