<?php

namespace App\Http\Requests\Studio;

use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GrantCourseUserAccessRequest extends FormRequest
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
        $users = ['required', 'array', 'min:1'];
        $userExists = Rule::exists(User::class, 'id');
        $rules = [];

        if (! $this->canGrantMultipleUsers()) {
            $users[] = 'max:1';
            $rules['email'] = ['required', 'string', 'email:rfc', 'max:191'];
            $email = $this->input('email');
            $userExists->where(fn (Builder $query) => $query->whereRaw('lower(email) = ?', [
                is_string($email) ? Str::lower(trim($email)) : '',
            ]));
        }

        return [
            ...$rules,
            'users' => $users,
            'users.*' => ['integer', 'distinct', $userExists],
        ];
    }

    public function canGrantMultipleUsers(): bool
    {
        return (bool) ($this->user()?->is_admin || Settings::boolean(Preference::EnableStudioUserDirectory));
    }

    /**
     * @return array<int, int>
     */
    public function userIds(): array
    {
        return array_map('intval', $this->validated()['users']);
    }
}
