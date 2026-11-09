<?php

class User extends Controller
{
    public function __construct()
    {
        if (isset($_SESSION["usr"])) {
        } else {
            header("location:" . BASEURL);
            exit();
        }
    }

    public function index()
    {
        $this->requireAccessAdministrator();
        if (empty($_SESSION["csrf_user_delete"])) {
            $_SESSION["csrf_user_delete"] = bin2hex(random_bytes(32));
        }
        $data["title"] = "IT-Asset Maintain User";
        $data["menu"] = "User";
        $data["menu-dsc"] = "Maintain User";
        $data["rdata"] = $this->model("User_model")->userList();
        $data["deleteCsrf"] = $_SESSION["csrf_user_delete"];

        $this->view("templates/header_a", $data);
        $this->view("user/index", $data);
        $this->view("templates/footer_a");
    }

    public function create()
    {
        $this->requireAccessAdministrator();
        if (empty($_SESSION["csrf_user_create"])) {
            $_SESSION["csrf_user_create"] = bin2hex(random_bytes(32));
        }
        $data["title"] = "IT-Asset Create User";
        $data["menu"] = "User";
        $data["menu-dsc"] = "Create User";

        $data["userData"] = null;
        $data["employees"] = $this->model("User_model")->getActiveEmployees();
        $data["post_url"] = BASEURL . "/User/register";
        $data["csrfToken"] = $_SESSION["csrf_user_create"];
        $this->view("templates/header_a", $data);
        $this->view("user/create", $data);
        $this->view("templates/footer_a");
    }
    public function edit($id)
    {
        $this->requireAccessAdministrator();
        $m = $this->model("User_model");
        if (empty($_SESSION["csrf_user_update"])) {
            $_SESSION["csrf_user_update"] = bin2hex(random_bytes(32));
        }
        $data = [
            "title" => "IT-Asset Edit User",
            "menu" => "User",
            "menu-dsc" => "Edit User",
            "userData" => $m->getUserById($id),
            "employees" => $m->getActiveEmployees(),
            "post_url" => BASEURL . "/User/update",
            "csrfToken" => $_SESSION["csrf_user_update"],
        ];
        if (!$data["userData"]) {
            header("location: " . BASEURL . "/User");
            exit();
        }
        $this->view("templates/header_a", $data);
        $this->view("user/create", $data);
        $this->view("templates/footer_a");
    }
    public function update()
    {
        $this->requireAccessAdministrator();
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        $token = $_POST["csrf_token"] ?? "";
        if (
            empty($_SESSION["csrf_user_update"]) ||
            !hash_equals($_SESSION["csrf_user_update"], $token)
        ) {
            Flasher::setMessage(
                "Update failed",
                "Invalid form token. Please reopen the edit form.",
                "danger",
            );
            header("location: " . BASEURL . "/User");
            exit();
        }
        try {
            $this->model("User_model")->updateData($_POST);
            unset($_SESSION["csrf_user_update"]);
            Flasher::setMessage("User", "Credentials updated successfully", "success");
        } catch (Throwable $e) {
            Flasher::setMessage("Update failed", $e->getMessage(), "danger");
        }
        header("location: " . BASEURL . "/User");
        exit();
    }

    public function profile()
    {
        $model = $this->model("User_model");
        $username = $_SESSION["usr"]["user"];
        $user = $model->getUserById($username);
        if (!$user) {
            unset($_SESSION["usr"]);
            Flasher::setMessage("Session ended", "Your user account could not be found.", "danger");
            header("location: " . BASEURL);
            exit();
        }
        if (empty($_SESSION["csrf_profile_update"])) {
            $_SESSION["csrf_profile_update"] = bin2hex(random_bytes(32));
        }
        $data = [
            "title" => "My Profile",
            "menu" => "My Profile",
            "menu-dsc" => "Edit account details",
            "userData" => $user,
            "csrfToken" => $_SESSION["csrf_profile_update"],
        ];
        $this->view("templates/header_a", $data);
        $this->view("user/profile", $data);
        $this->view("templates/footer_a");
    }

    public function updateProfile()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        $token = $_POST["csrf_token"] ?? "";
        if (
            empty($_SESSION["csrf_profile_update"]) ||
            !hash_equals($_SESSION["csrf_profile_update"], $token)
        ) {
            Flasher::setMessage(
                "Update failed",
                "Invalid form token. Please reopen your profile.",
                "danger",
            );
            header("location: " . BASEURL . "/User/profile");
            exit();
        }
        try {
            $user = $this->model("User_model")->updateProfile($_SESSION["usr"]["user"], $_POST);
            $_SESSION["usr"]["name"] = $user["nama"];
            unset($_SESSION["csrf_profile_update"]);
            Flasher::setMessage("Profile", "Updated successfully", "success");
        } catch (PDOException $e) {
            $message =
                $e->getCode() === "23000"
                    ? "That email address is already used by another account."
                    : "The profile could not be updated.";
            Flasher::setMessage("Update failed", $message, "danger");
        } catch (Throwable $e) {
            Flasher::setMessage("Update failed", $e->getMessage(), "danger");
        }
        header("location: " . BASEURL . "/User/profile");
        exit();
    }

    public function register()
    {
        $this->requireAccessAdministrator();
        if (
            ($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST" ||
            empty($_SESSION["csrf_user_create"]) ||
            !hash_equals(
                (string) $_SESSION["csrf_user_create"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            http_response_code(403);
            exit("Invalid form token.");
        }
        try {
            $result = $this->model("User_model")->register($_POST);
            if ($result === "X") {
                throw new InvalidArgumentException("Username already exists.");
            }
            unset($_SESSION["csrf_user_create"]);
            Flasher::setMessage("User created.", "Employee and workflow roles were saved.", "success");
            header("location: " . BASEURL . "/User");
        } catch (Throwable $error) {
            Flasher::setMessage("User not created.", $error->getMessage(), "danger");
            header("location: " . BASEURL . "/User/create");
        }
        exit();
    }

    public function hapusData()
    {
        $this->requireAccessAdministrator();
        if (
            ($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST" ||
            empty($_SESSION["csrf_user_delete"]) ||
            !hash_equals(
                (string) $_SESSION["csrf_user_delete"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            http_response_code(403);
            exit("Invalid form token.");
        }
        $id = trim((string) ($_POST["username"] ?? ""));
        if ($id === "" || strtolower($id) === "admin") {
            Flasher::setMessage("Delete blocked", "The built-in administrator cannot be deleted.", "danger");
            header("location: " . BASEURL . "/User");
            exit();
        }
        if ($this->model("User_model")->deleteData($id) > 0) {
            unset($_SESSION["csrf_user_delete"]);
            Flasher::setMessage("User Successfully", "Deleted", "success");
            header("location: " . BASEURL . "/User");
            exit();
        } else {
            Flasher::setMessage("Error", "Delete Data", "danger");
            header("location: " . BASEURL . "/User");
            exit();
        }
    }

    private function requireAccessAdministrator(): void
    {
        $username = (string) ($_SESSION["usr"]["user"] ?? "");
        $user = $this->model("User_model")->getUserById($username);
        $roles = $user["roles"] ?? [];
        if (
            strtolower($username) !== "admin" &&
            !in_array("ADMINISTRATOR", $roles, true)
        ) {
            http_response_code(403);
            exit("Only an Administrator can manage user access and workflow roles.");
        }
    }
}
