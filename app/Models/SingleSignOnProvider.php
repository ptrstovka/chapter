<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Socialite\CustomSSOProviderDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laravel\Socialite\Contracts\Provider;

/**
 * @property bool $is_active
 * @property array|null $configuration
 * @property string $name
 * @property string $type
 */
class SingleSignOnProvider extends Model
{
    use HasUuid;

    protected $guarded = false;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'configuration' => 'encrypted:array',
        ];
    }

    /**
     * Get the SSO callback URL.
     */
    public function getCallbackUrl(): string
    {
        return route('login.sso.callback', $this);
    }

    /**
     * Get the SSO redirect URL.
     */
    public function getRedirectUrl(): string
    {
        return route('login.sso.redirect', $this);
    }

    /**
     * Get the login button title.
     */
    public function getLoginButtonTitle(): string
    {
        return Arr::get($this->configuration, 'login_button_title');
    }

    /**
     * Get the login button text color.
     */
    public function getLoginButtonTextColor(): string
    {
        return Arr::get($this->configuration, 'login_button_text_color');
    }

    /**
     * Get the login button background color.
     */
    public function getLoginButtonBackgroundColor(): string
    {
        return Arr::get($this->configuration, 'login_button_background_color');
    }

    /**
     * Create a Socialite driver for this provider.
     */
    public function createDriver(): Provider
    {
        if ($this->type === 'custom') {
            return new CustomSSOProviderDriver($this);
        }

        throw new InvalidArgumentException('This provider type is not supported.');
    }
}
