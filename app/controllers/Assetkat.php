<?php

class Assetkat extends Controller
{
    public function __construct()
    {
        if (isset($_SESSION["usr"])) {
        } else {
            header("location:" . BASEURL);
            exit();
        }
        $this->requireRoles(
            defined("ISSUER_UI_ENABLED") && ISSUER_UI_ENABLED
                ? ["IT_MANAGER", "ISSUER"]
                : ["IT_MANAGER"],
            "Only an IT Manager or Administrator can manage asset categories.",
        );
    }

    public function index()
    {
        if (empty($_SESSION["csrf_category_delete"])) {
            $_SESSION["csrf_category_delete"] = bin2hex(random_bytes(32));
        }
        $data["title"] = "IT Asset - Maintain Asset Category";
        $data["menu"] = "Asset Category";
        $data["menu-dsc"] = "Maintain Asset Category";
        $data["rdata"] = $this->model("Assetkat_model")->getAllAssetCat();
        $data["deleteCsrf"] = $_SESSION["csrf_category_delete"];
        $this->view("templates/header_a", $data);
        $this->view("assetkat/index", $data);
        $this->view("templates/footer_a");
    }

    public function add()
    {
        $data["title"] = "IT Asset - Create Asset Category";
        $data["menu"] = "Asset Category";
        $data["menu-dsc"] = "Create Asset Category";
        $data["category"] = null;
        $data["post_url"] = BASEURL . "/Assetkat/create";
        $this->view("templates/header_a", $data);
        $this->view("assetkat/add", $data);
        $this->view("templates/footer_a");
    }
    public function edit($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can edit asset categories.",
        );
        $m = $this->model("Assetkat_model");
        $data = [
            "title" => "IT Asset - Edit Asset Category",
            "menu" => "Asset Category",
            "menu-dsc" => "Edit Asset Category",
            "category" => $m->getAssetCatByID($id),
            "post_url" => BASEURL . "/Assetkat/update",
        ];
        if (!$data["category"]) {
            header("location: " . BASEURL . "/Assetkat");
            exit();
        }
        $this->view("templates/header_a", $data);
        $this->view("assetkat/add", $data);
        $this->view("templates/footer_a");
    }
    public function update()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can update asset categories.",
        );
        try {
            $this->model("Assetkat_model")->updateData($_POST);
            Flasher::setMessage("Asset Category", "Updated successfully", "success");
        } catch (Throwable $error) {
            Flasher::setMessage(
                "Asset Category update failed",
                $error->getCode() === "23000"
                    ? "The category name or prefix is already in use."
                    : $error->getMessage(),
                "danger",
            );
        }
        header("location: " . BASEURL . "/Assetkat");
        exit();
    }

    public function create()
    {
        try {
            $this->model("Assetkat_model")->saveData($_POST);
            Flasher::setMessage("Asset Category", "Created successfully", "success");
        } catch (Throwable $error) {
            Flasher::setMessage(
                "Asset Category create failed",
                $error->getCode() === "23000"
                    ? "The category name or prefix is already in use."
                    : $error->getMessage(),
                "danger",
            );
        }
        header("location: " . BASEURL . "/Assetkat");
        exit();
    }

    // public function list(){
    // 	$data['title'] = 'IT Asset - Maintain Asset Category';
    // 	$data['menu'] = 'Asset Category';
    // 	$data['menu-dsc'] = 'Maintain Asset Category';
    // 	$data['rdata'] = $this->model('Assetkat_model')->getAllEmployee();

    // 	$this->view('templates/header_a', $data);
    // 	$this->view('assetkat/asset-list', $data);
    // 	$this->view('templates/footer_a');
    // }

    public function hapusData($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete asset categories.",
        );
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            Flasher::setMessage(
                "Delete failed",
                "Use the Delete button on the Asset Category page.",
                "danger",
            );
            $this->redirectToIndex();
        }

        $token = (string) ($_POST["csrf_token"] ?? "");
        if (
            empty($_SESSION["csrf_category_delete"]) ||
            !hash_equals((string) $_SESSION["csrf_category_delete"], $token)
        ) {
            Flasher::setMessage("Delete failed", "Invalid form token.", "danger");
            $this->redirectToIndex();
        }

        try {
            $model = $this->model("Assetkat_model");
            $category = $model->getAssetCatByID($id);
            if (!$category) {
                Flasher::setMessage("Delete failed", "Asset category was not found.", "danger");
                $this->redirectToIndex();
            }

            $usage = $model->getUsage($id);
            $assetCount = (int) ($usage["assetCount"] ?? 0);
            $requestCount = (int) ($usage["requestCount"] ?? 0);
            if ($assetCount > 0 || $requestCount > 0) {
                $details = [];
                if ($assetCount > 0) {
                    $assetNumbers = $usage["assetNumbers"] ?? [];
                    $assetText = implode(", ", $assetNumbers);
                    $remaining = max(0, $assetCount - count($assetNumbers));
                    if ($remaining > 0) {
                        $assetText .= " and " . $remaining . " more";
                    }
                    $details[] = "Used by assets: " . $assetText . ".";
                }
                if ($requestCount > 0) {
                    $details[] =
                        "Also referenced by " .
                        number_format($requestCount) .
                        " equipment request item(s).";
                }
                Flasher::setMessage(
                    "Category not deleted",
                    implode(" ", $details) .
                        " Reassign or remove those references before deleting the category.",
                    "danger",
                );
                $this->redirectToIndex();
            }

            if ($model->deleteData($id) > 0) {
                unset($_SESSION["csrf_category_delete"]);
                Flasher::setMessage("Asset Category", "Deleted successfully", "success");
            } else {
                Flasher::setMessage("Delete failed", "Asset category was not found.", "danger");
            }
        } catch (Throwable $error) {
            Flasher::setMessage(
                "Category not deleted",
                $error->getCode() === "23000"
                    ? "This category is still referenced by asset or request records."
                    : "The category could not be deleted.",
                "danger",
            );
        }
        $this->redirectToIndex();
    }

    private function redirectToIndex(): void
    {
        header("location: " . BASEURL . "/Assetkat");
        exit();
    }
}
