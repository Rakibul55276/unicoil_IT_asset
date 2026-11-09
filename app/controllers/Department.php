<?php

class Department extends Controller
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
            "Only an IT Manager or Administrator can manage departments.",
        );
    }

    public function index()
    {
        $data["title"] = "IT Asset - Maintain Department";
        $data["menu"] = "Department";
        $data["menu-dsc"] = "Maintain Department";
        $data["rdata"] = $this->model("Department_model")->getAllDept();
        $this->view("templates/header_a", $data);
        $this->view("department/department-list", $data);
        $this->view("templates/footer_a");
    }

    public function create()
    {
        $data["rdata"] = $this->model("Department_model")->getMaxID();
        $maxID = (string) ((int) $data["rdata"]["deptId"] + 1);

        $result = $this->model("Department_model")->saveData($maxID, $_POST);
        if ($result > 0) {
            Flasher::setMessage("New Department Successfully", "Created", "success");
            header("location: " . BASEURL . "/Department");
            exit();
        } elseif ($result === -1) {
            Flasher::setMessage("Failed,", "Department name already exists", "danger");
            header("location: " . BASEURL . "/Department/new/department");
            exit();
        } elseif ($result === -2) {
            Flasher::setMessage("Failed,", "Department prefix already exists", "danger");
            header("location: " . BASEURL . "/Department/new/department");
            exit();
        } elseif ($result === -4) {
            Flasher::setMessage("Failed,", "The section head must be an active employee of this department", "danger");
            header("location: " . BASEURL . "/Department");
            exit();
        } else {
            Flasher::setMessage("Failed,", "Check your input", "danger");
            header("location: " . BASEURL . "/Department");
            exit();
        }
    }

    public function new()
    {
        $data["title"] = "IT Asset - Maintain Department";
        $data["menu"] = "Department";
        $data["menu-dsc"] = "Maintain Department";
        $data["department"] = null;
        $data["employees"] = $this->model("Department_model")->getActiveEmployees();
        $data["post_url"] = BASEURL . "/Department/create";

        $this->view("templates/header_a", $data);
        $this->view("department/index", $data);
        $this->view("templates/footer_a");
    }

    public function edit($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can edit departments.",
        );
        $model = $this->model("Department_model");
        $data = [
            "title" => "IT Asset - Edit Department",
            "menu" => "Department",
            "menu-dsc" => "Edit Department",
            "department" => $model->getDeptByID($id),
            "employees" => $model->getActiveEmployees(),
            "post_url" => BASEURL . "/Department/update",
        ];
        if (!$data["department"]) {
            Flasher::setMessage("Department", "not found", "danger");
            header("location: " . BASEURL . "/Department");
            exit();
        }
        $this->view("templates/header_a", $data);
        $this->view("department/index", $data);
        $this->view("templates/footer_a");
    }

    public function update()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can update departments.",
        );
        $result =
            $_SERVER["REQUEST_METHOD"] === "POST"
                ? $this->model("Department_model")->updateData($_POST)
                : -1;
        if ($result >= 0) {
            Flasher::setMessage("Department", "Updated successfully", "success");
        } elseif ($result === -4) {
            Flasher::setMessage(
                "Update failed",
                "The section head must be an active employee of this department.",
                "danger",
            );
        } elseif ($result === -2) {
            Flasher::setMessage(
                "Update failed",
                "Department name or prefix already exists",
                "danger",
            );
        } else {
            Flasher::setMessage(
                "Department",
                "Update failed. Check the name and prefix format.",
                "danger",
            );
        }
        header("location: " . BASEURL . "/Department");
        exit();
    }

    public function hapusData($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete departments.",
        );
        $result = $this->model("Department_model")->deleteData($id);
        if (!empty($result["deleted"])) {
            Flasher::setMessage("Department", "Deleted successfully", "success");
        } else {
            Flasher::setMessage(
                "Department not deleted",
                (string) ($result["message"] ?? "The department is currently in use."),
                "danger",
            );
        }

        // Always return to the list; the previous failure path contained an
        // invalid space before Department and could lead to another bad URL.
        header("location: " . BASEURL . "/Department");
        exit();
    }
}
