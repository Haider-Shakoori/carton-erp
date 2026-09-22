<?php

test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'name' => 'Unauthorized User',
        'email' => 'unauthorized@example.com',
        'password' => 'Strong-password-2026!',
        'password_confirmation' => 'Strong-password-2026!',
    ])->assertNotFound();

    $this->assertGuest();
});
