<?php

return [

    /**
     * The session key used to store the original user id.
     */
    'session_key' => 'impersonated_by',

    /**
     * The session key used to stored the original user guard.
     */
    'session_guard' => 'impersonator_guard',

    /**
     * The session key used to stored what guard is impersonator using.
     */
    'session_guard_using' => 'impersonator_guard_using',

    /**
     * The default impersonator guard used.
     */
    'default_impersonator_guard' => 'web',

    /**
     * The URI to redirect after taking an impersonation.
     *
     * Only used in the built-in controller.
     * Use 'back' to redirect to the previous page
     *
     * Qayema: the owner's dashboard (app.dashboard_url), which is what the
     * admin came to see; it carries a "Back to admin" banner.
     */
    'take_redirect_to' => env('DASHBOARD_URL', env('APP_URL', 'http://localhost')),

    /**
     * The URI to redirect after leaving an impersonation.
     *
     * Only used in the built-in controller.
     * Use 'back' to redirect to the previous page
     *
     * Qayema: the admin's Users list, where impersonating starts.
     */
    'leave_redirect_to' => 'filament.admin.resources.users.index',

];
