<?php
class Employee extends Controller
{
    private $empModel;
    private $deptModel;

    public function __construct()
    {
        // Ensure user is logged in
        if (!isset($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
        $this->requireRoles(
            defined("ISSUER_UI_ENABLED") && ISSUER_UI_ENABLED
                ? ["HR_REQUESTER", "ISSUER"]
                : ["HR_REQUESTER"],
            "Only HR or an Administrator can manage employee records.",
        );

        $this->empModel = $this->model("Employee_model");
        $this->deptModel = $this->model("Department_model");
    }

    // ======================
    // Default: Show Create Employee Form
    // ======================
    public function index()
    {
        $data = [
            "title" => "IT Asset - Maintain Employee Data",
            "menu" => "Employee",
            "menu-dsc" => "Maintain Employee Data",
            "post_url" => BASEURL . "/Employee/create",
            "departments" => $this->deptModel->getAllDept(),
            "departmentHeads" => $this->deptModel->getAllDepartmentHeads(),
            "supervisors" => $this->deptModel->getActiveEmployees(),
        ];

        $this->view("templates/header_a", $data);
        $this->view("employee/index", $data);
        $this->view("templates/footer_a");
    }

    // ======================
    // Dashboard
    // ======================
    public function dashboard()
    {
        header("location: " . BASEURL);
        exit();
    }

    // ======================
    // Employee List
    // ======================
    public function list()
    {
        $data = [
            "title" => "IT Asset - Employee Data",
            "menu" => "Employee",
            "menu-dsc" => "Employee Data",
            "rdata" => $this->empModel->getAllEmployeeWithAssets(),
            "departments" => $this->deptModel->getAllDept(),
            "departmentHeads" => $this->deptModel->getAllDepartmentHeads(),
            "supervisors" => $this->deptModel->getActiveEmployees(),
        ];

        $this->view("templates/header_a", $data);
        $this->view("employee/employee-list", $data);
        $this->view("templates/footer_a");
    }

    // ======================
    // Save New Employee
    // ======================
    public function create()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $result = $this->empModel->saveData($_POST);

            // If AJAX request
            if (
                !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest"
            ) {
                header("Content-Type: application/json");
                echo json_encode($result);
                exit();
            }

            // Fallback for normal submit
            if (isset($result["error"])) {
                Flasher::setMessage("Employee", $result["error"], "danger");
                header("location:" . BASEURL . "/Employee");
                exit();
            }
            Flasher::setMessage("Employee", "Created Successfully", "success");
            header("location:" . BASEURL . "/Employee/list");
            exit();
        }
    }

    public function update()
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can update employee records.",
        );
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $isSystemAdmin = $this->isSystemAdmin();
            $result = $this->empModel->updateData(
                $_POST,
                (string) ($_SESSION["usr"]["user"] ?? "admin"),
                $isSystemAdmin,
            );

            if (
                !empty($result["renamedUsername"]) &&
                (string) ($_SESSION["usr"]["user"] ?? "") ===
                    (string) ($result["previousUsername"] ?? "")
            ) {
                $_SESSION["usr"]["user"] = (string) $result["renamedUsername"];
            }

            if (
                !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest"
            ) {
                header("Content-Type: application/json");
                echo json_encode($result);
                exit();
            }

            if (isset($result["error"])) {
                Flasher::setMessage("Employee", $result["error"], "danger");
            } else {
                $message = "Updated Successfully";
                if (!empty($result["departmentChanged"])) {
                    $message .=
                        ". Pending requests and active assignments now follow the new department.";
                }
                if (!empty($result["employeeIdChanged"])) {
                    $message .= ". Employee ID and linked records were updated.";
                }
                Flasher::setMessage("Employee", $message, "success");
            }
            header("location:" . BASEURL . "/Employee/list");
            exit();
        }
    }

    // ======================
    // Edit Employee Form
    // ======================
    public function edit($empId)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can edit employee records.",
        );
        $emp = $this->empModel->getEmployeeByID($empId);

        if (!$emp) {
            Flasher::setMessage("Employee", "Employee not found", "danger");
            header("location:" . BASEURL . "/Employee/list");
            exit();
        }

        $data = [
            "title" => "IT Asset - Edit Employee",
            "menu" => "Employee",
            "menu-dsc" => "Edit Employee Data",
            "post_url" => BASEURL . "/Employee/update",
            "departments" => $this->deptModel->getAllDept(),
            "empData" => $emp,
            "canEditEmployeeId" => $this->isSystemAdmin(),
        ];

        $this->view("templates/header_a", $data);
        $this->view("employee/index", $data); // reuse form
        $this->view("templates/footer_a");
    }

    // ======================
    // Delete Employee
    // ======================
    public function hapusData($id)
    {
        $this->requireRoles(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete employee records.",
        );
        if ($this->empModel->deleteData($id) > 0) {
            Flasher::setMessage("Employee", "Deleted", "success");
        } else {
            Flasher::setMessage("Employee", "Delete Failed", "danger");
        }

        header("location:" . BASEURL . "/Employee/list");
        exit();
    }

    // ======================
    // Get Employee by ID (AJAX)
    // ======================
    public function getById($id)
    {
        header("Content-Type: application/json");

        $emp = $this->empModel->getEmployeeByID($id);

        if ($emp) {
            if ((int) $emp["aktif"] === 1) {
                echo json_encode([
                    "empId" => $emp["empId"],
                    "empName" => $emp["fullname"],
                    "aktif" => 1,
                ]);
            } else {
                http_response_code(403);
                echo json_encode([
                    "empId" => $emp["empId"],
                    "empName" => "",
                    "aktif" => 0,
                    "message" => "Employee is inactive",
                ]);
            }
        } else {
            http_response_code(404);
            echo json_encode([
                "empId" => $id,
                "empName" => "",
                "aktif" => 0,
                "message" => "Employee not found",
            ]);
        }
        exit();
    }

    public function details($id)
    {
        header("Content-Type: application/json; charset=UTF-8");
        if (!ctype_digit((string) $id)) {
            http_response_code(422);
            echo json_encode(["error" => "Invalid employee ID"]);
            exit();
        }
        $employee = $this->empModel->getEmployeeByID((int) $id);
        if (!$employee) {
            http_response_code(404);
            echo json_encode(["error" => "Employee not found"]);
            exit();
        }
        $employee["departmentName"] = $employee["department"] ?? "";
        echo json_encode(
            [
                "employee" => $employee,
                "transactions" => $this->empModel->getEmployeeAssetTransactions((int) $id),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
        exit();
    }

    /** Employee identifiers are sensitive master-data keys. */
    private function isSystemAdmin(): bool
    {
        $username = trim((string) ($_SESSION["usr"]["user"] ?? ""));
        if ($username === "" || strtolower($username) === "admin") {
            return strtolower($username) === "admin";
        }

        $account = $this->model("User_model")->getUserById($username);
        return is_array($account) &&
            (int) ($account["active"] ?? 0) === 1 &&
            in_array("ADMINISTRATOR", $account["roles"] ?? [], true);
    }
}
?>
