<?php
class Vendor_model
{
    private $db;
    public function __construct()
    {
        $this->db = new Database();
    }
    public function getAll($activeOnly = false)
    {
        $sql =
            "SELECT * FROM vendor" .
            ($activeOnly ? " WHERE active=1" : "") .
            " ORDER BY vendor_name";
        $this->db->query($sql);
        return $this->db->resultSet();
    }
    public function getById($id)
    {
        $this->db->query("SELECT * FROM vendor WHERE vendor_id=:id");
        $this->db->bind("id", (int) $id);
        return $this->db->single();
    }
    public function save($data)
    {
        $name = trim($data["vendorName"] ?? "");
        if ($name === "") {
            throw new InvalidArgumentException("Vendor name is required.");
        }
        $email = trim($data["email"] ?? "");
        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Vendor email is invalid.");
        }
        $this->db->query(
            "INSERT INTO vendor(vendor_name,contact_person,email,phone,address,tax_number,active) VALUES(:name,:contact,:email,:phone,:address,:tax,:active)",
        );
        $this->bind($data, $name, $email);
        $this->db->executeOrFail();
        return $this->db->rowCount();
    }
    public function update($data)
    {
        $name = trim($data["vendorName"] ?? "");
        if ($name === "") {
            throw new InvalidArgumentException("Vendor name is required.");
        }
        $email = trim($data["email"] ?? "");
        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Vendor email is invalid.");
        }
        $this->db->query(
            "UPDATE vendor SET vendor_name=:name,contact_person=:contact,email=:email,phone=:phone,address=:address,tax_number=:tax,active=:active WHERE vendor_id=:id",
        );
        $this->bind($data, $name, $email);
        $this->db->bind("id", (int) $data["vendorId"]);
        $this->db->executeOrFail();
        return $this->db->rowCount();
    }
    public function delete($id)
    {
        $id = (int) $id;
        $vendor = $this->getById($id);
        if (!$vendor) {
            throw new RuntimeException("Vendor not found.");
        }
        $this->db->query("SELECT COUNT(*) total FROM asset_receipt WHERE vendor_id=:id");
        $this->db->bind("id", $id);
        $usage = $this->db->single();
        if ((int) ($usage["total"] ?? 0) > 0) {
            throw new RuntimeException(
                "Vendor is used by asset receipts and cannot be deleted. Mark it inactive instead.",
            );
        }
        $this->db->query("DELETE FROM vendor WHERE vendor_id=:id");
        $this->db->bind("id", $id);
        $this->db->executeOrFail();
        if ($this->db->rowCount() !== 1) {
            throw new RuntimeException("Vendor could not be deleted.");
        }
        return true;
    }
    private function bind($data, $name, $email)
    {
        foreach (
            [
                "name" => $name,
                "contact" => trim($data["contactPerson"] ?? "") ?: null,
                "email" => $email ?: null,
                "phone" => trim($data["phone"] ?? "") ?: null,
                "address" => trim($data["address"] ?? "") ?: null,
                "tax" => trim($data["taxNumber"] ?? "") ?: null,
                "active" => isset($data["active"]) ? 1 : 0,
            ]
            as $key => $value
        ) {
            $this->db->bind($key, $value);
        }
    }
}
