<?php

it('redirects the root URL to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
