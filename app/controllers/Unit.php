<?php

class Unit extends Controller
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
            "Only an IT Manager or Administrator can manage asset units.",
        );
    }

    public function index()
    {
        $data["title"] = "IT Asset - Maintain Asset Unit";
        $data["menu"] = "Asset Unit";
        $data["menu-dsc"] = "Maintain Asset Unit";
        $data["rdata"] = $this->model("Unit_model")->getAllData();
        $roles = array_values(array_filter($_SESSION["usr"]["roles"] ?? []));
        $data["isAdministrator"] =
            strtolower(trim((string) ($_SESSION["usr"]["user"] ?? ""))) === "admin" ||
            in_array("ADMINISTRATOR", $roles, true);
        $this->view("templates/header_a", $data);
        $this->view("unit/index", $data);
        $this->view("templates/footer_a");
    }

    public function add()
    {
        $data["title"] = "IT Asset - Create Asset Unit";
        $data["menu"] = "Asset Unit";
        $data["menu-dsc"] = "Create Asset Unit";
        $data["unitData"] = null;
        $data["post_url"] = BASEURL . "/Unit/create";
        $this->view("templates/header_a", $data);
        $this->view("unit/add", $data);
        $this->view("templates/footer_a");
    }
    public function edit($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can edit asset units.",
        );
        $m = $this->model("Unit_model");
        $data = [
            "title" => "IT Asset - Edit Unit",
            "menu" => "Asset Unit",
            "menu-dsc" => "Edit Asset Unit",
            "unitData" => $m->getDataByID($id),
            "post_url" => BASEURL . "/Unit/update",
        ];
        if (!$data["unitData"]) {
            header("location: " . BASEURL . "/Unit");
            exit();
        }
        $this->view("templates/header_a", $data);
        $this->view("unit/add", $data);
        $this->view("templates/footer_a");
    }
    public function update()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can update asset units.",
        );
        try {
            $this->model("Unit_model")->updateData($_POST);
            Flasher::setMessage("Unit", "Updated successfully", "success");
        } catch (InvalidArgumentException | RuntimeException $error) {
            Flasher::setMessage("Unit not updated", $error->getMessage(), "danger");
        } catch (Throwable $error) {
            error_log("Unit update failed: " . $error->getMessage());
            Flasher::setMessage(
                "Unit not updated",
                "The unit could not be updated. Please verify the values and try again.",
                "danger",
            );
        }
        header("location: " . BASEURL . "/Unit");
        exit();
    }

    public function create()
    {
        // $data['rdata'] = $this->model('Unit_model')->getMaxID();
        // $maxID =  $data['rdata']['location'] + 1;

        // if($maxID == 1){
        // 	$maxID = '3000000000';
        // }

        if ($this->model("Unit_model")->saveData($_POST) > 0) {
            Flasher::setMessage("New Unit Successfully", "Created", "success");
            header("location: " . BASEURL . "/Unit");
            exit();
        } else {
            Flasher::setMessage("Failed,", "Check your input", "danger");
            header("location: " . BASEURL . "/Unit");
            exit();
        }
    }

    public function hapusData($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete asset units.",
        );
        if ($this->model("Unit_model")->deleteData($id) > 0) {
            Flasher::setMessage("Unit Successfully", "Deleted", "success");
            header("location: " . BASEURL . "/Unit");
            exit();
        } else {
            Flasher::setMessage("Error", "Delete Data", "danger");
            header("location: " . BASEURL . "/Unit");
            exit();
        }
    }
}
