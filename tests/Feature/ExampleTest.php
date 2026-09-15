<?php

test('the registration page requires authentication', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});
