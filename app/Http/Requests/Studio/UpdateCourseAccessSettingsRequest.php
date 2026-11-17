<?php

namespace App\Http\Requests\Studio;

use App\Enums\CourseAccessMode;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseAccessSettingsRequest extends FormRequest
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
            'access_mode' => ['required', Rule::enum(CourseAccessMode::class)],
        ];
    }

    public function accessMode(): CourseAccessMode
    {
        return CourseAccessMode::from($this->validated()['access_mode']);
    }
}
