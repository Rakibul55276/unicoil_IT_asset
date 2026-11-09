<?php

/**
 * Blocks expired companies before an existing controller or model runs.
 * No asset workflow method is changed by this gate.
 */
class SaasGate
{
    public static function enforce(string $controller, string $method): void
    {
        if (!SaasTenantContext::enabled()) {
            return;
        }

        $controller = strtolower($controller);
        $method = strtolower($method);
        if ($controller === "platform") {
            return;
        }

        if (!SaasTenantContext::resolved()) {
            if (
                $controller === "home" &&
                in_array($method, ["index", "members", "logout"], true)
            ) {
                return;
            }
            self::redirectHome("Select a valid company before continuing.");
        }

        if (SaasTenantContext::hasApplicationAccess()) {
            return;
        }

        if ($controller === "subscription" || $controller === "platform") {
            return;
        }
        if (
            $controller === "home" &&
            in_array($method, ["members", "logout"], true)
        ) {
            return;
        }
        if ($controller === "home" && $method === "index" && empty($_SESSION["usr"])) {
            return;
        }

        if (!empty($_SESSION["usr"])) {
            header("Location: " . BASEURL . "/Subscription");
            exit();
        }
    }

    private static function redirectHome(string $message): void
    {
        if (class_exists("Flasher")) {
            Flasher::setMessage("", $message, "danger");
        }
        header("Location: " . BASEURL);
        exit();
    }
}
