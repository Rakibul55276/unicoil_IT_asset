<?php

class Flasher
{
    public static function setMessage($pesan, $aksi, $type)
    {
        $_SESSION["msg"] = [
            "pesan" => $pesan,
            "aksi" => $aksi,
            "type" => $type,
        ];

        // Preserve safe form values only when a submitted form is rejected.
        // Passwords, tokens, secrets and file uploads are never retained.
        $isRejectedPost =
            strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? "GET")) === "POST" &&
            in_array($type, ["danger", "warning"], true) &&
            !empty($_POST);
        if ($isRejectedPost) {
            $_SESSION["old_form_input"] = self::sanitizeOldInput($_POST);
        } elseif ($type === "success") {
            unset($_SESSION["old_form_input"]);
        }
    }

    /**
     * Return old form input once for the shared page script.
     */
    public static function pullOldInputJson(): string
    {
        $input = $_SESSION["old_form_input"] ?? [];
        unset($_SESSION["old_form_input"]);

        return json_encode(
            is_array($input) ? $input : [],
            JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT |
                JSON_UNESCAPED_UNICODE,
        ) ?: "{}";
    }

    private static function sanitizeOldInput(array $input, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }

        $safe = [];
        foreach (array_slice($input, 0, 500, true) as $key => $value) {
            $key = (string) $key;
            if (
                preg_match(
                    "/password|passwd|csrf|token|secret|api[_-]?key/i",
                    $key,
                )
            ) {
                continue;
            }

            if (is_array($value)) {
                $safe[$key] = self::sanitizeOldInput($value, $depth + 1);
            } elseif (is_scalar($value)) {
                $safe[$key] = substr((string) $value, 0, 5000);
            }
        }

        return $safe;
    }

    public static function Message()
    {
        if (isset($_SESSION["msg"])) {
            $type = in_array(
                $_SESSION["msg"]["type"] ?? "",
                ["success", "info", "warning", "danger", "primary", "secondary"],
                true,
            )
                ? $_SESSION["msg"]["type"]
                : "info";
            $message = htmlspecialchars(
                (string) ($_SESSION["msg"]["pesan"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            );
            $action = htmlspecialchars(
                (string) ($_SESSION["msg"]["aksi"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            );
            echo '<div class="alert alert-' .
                $type .
                ' alert-dismissible fade show" role="alert">
                    <strong>' .
                $message .
                "</strong> " .
                $action .
                '
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                  </div>';

            unset($_SESSION["msg"]);
        }
    }

    public static function msgInfo()
    {
        if (isset($_SESSION["msg"])) {
            $type = in_array(
                $_SESSION["msg"]["type"] ?? "",
                ["success", "info", "warning", "danger", "primary", "secondary"],
                true,
            )
                ? $_SESSION["msg"]["type"]
                : "info";
            $message = htmlspecialchars(
                (string) ($_SESSION["msg"]["pesan"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            );
            $action = htmlspecialchars(
                (string) ($_SESSION["msg"]["aksi"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            );
            echo '<div class="alert alert-' .
                $type .
                '" role="alert">
                    ' .
                $message .
                " <strong> " .
                $action .
                ' </strong>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                 ';

            unset($_SESSION["msg"]);
        }
    }
}
