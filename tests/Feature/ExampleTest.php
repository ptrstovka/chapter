<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('returns a successful response', function () {
    $response = $this->followingRedirects()->get('/');

    $response->assertStatus(200);
});
