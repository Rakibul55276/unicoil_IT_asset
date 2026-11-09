<?php
class Location extends Controller
{
    private $model;
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
            "Only an IT Manager or Administrator can manage locations.",
        );
        $this->model = $this->model("Location_model");
    }
    public function index()
    {
        $data = [
            "title" => "IT Asset - Locations",
            "menu" => "Asset Location",
            "menu-dsc" => "Maintain storage locations",
            "locations" => $this->model->getAllData(),
        ];
        $this->view("templates/header_a", $data);
        $this->view("location/index", $data);
        $this->view("templates/footer_a");
    }
    public function add()
    {
        $data = [
            "title" => "IT Asset - Add Location",
            "menu" => "Asset Location",
            "menu-dsc" => "Add storage location",
            "locationData" => null,
            "post_url" => BASEURL . "/Location/create",
        ];
        $this->view("templates/header_a", $data);
        $this->view("location/form", $data);
        $this->view("templates/footer_a");
    }
    public function create()
    {
        $last = $this->model->getMaxID();
        $id = (string) ((int) $last["location"] + 1);
        if ($this->model->saveData($id, $_POST) > 0) {
            Flasher::setMessage("Location", "Created successfully", "success");
        } else {
            Flasher::setMessage("Location", "Create failed", "danger");
        }
        header("location: " . BASEURL . "/Location");
        exit();
    }
    public function edit($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can edit locations.",
        );
        $row = $this->model->getDataByID($id);
        if (!$row) {
            header("location: " . BASEURL . "/Location");
            exit();
        }
        $data = [
            "title" => "IT Asset - Edit Location",
            "menu" => "Asset Location",
            "menu-dsc" => "Edit storage location",
            "locationData" => $row,
            "post_url" => BASEURL . "/Location/update",
        ];
        $this->view("templates/header_a", $data);
        $this->view("location/form", $data);
        $this->view("templates/footer_a");
    }
    public function update()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can update locations.",
        );
        if ($this->model->updateData($_POST) >= 0) {
            Flasher::setMessage("Location", "Updated successfully", "success");
        } else {
            Flasher::setMessage("Location", "Update failed", "danger");
        }
        header("location: " . BASEURL . "/Location");
        exit();
    }
    public function delete($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete locations.",
        );
        if ($this->model->deleteData($id) > 0) {
            Flasher::setMessage("Location", "Deleted successfully", "success");
        } else {
            Flasher::setMessage(
                "Location",
                "Cannot delete a location currently used by assets",
                "danger",
            );
        }
        header("location: " . BASEURL . "/Location");
        exit();
    }
}
