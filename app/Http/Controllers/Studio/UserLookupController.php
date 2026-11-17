<?php

namespace App\Http\Controllers\Studio;

use App\Http\Requests\Studio\UserLookupRequest;
use App\Http\Resources\Studio\UserLookupResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class UserLookupController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UserLookupRequest $request): JsonResponse|AnonymousResourceCollection|UserLookupResource
    {
        if ($request->canSearchDirectory()) {
            $users = User::query()
                ->select(['id', 'name', 'email'])
                ->where(function (Builder $builder) use ($request) {
                    $term = '%'.Str::lower($request->queryTerm()).'%';

                    $builder
                        ->whereRaw('lower(name) like ?', [$term])
                        ->orWhereRaw('lower(email) like ?', [$term]);
                })
                ->orderBy('name')
                ->orderBy('email')
                ->limit(20)
                ->get();

            return UserLookupResource::collection($users)
                ->additional($this->meta($request, 'search'));
        }

        $user = User::query()
            ->select(['id', 'name', 'email'])
            ->whereRaw('lower(email) = ?', [Str::lower($request->queryTerm())])
            ->first();

        if (! $user) {
            return response()->json([
                'message' => __('No user was found for that email address.'),
                'data' => null,
                ...$this->meta($request, 'exact_email'),
            ], 404);
        }

        return (new UserLookupResource($user))
            ->additional($this->meta($request, 'exact_email'));
    }

    /**
     * @return array{meta: array{lookupMode: string, searchable: bool, directorySearchEnabled: bool}}
     */
    protected function meta(UserLookupRequest $request, string $lookupMode): array
    {
        return [
            'meta' => [
                'lookupMode' => $lookupMode,
                'searchable' => $request->canSearchDirectory(),
                'directorySearchEnabled' => $request->studioDirectorySearchEnabled(),
            ],
        ];
    }
}
