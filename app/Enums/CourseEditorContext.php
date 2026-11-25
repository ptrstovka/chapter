<?php

namespace App\Enums;

use Illuminate\Http\Request;

enum CourseEditorContext: string
{
    case Studio = 'studio';
    case Admin = 'admin.course-editor';

    public static function current(): self
    {
        return self::forRequest(request());
    }

    public static function forRequest(Request $request): self
    {
        return $request->routeIs(self::Admin->routeName('*')) ? self::Admin : self::Studio;
    }

    public function routeName(string $name): string
    {
        return "$this->value.$name";
    }

    public function indexRoute(): string
    {
        return match ($this) {
            self::Studio => 'studio.courses',
            self::Admin => 'admin.courses',
        };
    }
}
