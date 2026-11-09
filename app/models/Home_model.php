<?php

class Home_model
{
    private $db;
    private $table = "users";

    public function __construct()
    {
        $this->db = new Database();
    }

    public function searchBookMark($data)
    {
        $this->db->query("CALL sp_getBookMark(:search)");
        $this->db->bind("search", trim((string) ($data["search"] ?? "")));
        return $this->db->resultSet();
    }

    public function register($data)
    {
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

            $query = "INSERT INTO users (username, email, nama, password, active, registeredOn) 
					  VALUES(:username, :email, :nama, :password, :active, :registeredOn)";
            $this->db->query($query);
            $this->db->bind("username", $data["username"]);
            $this->db->bind("email", $data["email"]);
            $this->db->bind("nama", $data["nama"]);
            $this->db->bind("password", $password);
            $this->db->bind("active", 1);
            $this->db->bind("registeredOn", "2019-02-01");
            $this->db->execute();

            return $this->db->rowCount();
        }
    }

    public function login($data)
    {
        $username = trim((string) ($data["username"] ?? ""));
        $password = (string) ($data["password"] ?? "");
        if ($username === "" || $password === "") {
            return "X";
        }

        $this->db->query(
            "SELECT * FROM " . $this->table . " WHERE username=:username AND active=1",
        );
        $this->db->bind("username", $username);
        $usrdata = $this->db->single();

        if (!$usrdata) {
            return "X";
        }

        return password_verify($password, $usrdata["password"]) ? $usrdata : false;
    }
}
