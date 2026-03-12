<?php

use Illuminate\Support\Facades\Broadcast;

// Default model channel used by Laravel in many examples.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Parent-scoped dashboard stream.
// Why private channel:
// - each parent should only receive events for devices they own.
// How it works:
// - frontend subscribes to private-user.{id}
// - this callback authorizes only if auth user id matches channel id.
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
