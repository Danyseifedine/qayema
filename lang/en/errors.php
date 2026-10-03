<?php

// The error pages (resources/views/errors). Each headline is `title` then
// `gold`, the part drawn in gold, the way the other page heroes read.
return [
    'eyebrow' => 'Error :code',
    'back_home' => 'Back to home',
    'try_again' => 'Try again',
    'pricing' => 'See pricing',

    '401' => ['title' => 'Sign in to', 'gold' => 'continue', 'message' => 'This page is for signed-in restaurant owners.'],
    '403' => ['title' => 'This table is', 'gold' => 'reserved', 'message' => "You don't have access to this page."],
    '404' => ['title' => 'This page is', 'gold' => 'off the menu', 'message' => "It doesn't exist, or it has moved."],
    '419' => ['title' => 'This page', 'gold' => 'expired', 'message' => 'It was open for a long time. Reload it and try again.'],
    '429' => ['title' => 'One moment,', 'gold' => 'please', 'message' => 'Too many tries. Wait a minute, then try again.'],
    '500' => ['title' => 'Our kitchen', 'gold' => 'slipped up', 'message' => 'Something went wrong on our side, not yours. Try again in a moment.'],
    '503' => ['title' => 'Back', 'gold' => 'soon', 'message' => "We're making Qayema better. Try again in a few minutes."],
    '4xx' => ['title' => 'That', 'gold' => "didn't work", 'message' => 'Check the address and try again.'],
    '5xx' => ['title' => 'Our kitchen', 'gold' => 'slipped up', 'message' => 'Something went wrong on our side, not yours. Try again in a moment.'],
];
