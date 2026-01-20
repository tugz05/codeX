<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/classlist');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the classlist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/classlist');
    $response->assertStatus(200);
});
