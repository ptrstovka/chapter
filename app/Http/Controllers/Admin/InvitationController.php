<?php

namespace App\Http\Controllers\Admin;

use App\Models\Invitation;
use App\Table\Actions\DeleteAction;
use App\Table\Actions\RevokeInvitationAction;
use App\View\Layouts\AdminLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Columns;

class InvitationController
{
    public function index()
    {
        Gate::authorize('viewAny', Invitation::class);

        $invitations = Table::make(Invitation::query())
            ->searchable(fn (Builder $builder, string $term) => $builder->where('code', 'like', '%'.Str::lower($term).'%'))
            ->withColumns([
                Columns\Text::make(__('Code'), fn (Invitation $invitation) => Str::upper($invitation->code))
                    ->fontMono()
                    ->fontMedium()
                    ->numsTabular(),

                Columns\Badge::make(__('Status'), function (Invitation $invitation) {
                    if ($invitation->used_at) {
                        return 'accepted';
                    }

                    if ($invitation->isValid()) {
                        return 'pending';
                    }

                    return 'expired';
                })->label([
                    'accepted' => __('Invitation:Accepted'),
                    'pending' => __('Invitation:Pending'),
                    'expired' => __('Invitation:Expired'),
                ])->variant([
                    'accepted' => 'positive',
                    'pending' => 'default',
                    'expired' => 'destructive',
                ])->width(40),

                Columns\Text::make(__('Accepted By'), fn (Invitation $invitation) => $invitation->usedBy?->name)
                    ->width(40),

                Columns\Text::make(__('Accepted At'), fn (Invitation $invitation) => $invitation->used_at?->format('d.m.Y H:i'))
                    ->numsTabular()
                    ->width(40),

                Columns\Text::make(__('Expires At'), fn (Invitation $invitation) => $invitation->expires_at?->format('d.m.Y H:i') ?: __('Never'))
                    ->numsTabular()
                    ->width(40),
            ])
            ->withActions([
                RevokeInvitationAction::make()
                    ->can(fn (Invitation $invitation) => $invitation->isValid())
                    ->bulk(),

                DeleteAction::make(Invitation::class)
                    ->can(fn (Invitation $invitation) => Gate::allows('delete', $invitation))
                    ->bulk(),
            ]);

        return Inertia::render('Admin/InvitationsPage', AdminLayout::make([
            'invitations' => $invitations,
        ])->breadcrumb(BreadcrumbItem::make(__('Invitations'))));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Invitation::class);

        $request->validate([
            'code' => ['nullable', 'string', 'alpha_num:ascii', 'min:4', 'max:12', Rule::unique(Invitation::class, 'code')],
            'expires_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.now()->format('Y-m-d')],
        ]);

        Invitation::create([
            'code' => Str::lower($request->input('code') ?: Str::random(12)),
            'expires_at' => $request->date('expires_at')?->endOfDay(),
        ]);

        return back();
    }
}
