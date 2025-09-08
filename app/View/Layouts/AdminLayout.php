<?php

namespace App\View\Layouts;

use App\Enums\Preference;
use App\Facades\Settings;
use App\View\Layout;
use Closure;
use Illuminate\Support\Arr;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Breadcrumbs\BreadcrumbList;
use StackTrace\Ui\Icon;
use StackTrace\Ui\Link;
use StackTrace\Ui\Menu\Menu;
use StackTrace\Ui\Menu\MenuItem;

class AdminLayout extends Layout
{
    /**
     * The Breadcrumb navigation.
     */
    protected BreadcrumbList $breadcrumbs;

    public function __construct(array $props = [])
    {
        parent::__construct($props);

        $this->breadcrumbs = new BreadcrumbList;

        $this->breadcrumbs->append(BreadcrumbItem::make(__('Admin'), Link::to(route('admin'))));
    }

    /**
     * Add a breadcrumb item to the list.
     */
    public function breadcrumb(BreadcrumbItem|array|Closure $item): static
    {
        if ($item instanceof Closure) {
            call_user_func($item, $this->breadcrumbs);
        } else {
            foreach (Arr::wrap($item) as $breadcrumb) {
                $this->breadcrumbs->append($breadcrumb);
            }
        }

        return $this;
    }

    /**
     * The Sidebar menu configuration.
     */
    protected function sidebar(): Menu
    {
        return Menu::make()
            ->add(
                MenuItem::make()
                    ->addChild(
                        MenuItem::make(
                            title: __('Settings'),
                            action: Link::to(route('admin.settings')),
                            icon: new Icon('settings'),
                        )->active(routes: [
                            'admin.settings',
                        ])
                    )
                    ->addChild(
                        MenuItem::make(
                            title: __('Courses'),
                            action: Link::to(route('admin.courses')),
                            icon: new Icon('graduation-cap'),
                        )->active(routes: [
                            'admin.courses',
                        ])
                    )
                    ->addChild(
                        MenuItem::make(
                            title: __('Instructors'),
                            action: Link::to(route('admin.instructors')),
                            icon: new Icon('contact'),
                        )->active(routes: [
                            'admin.instructors*',
                        ])
                    )
                    ->addChild(
                        MenuItem::make(
                            title: __('Categories'),
                            action: Link::to(route('admin.categories')),
                            icon: new Icon('list-tree'),
                        )->active(routes: [
                            'admin.categories',
                        ])
                    )
                    ->when(
                        Settings::boolean(Preference::EnableRegistration) && Settings::boolean(Preference::EnableInvitations),
                        fn (MenuItem $menu) => $menu->addChild(
                            MenuItem::make(
                                title: __('Invitations'),
                                action: Link::to(route('admin.invitations')),
                                icon: new Icon('send'),
                            )->active(routes: [
                                'admin.invitations',
                            ])
                        )
                    )
                    ->when(
                        Settings::boolean(Preference::EnableSingleSignOn),
                        fn (MenuItem $menu) => $menu->addChild(
                            MenuItem::make(
                                title: __('SSO Providers'),
                                action: Link::to(route('admin.sso')),
                                icon: new Icon('key-square'),
                            )->active(routes: [
                                'admin.sso*',
                            ])
                        )
                    )
                    ->addChild(
                        MenuItem::make(
                            title: __('Users'),
                            action: Link::to(route('admin.users')),
                            icon: new Icon('users'),
                        )->active(routes: [
                            'admin.users',
                        ])
                    )
            );
    }

    public function toLayout(): array
    {
        return [
            'sidebar' => $this->sidebar(),
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }

    /**
     * Create a new layout instance.
     */
    public static function make(): static
    {
        return new static(...func_get_args());
    }
}
