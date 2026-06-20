<?php

test('the root redirects to admin login', function () {
    $this->get('/')
        ->assertRedirect(route('admin.login'));
});
