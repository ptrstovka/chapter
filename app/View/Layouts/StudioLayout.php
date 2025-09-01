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

class StudioLayout extends Layout
{
    /**
     * The Breadcrumb navigation.
     */
    protected BreadcrumbList $breadcrumbs;

    public function __construct(array $props = [])
    {
        parent::__construct($props);

        $this->breadcrumbs = new BreadcrumbList;
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
                    // ->addChild(
                    //     MenuItem::make(
                    //         title: __('Overview'),
                    //         action: Link::to(route('studio.overview')),
                    //         icon: new Icon('chart-line'),
                    //     )
                    // )
                    ->addChild(
                        MenuItem::make(
                            title: __('Courses'),
                            action: Link::to(route('studio.courses')),
                            icon: new Icon('graduation-cap'),
                        )->active(routes: [
                            'studio.courses',
                            'studio.courses.*',
                            'studio.course.*',
                        ])
                    )
                    // ->addChild(
                    //     MenuItem::make(
                    //         title: __('Profile'),
                    //         action: Link::to(route('studio.profile')),
                    //         icon: new Icon('contact'),
                    //     )
                    // )
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
