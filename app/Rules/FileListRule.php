<?php

namespace App\Rules;

use App\Models\TemporaryUpload;
use App\Support\FileListAdapter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FileListRule implements ValidationRule
{
    public function __construct(
        protected string $scope,
        protected FileListAdapter $adapter,
    ) {}

    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $validator = Validator::make([$attribute => $value], [
            "{$attribute}" => ['array'],
            "{$attribute}.*.type" => ['required', 'string', Rule::in(['existing', 'temporary'])],
            "{$attribute}.*.id" => ['required'],
            "{$attribute}.*.name" => ['nullable', 'string', 'max:191'],
        ]);

        if ($validator->fails()) {
            $fail($validator->errors()->first());

            return;
        }

        $files = collect($value);

        $temporaryIds = $files->where('type', 'temporary')->pluck('id');
        $temporaryUploads = TemporaryUpload::query()
            ->whereIn('uuid', $temporaryIds)
            ->where('scope', $this->scope)
            ->whereBelongsTo(Auth::user())
            ->get()
            ->pluck('uuid');

        foreach ($temporaryIds as $id) {
            if (! $temporaryUploads->contains($id)) {
                $fail('This file is not valid anymore. Please, upload it again.')->translate();

                return;
            }
        }

        $existingIds = $files->where('type', 'existing')->pluck('id');
        $existingUploads = $this->adapter->list()->pluck('id');

        foreach ($existingIds as $id) {
            if (! $existingUploads->contains($id)) {
                $fail('This file is not valid.')->translate();

                return;
            }
        }
    }

    /**
     * Create a new rule instance.
     */
    public static function make(string $scope, FileListAdapter $adapter): static
    {
        return new static($scope, $adapter);
    }
}
