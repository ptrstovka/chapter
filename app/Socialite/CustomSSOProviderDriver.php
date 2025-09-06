<?php

namespace App\Socialite;

use App\Models\SingleSignOnProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class CustomSSOProviderDriver extends AbstractProvider
{
    /**
     * The provider configuration.
     */
    protected array $config;

    public function __construct(SingleSignOnProvider $provider)
    {
        parent::__construct(
            request: App::make('request'),
            clientId: $provider->configuration['client_id'],
            clientSecret: $provider->configuration['client_secret'],
            redirectUrl: $provider->getCallbackUrl(),
            guzzle: [
                'verify' => !App::isLocal(),
            ]
        );

        $this->config = $provider->configuration;
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->config['authorize_url'], $state);
    }

    protected function getTokenUrl()
    {
        return $this->config['token_url'];
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->config['user_url'], [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $user = json_decode($response->getBody()->getContents(), true);

        if (is_array($user)) {
            return $user;
        }

        return null;
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)
            ->setRaw($user)
            ->map([
                'email' => Arr::get($user, $this->config['user_email_field']),
                'name' => Arr::get($user, $this->config['user_name_field']),
                'avatar' => Arr::get($user, $this->config['user_avatar_field']),
            ]);
    }
}
