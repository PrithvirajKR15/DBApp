<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Each third-party (broadcast-eligible) driver listens on their own private
 * channel for new broadcast offers. Authorized only for the matching user's
 * own driver profile.
 */
Broadcast::channel('driver.{driverId}', function ($user, $driverId) {
    return (int) $user->driver?->id === (int) $driverId;
});
