<?php

namespace App\Http\Requests\Studio;

use App\Enums\Preference;
use App\Facades\Settings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UserLookupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->is_admin || $user->can('accessStudio'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->canSearchDirectory()) {
            return [
                'query' => ['required', 'string', 'max:191'],
            ];
        }

        return [
            'query' => ['required', 'string', 'email:rfc', 'max:191'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        if ($this->canSearchDirectory()) {
            return [];
        }

        return [
            'query.email' => __('Enter a full email address to find a user.'),
        ];
    }

    public function queryTerm(): string
    {
        return (string) $this->validated()['query'];
    }

    public function canSearchDirectory(): bool
    {
        return (bool) ($this->user()?->is_admin || $this->studioDirectorySearchEnabled());
    }

    public function studioDirectorySearchEnabled(): bool
    {
        return Settings::boolean(Preference::EnableStudioUserDirectory);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->input('query', $this->input('search', $this->input('email')));

        if (is_string($query)) {
            $query = Str::of($query)->trim()->toString();
        }

        if ($query !== null) {
            $this->merge([
                'query' => $query,
            ]);
        }
    }
}
