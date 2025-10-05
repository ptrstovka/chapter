<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SettingsRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        App::macro('contentDisk', function (): string {
            return config('filesystems.content_disk');
        });

        Arr::macro('insert', function (array $array, mixed $item, int $index) {
            $count = count($array);

            if ($index <= 0) {
                array_unshift($array, $item);
            } elseif ($index >= $count) {
                $array[] = $item;
            } else {
                array_splice($array, $index, 0, [$item]);
            }

            return $array;
        });

        Arr::macro('move', function (array $array, int $fromIndex, int $toIndex) {
            $count = count($array);

            if ($fromIndex < 0 || $fromIndex >= $count) {
                return;
            }

            if ($toIndex < 0) {
                $toIndex = 0;
            } elseif ($toIndex >= $count) {
                $toIndex = $count - 1;
            }

            $item = $array[$fromIndex];
            array_splice($array, $fromIndex, 1);
            array_splice($array, $toIndex, 0, [$item]);

            return $array;
        });

        $this->app->bind('settings', SettingsRepository::class);
    }

    public function boot(): void
    {
        Gate::define('accessStudio', function (User $user) {
            return $user->author != null;
        });

        Gate::define('viewAdmin', function (User $user) {
            return $user->is_admin;
        });
    }
}
