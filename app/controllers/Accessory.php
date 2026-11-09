<?php

/** Isolated quantity-based accessory operations for Issuer/Administrator. */
class Accessory extends Controller
{
    private $accessory;
    private bool $isAdministrator = false;

    public function __construct()
    {
        if (!isset($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
        if (!defined("ACCESSORY_INVENTORY_ENABLED") || !ACCESSORY_INVENTORY_ENABLED) {
            http_response_code(404);
            exit("Accessory inventory is disabled.");
        }
        $this->requireRoles(
            ["ISSUER"],
            "Only an Issuer or Administrator can manage accessory inventory.",
        );
        if (empty($_SESSION["csrf_accessory_inventory"])) {
            $_SESSION["csrf_accessory_inventory"] = bin2hex(random_bytes(32));
        }
        $username = trim((string) ($_SESSION["usr"]["user"] ?? ""));
        $account = $this->model("User_model")->getUserById($username);
        $roles = is_array($account) ? (array) ($account["roles"] ?? []) : [];
        $this->isAdministrator =
            strtolower($username) === "admin" ||
            in_array("ADMINISTRATOR", $roles, true);
        $this->accessory = $this->model("Accessory_model");
    }

    public function index()
    {
        $search = substr(trim((string) ($_GET["search"] ?? "")), 0, 100);
        $category = substr(trim((string) ($_GET["category"] ?? "")), 0, 100);
        $status = strtoupper(trim((string) ($_GET["status"] ?? "")));
        if (!in_array($status, ["", "AVAILABLE", "LOW", "OUT", "INACTIVE"], true)) {
            $status = "";
        }
        $editItem = null;
        if ($this->isAdministrator && (int) ($_GET["edit"] ?? 0) > 0) {
            $editItem = $this->accessory->getItemById((int) $_GET["edit"]);
        }
        $data = [
            "title" => "IT Asset - Accessory Inventory",
            "menu" => "Accessory Inventory",
            "menu-dsc" => "Quantity-based cables, connectors and accessories",
            "accessoryPage" => true,
            "inventory" => $this->accessory->getInventory($search, $category, $status),
            "items" => $this->accessory->getActiveItems(),
            "transactions" => $this->accessory->getRecentTransactions(),
            "categories" => $this->accessory->getCategories(),
            "employees" => $this->accessory->getActiveEmployees(),
            "departments" => $this->accessory->getActiveDepartments(),
            "search" => $search,
            "category" => $category,
            "status" => $status,
            "csrf" => $_SESSION["csrf_accessory_inventory"],
            "isAdministrator" => $this->isAdministrator,
            "editItem" => $editItem,
        ];
        $this->view("templates/header_a", $data);
        $this->view("accessory/index", $data);
        $this->view("templates/footer_a");
    }

    public function create()
    {
        $this->requirePostAndToken();
        try {
            $this->accessory->createItem(
                $_POST,
                (string) ($_SESSION["usr"]["user"] ?? ""),
            );
            Flasher::setMessage("Accessory", "Created successfully", "success");
        } catch (InvalidArgumentException | RuntimeException $exception) {
            Flasher::setMessage("Accessory not created", $exception->getMessage(), "danger");
        } catch (Throwable $exception) {
            error_log("Accessory creation failed: " . $exception->getMessage());
            Flasher::setMessage("Accessory not created", "The accessory could not be saved.", "danger");
        }
        header("location: " . BASEURL . "/Accessory");
        exit();
    }

    public function transact()
    {
        $this->requirePostAndToken();
        try {
            $number = $this->accessory->recordTransaction(
                $_POST,
                (string) ($_SESSION["usr"]["user"] ?? ""),
            );
            if (strtoupper(trim((string) ($_POST["event_type"] ?? ""))) === "ISSUE") {
                $item = $this->accessory->getItemById((int) ($_POST["accessory_id"] ?? 0));
                if (
                    $item &&
                    (float) $item["quantity_on_hand"] <= (float) $item["reorder_level"]
                ) {
                    $_SESSION["accessory_low_stock_notice"] = [
                        "code" => (string) $item["accessory_code"],
                        "name" => (string) $item["accessory_name"],
                        "stock" => (float) $item["quantity_on_hand"],
                        "level" => (float) $item["reorder_level"],
                        "unit" => (string) $item["unit"],
                    ];
                }
            }
            Flasher::setMessage("Accessory stock updated", "Transaction " . $number, "success");
        } catch (InvalidArgumentException | RuntimeException $exception) {
            Flasher::setMessage("Stock not updated", $exception->getMessage(), "danger");
        } catch (Throwable $exception) {
            error_log("Accessory transaction failed: " . $exception->getMessage());
            Flasher::setMessage("Stock not updated", "The stock action could not be completed.", "danger");
        }
        header("location: " . BASEURL . "/Accessory");
        exit();
    }

    public function update()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can update an accessory item.",
        );
        $this->requirePostAndToken();
        try {
            $this->accessory->updateItem(
                $_POST,
                (string) ($_SESSION["usr"]["user"] ?? ""),
            );
            Flasher::setMessage("Accessory", "Updated successfully", "success");
        } catch (InvalidArgumentException | RuntimeException $exception) {
            Flasher::setMessage("Accessory not updated", $exception->getMessage(), "danger");
        } catch (Throwable $exception) {
            error_log("Accessory update failed: " . $exception->getMessage());
            Flasher::setMessage("Accessory not updated", "The accessory could not be updated.", "danger");
        }
        header("location: " . BASEURL . "/Accessory");
        exit();
    }

    public function delete()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete an accessory item.",
        );
        $this->requirePostAndToken();
        try {
            $this->accessory->deleteItem((int) ($_POST["accessory_id"] ?? 0));
            Flasher::setMessage("Accessory", "Deleted successfully", "success");
        } catch (InvalidArgumentException | RuntimeException $exception) {
            Flasher::setMessage("Accessory not deleted", $exception->getMessage(), "danger");
        } catch (Throwable $exception) {
            error_log("Accessory deletion failed: " . $exception->getMessage());
            Flasher::setMessage("Accessory not deleted", "The accessory could not be deleted.", "danger");
        }
        header("location: " . BASEURL . "/Accessory");
        exit();
    }

    private function requirePostAndToken(): void
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        if (
            empty($_SESSION["csrf_accessory_inventory"]) ||
            !hash_equals(
                (string) $_SESSION["csrf_accessory_inventory"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            Flasher::setMessage("Request rejected", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Accessory");
            exit();
        }
    }
}
