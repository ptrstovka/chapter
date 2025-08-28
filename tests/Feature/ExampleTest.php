<?php

it('returns a successful response', function () {
    $response = $this->followingRedirects()->get('/');

    $response->assertStatus(200);
});
