<?php

namespace App\Enums;

enum Preference: string
{
    case PlatformName = 'Platform.Name';
    case PlatformLocale = 'Platform.Locale';

    case EnableRegistration = 'Access.Registration';
    case EnableInvitations = 'Access.Invitations';
    case EnableSingleSignOn = 'Access.SingleSignOn';
    case EnablePasswordLogin = 'Access.PasswordLogin';

    /**
     * Get the default value of the preference.
     *
     * @noinspection PhpDuplicateMatchArmBodyInspection
     * @noinspection PhpUnusedMatchConditionInspection
     */
    public function defaultValue(): mixed
    {
        return match ($this) {
            Preference::PlatformName => config('app.name'),
            Preference::PlatformLocale => config('app.locale'),
            Preference::EnableRegistration => true,
            Preference::EnableInvitations => false,
            Preference::EnableSingleSignOn => false,
            Preference::EnablePasswordLogin => true,
            default => null,
        };
    }
}
