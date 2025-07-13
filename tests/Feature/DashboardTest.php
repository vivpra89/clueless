<?php

test('dashboard page is accessible', function () {
    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('realtime agent page is accessible', function () {
    $response = $this->get('/realtime-agent');
    $response->assertStatus(200);
});
