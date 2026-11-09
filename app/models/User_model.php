<?php

class User_model
{
    private $db;
    private $table = "users";

    public function __construct()
    {
        $this->db = new Database();
    }

    public function userList()
    {
        $this->db->query("SELECT u.username,u.email,u.nama,u.empId,u.active,u.registeredOn,
            e.fullname AS employeeName,GROUP_CONCAT(ur.role_code ORDER BY ur.role_code) AS roles
            FROM users u LEFT JOIN employee e ON e.empId=u.empId
            LEFT JOIN user_role ur ON ur.username=u.username
            GROUP BY u.username,u.email,u.nama,u.empId,u.active,u.registeredOn,e.fullname
            ORDER BY u.nama");
        return $this->db->resultSet();
    }

    public function getActiveEmployees()
    {
        $this->db->query("SELECT e.empId,e.fullname,e.email,d.deptName,
            u.username AS linkedUsername
            FROM employee e
            LEFT JOIN department d ON d.deptId=e.deptId
            LEFT JOIN users u ON u.empId=e.empId
            WHERE e.aktif=1
            ORDER BY e.fullname");
        return $this->db->resultSet();
    }

    public function register($data)
    {
        $email = $this->resolveAccountEmail($data);
        $this->assertEmployeeHasSingleUser((int) ($data["empId"] ?? 0));
        $this->db->query("SELECT * FROM " . $this->table . " WHERE username=:username");
        $this->db->bind("username", $data["username"]);
        $this->db->execute();
        $result = $this->db->rowCount();

        if ($result > 0) {
            return "X";
        } else {
            $options = [
                "cost" => 12,
            ];
            $password = password_hash($data["password"], PASSWORD_BCRYPT, $options);

            $this->db->beginTransaction();
            try {
                $query = "INSERT INTO users (username, email, nama, empId, password, active, registeredOn)
                    VALUES(:username, :email, :nama, :empId, :password, :active, :registeredOn)";
                $this->db->query($query);
                $this->db->bind("username", trim($data["username"]));
                $this->db->bind("email", $email);
                $this->db->bind("nama", trim($data["nama"]));
                $this->db->bind("empId", !empty($data["empId"]) ? (int) $data["empId"] : null);
                $this->db->bind("password", $password);
                $this->db->bind("active", 1);
                $this->db->bind("registeredOn", date("Y-m-d"));
                $this->db->executeOrFail();
                $this->saveMissingEmployeeEmail(
                    (int) ($data["empId"] ?? 0),
                    $email,
                );
                $this->replaceRoles(trim($data["username"]), $data["roles"] ?? []);
                $this->db->commit();
                return 1;
            } catch (Throwable $error) {
                $this->db->rollBack();
                throw $error;
            }
        }
    }

    public function updateData($data)
    {
        $username = trim($data["username"] ?? "");
        $email = $this->resolveAccountEmail($data);
        $this->assertEmployeeHasSingleUser((int) ($data["empId"] ?? 0), $username);
        $name = trim($data["nama"] ?? "");
        $password = (string) ($data["password"] ?? "");
        $confirmation = (string) ($data["passwordConfirmation"] ?? "");
        if ($username === "" || $name === "" || $email === "") {
            throw new InvalidArgumentException("Username, name, and email are required.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Enter a valid email address.");
        }
        if ($password !== "" && strlen($password) < 12) {
            throw new InvalidArgumentException("New password must contain at least 12 characters.");
        }
        if ($password !== "" && !hash_equals($password, $confirmation)) {
            throw new InvalidArgumentException("New password and confirmation do not match.");
        }
        if (!$this->getUserById($username)) {
            throw new RuntimeException("User account was not found.");
        }
        $query =
            "UPDATE users SET email=:email,nama=:nama,empId=:empId,active=:active" .
            (!empty($data["password"]) ? ",password=:password" : "") .
            " WHERE username=:username";
        $this->db->query($query);
        $this->db->bind("username", $username);
        $this->db->bind("email", $email);
        $this->db->bind("nama", $name);
        $this->db->bind("empId", !empty($data["empId"]) ? (int) $data["empId"] : null);
        $this->db->bind("active", isset($data["active"]) ? 1 : 0);
        if ($password !== "") {
            $this->db->bind("password", password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]));
        }
        $this->db->executeOrFail();
        $this->saveMissingEmployeeEmail(
            (int) ($data["empId"] ?? 0),
            $email,
        );
        $this->replaceRoles($username, $data["roles"] ?? []);
        return true;
    }
    public function getUserById($id)
    {
        $this->db->query("SELECT u.username,u.email,u.nama,u.empId,u.active,
            e.position AS employeePosition,
            COALESCE(d.deptName,e.department) AS employeeDepartment,
            GROUP_CONCAT(ur.role_code ORDER BY ur.role_code) AS roleList
            FROM users u
            LEFT JOIN employee e ON e.empId=u.empId
            LEFT JOIN department d ON d.deptId=e.deptId
            LEFT JOIN user_role ur ON ur.username=u.username
            WHERE u.username=:username
            GROUP BY u.username,u.email,u.nama,u.empId,u.active,
                     e.position,e.department,d.deptName");
        $this->db->bind("username", $id);
        $user = $this->db->single();
        if ($user) {
            $user["roles"] = array_values(array_filter(explode(",", (string) ($user["roleList"] ?? ""))));
        }
        return $user;
    }

    public function updateProfile($username, $data)
    {
        $username = trim((string) $username);
        $email = trim((string) ($data["email"] ?? ""));
        $name = trim((string) ($data["nama"] ?? ""));
        $currentPassword = (string) ($data["currentPassword"] ?? "");
        $password = (string) ($data["password"] ?? "");
        $confirmation = (string) ($data["passwordConfirmation"] ?? "");

        if ($username === "" || $name === "" || $email === "") {
            throw new InvalidArgumentException("Name and email are required.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Enter a valid email address.");
        }
        if ($password !== "" && strlen($password) < 12) {
            throw new InvalidArgumentException("New password must contain at least 12 characters.");
        }
        if ($password !== "" && !hash_equals($password, $confirmation)) {
            throw new InvalidArgumentException("New password and confirmation do not match.");
        }

        $this->db->query("SELECT password FROM users WHERE username=:username");
        $this->db->bind("username", $username);
        $credentials = $this->db->single();
        if (!$credentials) {
            throw new RuntimeException("Your user account was not found.");
        }
        if (
            $password !== "" &&
            ($currentPassword === "" ||
                !password_verify($currentPassword, $credentials["password"]))
        ) {
            throw new InvalidArgumentException(
                "Enter your correct current password before setting a new password.",
            );
        }

        $query =
            "UPDATE users SET email=:email,nama=:nama" .
            ($password !== "" ? ",password=:password" : "") .
            " WHERE username=:username";
        $this->db->query($query);
        $this->db->bind("username", $username);
        $this->db->bind("email", $email);
        $this->db->bind("nama", $name);
        if ($password !== "") {
            $this->db->bind("password", password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]));
        }
        $this->db->executeOrFail();

        return $this->getUserById($username);
    }

    public function deleteData($id)
    {
        $this->db->query("DELETE FROM " . $this->table . " WHERE username=:username");
        $this->db->bind("username", $id);
        $this->db->execute();

        return $this->db->rowCount();
    }

    private function replaceRoles(string $username, $roles): void
    {
        $allowed = [
            "ADMINISTRATOR",
            "HR_REQUESTER",
            "DEPARTMENT_MANAGER",
            "IT_MANAGER",
            "ISSUER",
        ];
        $roles = is_array($roles) ? array_values(array_intersect($allowed, $roles)) : [];
        $this->db->query("DELETE FROM user_role WHERE username=:username");
        $this->db->bind("username", $username);
        $this->db->executeOrFail();
        foreach ($roles as $role) {
            $this->db->query("INSERT INTO user_role (username,role_code,assigned_by)
                VALUES (:username,:role,:assignedBy)");
            $this->db->bind("username", $username);
            $this->db->bind("role", $role);
            $this->db->bind("assignedBy", $_SESSION["usr"]["user"] ?? $username);
            $this->db->executeOrFail();
        }
    }

    /** Use the employee master email whenever an account is employee-linked. */
    private function resolveAccountEmail(array $data): string
    {
        $employeeId = (int) ($data["empId"] ?? 0);
        if ($employeeId > 0) {
            $this->db->query("SELECT email FROM employee WHERE empId=:empId AND aktif=1");
            $this->db->bind("empId", $employeeId);
            $employee = $this->db->single();
            if (!$employee) {
                throw new InvalidArgumentException("Select an active linked employee.");
            }
            $email = trim((string) ($employee["email"] ?? ""));
            if ($email === "") {
                // Older employee imports may not contain email. Allow a one-time
                // account email until the employee master record is completed.
                $email = trim((string) ($data["email"] ?? ""));
            }
        } else {
            $email = trim((string) ($data["email"] ?? ""));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Enter a valid email address.");
        }
        return $email;
    }

    /** Prevent one employee from being linked to multiple login accounts. */
    private function assertEmployeeHasSingleUser(
        int $employeeId,
        string $excludedUsername = "",
    ): void {
        if ($employeeId <= 0) {
            return;
        }

        $query = "SELECT username FROM users WHERE empId=:empId";
        if ($excludedUsername !== "") {
            $query .= " AND username<>:excludedUsername";
        }
        $query .= " LIMIT 1";

        $this->db->query($query);
        $this->db->bind("empId", $employeeId);
        if ($excludedUsername !== "") {
            $this->db->bind("excludedUsername", $excludedUsername);
        }
        $linkedUser = $this->db->single();
        if ($linkedUser) {
            throw new InvalidArgumentException(
                "This employee is already linked to user ID " .
                    $linkedUser["username"] .
                    ". Each employee can have only one user ID.",
            );
        }
    }

    /**
     * When an old employee record has no email, retain the address entered while
     * maintaining the user so later forms can load it from the employee master.
     */
    private function saveMissingEmployeeEmail(int $employeeId, string $email): void
    {
        if ($employeeId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->db->query("UPDATE employee
            SET email=:email
            WHERE empId=:empId
              AND (email IS NULL OR TRIM(email)='')");
        $this->db->bind("email", $email);
        $this->db->bind("empId", $employeeId);
        $this->db->executeOrFail();
    }
}
