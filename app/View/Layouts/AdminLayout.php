<?php

namespace App\View\Layouts;

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
                            title: __('Courses'),
                            action: Link::to(route('admin.courses')),
                            icon: new Icon('graduation-cap'),
                        )->active(routes: [
                            'admin.courses',
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
