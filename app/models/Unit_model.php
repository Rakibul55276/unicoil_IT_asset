<?php
/**
 *
 */
class unit_model
{
    private $db;
    private $table = "unit";

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllData()
    {
        $this->db->query("SELECT * FROM " . $this->table);
        return $this->db->resultSet();
    }

    public function getDataByID($id)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE unit=:unit");
        $this->db->bind("unit", $id);
        return $this->db->single();
    }

    public function saveData($data)
    {
        $query = "INSERT INTO unit (unit, description) VALUES(:unit, :description)";
        $this->db->query($query);
        $this->db->bind("unit", $data["unit"]);
        $this->db->bind("description", $data["Description"]);
        $this->db->execute();

        return $this->db->rowCount();
    }

    public function updateData($data)
    {
        $originalUnit = trim((string) ($data["original_unit"] ?? ""));
        $unit = trim((string) ($data["unit"] ?? ""));
        $description = trim((string) ($data["Description"] ?? ""));

        if ($originalUnit === "" || $unit === "" || $description === "") {
            throw new InvalidArgumentException("Unit code and description are required.");
        }
        if (strlen($unit) > 10) {
            throw new InvalidArgumentException("Unit code cannot exceed 10 characters.");
        }
        if (strlen($description) > 50) {
            throw new InvalidArgumentException("Unit description cannot exceed 50 characters.");
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "SELECT unit FROM unit WHERE unit=:originalUnit FOR UPDATE",
            );
            $this->db->bind("originalUnit", $originalUnit);
            if (!$this->db->single()) {
                throw new RuntimeException("The selected unit no longer exists.");
            }

            if ($unit !== $originalUnit) {
                $this->db->query(
                    "SELECT unit FROM unit WHERE unit=:unit LIMIT 1",
                );
                $this->db->bind("unit", $unit);
                if ($this->db->single()) {
                    throw new InvalidArgumentException(
                        "Another unit already uses the code " . $unit . ".",
                    );
                }

                // Create the replacement code first so installations with an
                // assetdata-to-unit foreign key can safely move references.
                $this->db->query(
                    "INSERT INTO unit (unit,description) VALUES (:unit,:description)",
                );
                $this->db->bind("unit", $unit);
                $this->db->bind("description", $description);
                $this->db->execute();

                $this->db->query(
                    "UPDATE assetdata SET unit=:unit WHERE unit=:originalUnit",
                );
                $this->db->bind("unit", $unit);
                $this->db->bind("originalUnit", $originalUnit);
                $this->db->execute();

                $this->db->query("DELETE FROM unit WHERE unit=:originalUnit");
                $this->db->bind("originalUnit", $originalUnit);
                $this->db->execute();
            } else {
                $this->db->query(
                    "UPDATE unit SET description=:description WHERE unit=:unit",
                );
                $this->db->bind("unit", $unit);
                $this->db->bind("description", $description);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function deleteData($id)
    {
        $this->db->query("DELETE FROM " . $this->table . " WHERE unit=:unit");
        $this->db->bind("unit", $id);
        $this->db->execute();

        return $this->db->rowCount();
    }

    public function getMaxID()
    {
        $this->db->query("SELECT MAX(unit) as unit FROM " . $this->table);
        return $this->db->single();
    }
}
