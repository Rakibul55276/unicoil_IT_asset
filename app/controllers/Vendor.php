<?php
class Vendor extends Controller
{
    private $vendor;
    public function __construct()
    {
        if (!isset($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
        $this->requireRoles(
            defined("ISSUER_UI_ENABLED") && ISSUER_UI_ENABLED
                ? ["IT_MANAGER", "ISSUER"]
                : ["IT_MANAGER"],
            "Only an IT Manager or Administrator can manage vendors.",
        );
        $this->vendor = $this->model("Vendor_model");
    }
    public function index()
    {
        if (empty($_SESSION["csrf_vendor_delete"])) {
            $_SESSION["csrf_vendor_delete"] = bin2hex(random_bytes(32));
        }
        $data = [
            "title" => "IT Asset - Vendors",
            "menu" => "Vendors",
            "menu-dsc" => "Maintain asset suppliers",
            "vendors" => $this->vendor->getAll(),
            "deleteCsrf" => $_SESSION["csrf_vendor_delete"],
        ];
        $this->view("templates/header_a", $data);
        $this->view("vendor/index", $data);
        $this->view("templates/footer_a");
    }
    public function add()
    {
        $this->form(null, BASEURL . "/Vendor/create");
    }
    public function edit($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can edit vendors.",
        );
        $row = $this->vendor->getById($id);
        if (!$row) {
            header("location: " . BASEURL . "/Vendor");
            exit();
        }
        $this->form($row, BASEURL . "/Vendor/update");
    }
    private function form($row, $url)
    {
        $data = [
            "title" => "IT Asset - Vendor",
            "menu" => "Vendors",
            "menu-dsc" => $row ? "Edit supplier" : "Add supplier",
            "vendor" => $row,
            "post_url" => $url,
        ];
        $this->view("templates/header_a", $data);
        $this->view("vendor/form", $data);
        $this->view("templates/footer_a");
    }
    public function create()
    {
        try {
            $this->vendor->save($_POST);
            Flasher::setMessage("Vendor", "Created successfully", "success");
        } catch (Throwable $e) {
            Flasher::setMessage("Vendor", $e->getMessage(), "danger");
        }
        header("location: " . BASEURL . "/Vendor");
        exit();
    }
    public function update()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can update vendors.",
        );
        try {
            $this->vendor->update($_POST);
            Flasher::setMessage("Vendor", "Updated successfully", "success");
        } catch (Throwable $e) {
            Flasher::setMessage("Vendor", $e->getMessage(), "danger");
        }
        header("location: " . BASEURL . "/Vendor");
        exit();
    }
    public function delete()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete vendors.",
        );
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        $token = $_POST["csrf_token"] ?? "";
        if (
            empty($_SESSION["csrf_vendor_delete"]) ||
            !hash_equals($_SESSION["csrf_vendor_delete"], $token)
        ) {
            Flasher::setMessage("Delete failed", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Vendor");
            exit();
        }
        try {
            $this->vendor->delete($_POST["vendorId"] ?? 0);
            unset($_SESSION["csrf_vendor_delete"]);
            Flasher::setMessage("Vendor", "Deleted successfully", "success");
        } catch (Throwable $e) {
            Flasher::setMessage("Delete failed", $e->getMessage(), "danger");
        }
        header("location: " . BASEURL . "/Vendor");
        exit();
    }
}
