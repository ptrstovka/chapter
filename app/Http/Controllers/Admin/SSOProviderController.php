<?php


namespace App\Http\Controllers\Admin;


use App\Models\SingleSignOnProvider;
use App\Table\Columns as CustomColumns;
use App\View\Layouts\AdminLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Link;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Columns;
use StackTrace\Ui\Table\Actions;

class SSOProviderController
{
    public function index()
    {
        $providers = Table::make(SingleSignOnProvider::query())
            ->searchable(fn (Builder $builder, string $term) => $builder->where(DB::raw('lower(name)'), 'like', '%'.Str::lower($term).'%'))
            ->withColumns([
                Columns\Text::make(__('Provider Name'), 'name')
                    ->fontMedium()
                    ->link(fn (SingleSignOnProvider $provider) => Link::to(route('admin.sso.edit', $provider))),

                CustomColumns\Toggle::make(__('Active'), 'is_active')
                    ->url(fn (SingleSignOnProvider $provider) => route('admin.sso.activate', $provider))
                    ->method('post')
                    ->field('is_active')
                    ->width(28)
                    ->alignCenter(),

                Columns\Text::make(__('Type'), 'type')
                    ->width(28),
            ])
            ->withActions([
                Actions\Link::make(__('Manage'), fn (SingleSignOnProvider $provider) => Link::to(route('admin.sso.edit', $provider))),
            ]);

        return Inertia::render('Admin/SSOProvidersPage', AdminLayout::make([
            'providers' => $providers,
        ])->breadcrumb(BreadcrumbItem::make(__('SSO Providers'))));
    }

    public function create()
    {
        return Inertia::render('Admin/SSOProviderFormPage', AdminLayout::make([

        ])->breadcrumb([
            BreadcrumbItem::make(__('SSO Providers'), Link::to(route('admin.sso'))),
            BreadcrumbItem::make(__('New Provider')),
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['custom'])],
            'name' => ['required', 'string', 'max:191'],
        ]);

        $type = $request->input('type');

        $configuration = [];

        if ($type === 'custom') {
            $configuration = $request->validate([
                'client_id' => ['required', 'string', 'max:128'],
                'client_secret' => ['required', 'string', 'max:128'],
                'authorize_url' => ['required', 'string', 'max:256'],
                'token_url' => ['required', 'string', 'max:256'],
                'user_url' => ['required', 'string', 'max:256'],
                'user_email_field' => ['required', 'string', 'max:30'],
                'user_name_field' => ['required', 'string', 'max:30'],
                'user_avatar_field' => ['required', 'string', 'max:30'],
                'login_button_title' => ['required', 'string', 'max:64'],
                'login_button_text_color' => ['required', 'string', 'regex:/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i'],
                'login_button_background_color' => ['required', 'string', 'regex:/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i'],
            ]);
        } else {
            abort(404);
        }

        $provider = SingleSignOnProvider::create([
            'type' => $type,
            'name' => $request->input('name'),
            'is_active' => true,
            'configuration' => $configuration,
        ]);

        return to_route('admin.sso.edit', $provider);
    }

    public function edit(SingleSignOnProvider $provider)
    {
        return Inertia::render('Admin/SSOProviderFormPage', AdminLayout::make([
            'provider' => [
                'id' => $provider->uuid,
                'name' => $provider->name,
                'type' => $provider->type,
                'configuration' => $provider->configuration,
                'isActive' => $provider->is_active,
                'callbackUrl' => $provider->getCallbackUrl(),
            ],
        ])->breadcrumb([
            BreadcrumbItem::make(__('SSO Providers'), Link::to(route('admin.sso'))),
            BreadcrumbItem::make($provider->name),
        ]));
    }

    public function update(Request $request, SingleSignOnProvider $provider)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        $configuration = $provider->configuration;

        if ($provider->type === 'custom') {
            $configuration = $request->validate([
                'client_id' => ['required', 'string', 'max:128'],
                'client_secret' => ['required', 'string', 'max:128'],
                'authorize_url' => ['required', 'string', 'max:256'],
                'token_url' => ['required', 'string', 'max:256'],
                'user_url' => ['required', 'string', 'max:256'],
                'user_email_field' => ['required', 'string', 'max:30'],
                'user_name_field' => ['required', 'string', 'max:30'],
                'user_avatar_field' => ['required', 'string', 'max:30'],
                'login_button_title' => ['required', 'string', 'max:64'],
                'login_button_text_color' => ['required', 'string', 'regex:/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i'],
                'login_button_background_color' => ['required', 'string', 'regex:/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i'],
            ]);
        }

        $provider->update([
            'name' => $request->input('name'),
            'configuration' => $configuration,
        ]);

        return back();
    }

    public function destroy(SingleSignOnProvider $provider)
    {
        $provider->delete();

        return to_route('admin.sso');
    }
}
