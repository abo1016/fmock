<?php

return [
    'github' => [
        'client_id' => env('GithubClientID', ''),
        'client_secret' => env('GithubClientSecret', ''),
        'base_url' => 'https://github.com/login/oauth/authorize',
        'call_back' => env('SERVER_URL') . '/api/V1/oauth/github/callback',
        'scope' => 'user',
        'access_token_url' => 'https://github.com/login/oauth/access_token',
        'user_info_url' => 'https://api.github.com/user',
    ],
];
