<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you remain free to modify this option if you wish.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => env('HASH_DRIVER', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of work it takes to hash a password using
    | the bcrypt algorithm. The default cost is 12, which is a reasonable
    | amount of work that helps keep passwords safe from brute force.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | These options define the memory and time cost factors used by the Argon
    | hashing algorithm. The default values provide a strong balance between
    | security and performance. Increase them if your hardware allows.
    |
    */

    'argon' => [
        'memory' => 65536,
        'threads' => 8,
        'time' => 16,
        'verify' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Setting this option to true will tell Laravel to automatically rehash
    | the user's password during login if the configured work factor for
    | the algorithm has changed, allowing a gradual password rehashing.
    |
    */

    'rehash_on_login' => true,

];
