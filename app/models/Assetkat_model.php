<?php

class Assetkat_model
{
    private $db;
    private $table = "assetkat";

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllAssetCat()
    {
        $this->db->query("SELECT * FROM " . $this->table . " ORDER BY Description");
        return $this->db->resultSet();
    }

    public function getAssetCatByID($id)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE assetKat=:assetKat");
        $this->db->bind("assetKat", $id);
        return $this->db->single();
    }

    public function saveData(array $data)
    {
        $description = trim((string) ($data["Description"] ?? ""));
        $prefix = $this->cleanPrefix($data["asset_prefix"] ?? "");
        if ($description === "") {
            throw new InvalidArgumentException("Category name is required.");
        }

        $this->db->query('INSERT INTO assetkat (assetKat,Description,asset_prefix,active)
            VALUES (:assetKat,:description,:prefix,1)');
        $this->db->bind("assetKat", $prefix);
        $this->db->bind("description", $description);
        $this->db->bind("prefix", $prefix);
        $this->db->executeOrFail();
        return $this->db->rowCount();
    }

    public function updateData(array $data)
    {
        $category = trim((string) ($data["assetKat"] ?? ""));
        $description = trim((string) ($data["Description"] ?? ""));
        $prefix = $this->cleanPrefix($data["asset_prefix"] ?? "");
        if ($category === "" || $description === "") {
            throw new InvalidArgumentException("Category name is required.");
        }

        $this->db->query(
            "UPDATE assetkat SET Description=:description,asset_prefix=:prefix WHERE assetKat=:assetKat",
        );
        $this->db->bind("assetKat", $category);
        $this->db->bind("description", $description);
        $this->db->bind("prefix", $prefix);
        $this->db->executeOrFail();
        return $this->db->rowCount();
    }

    public function deleteData($id)
    {
        $this->db->query("DELETE FROM " . $this->table . " WHERE assetKat=:assetKat");
        $this->db->bind("assetKat", $id);
        $this->db->executeOrFail();
        return $this->db->rowCount();
    }

    /** Return asset and request references that prevent category deletion. */
    public function getUsage($id): array
    {
        $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM assetdata a WHERE a.assetKat=:asset_category) AS asset_count,
                (SELECT COUNT(*) FROM asset_request_item r WHERE r.assetKat=:request_category) AS request_count",
        );
        $this->db->bind("asset_category", trim((string) $id));
        $this->db->bind("request_category", trim((string) $id));
        $usage = $this->db->single() ?: [];

        $this->db->query(
            "SELECT assetNum FROM assetdata
             WHERE assetKat=:asset_category
             ORDER BY assetNum
             LIMIT 10",
        );
        $this->db->bind("asset_category", trim((string) $id));
        $assetNumbers = array_map(
            static fn($row) => (string) $row["assetNum"],
            $this->db->resultSet(),
        );

        return [
            "assetCount" => (int) ($usage["asset_count"] ?? 0),
            "requestCount" => (int) ($usage["request_count"] ?? 0),
            "assetNumbers" => $assetNumbers,
        ];
    }

    private function cleanPrefix($value): string
    {
        $prefix = strtoupper(preg_replace("/[^A-Za-z0-9]/", "", trim((string) $value)));
        if (!preg_match('/^[A-Z][A-Z0-9]{1,5}$/', $prefix)) {
            throw new InvalidArgumentException(
                "Prefix must be 2 to 6 characters, start with a letter, and contain only letters or numbers.",
            );
        }
        return $prefix;
    }
}
