<?php

class Auth
{
    public static function setLoginSession($user, $role = "")
    {
        // Rotate the session identifier after authentication to prevent fixation.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION["usr"] = [
            "user" => $user,
            "role" => $role,
        ];
    }
}
