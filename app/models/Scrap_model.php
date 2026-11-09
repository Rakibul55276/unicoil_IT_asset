<?php

class Scrap_model
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAll(): array
    {
        $this->db->query("SELECT sr.*,a.Description,a.serial_number,k.Description AS categoryName,
            requester.nama AS requestedByName,reviewer.nama AS reviewedByName
            FROM asset_scrap_request sr JOIN assetdata a ON a.assetNum=sr.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            JOIN users requester ON requester.username=sr.requested_by
            LEFT JOIN users reviewer ON reviewer.username=sr.reviewed_by
            ORDER BY sr.created_at DESC,sr.scrap_request_id DESC");
        return $this->db->resultSet();
    }

    public function getAvailableAssets(): array
    {
        $this->db->query("SELECT a.assetNum,a.Description,a.serial_number,k.Description AS categoryName,
            l.description AS locationName
            FROM assetdata a LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            LEFT JOIN location l ON l.location=a.location
            WHERE a.assetStat='IN_STOCK'
              AND NOT EXISTS (SELECT 1 FROM asset_scrap_request sr
                WHERE sr.assetNum=a.assetNum AND sr.status='PENDING_IT_APPROVAL')
            ORDER BY a.assetNum");
        return $this->db->resultSet();
    }

    public function searchAvailableAssets(string $term, int $limit = 30): array
    {
        $term = mb_substr(trim($term), 0, 100);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $limit = max(1, min($limit, 50));
        $this->db->query("SELECT
            a.assetNum,
            a.Description,
            a.serial_number,
            k.Description AS categoryName,
            l.description AS locationName
        FROM assetdata a
        LEFT JOIN assetkat k ON k.assetKat=a.assetKat
        LEFT JOIN location l ON l.location=a.location
        WHERE a.assetStat='IN_STOCK'
          AND (
              a.assetNum LIKE :scrapAssetSearch
              OR a.serial_number LIKE :scrapSerialSearch
              OR a.Description LIKE :scrapDescriptionSearch
          )
          AND NOT EXISTS (
              SELECT 1
              FROM asset_scrap_request sr
              WHERE sr.assetNum=a.assetNum
                AND sr.status='PENDING_IT_APPROVAL'
          )
        ORDER BY
          CASE WHEN a.assetNum=:scrapExactAsset THEN 0 ELSE 1 END,
          a.assetNum
        LIMIT :scrapAssetLimit");
        $this->db->bind("scrapAssetSearch", $term . "%");
        $this->db->bind("scrapSerialSearch", $term . "%");
        $this->db->bind("scrapDescriptionSearch", $term . "%");
        $this->db->bind("scrapExactAsset", $term);
        $this->db->bind("scrapAssetLimit", $limit);
        return $this->db->resultSet();
    }

    public function create(string $assetNum, string $reason, string $actor): int
    {
        $assetNum = trim($assetNum);
        $reason = trim($reason);
        if ($assetNum === "" || $reason === "") {
            throw new InvalidArgumentException("Select an asset and enter the scrap reason.");
        }
        $this->db->beginTransaction();
        try {
            $this->db->query("SELECT assetNum,assetStat FROM assetdata WHERE assetNum=:assetNum FOR UPDATE");
            $this->db->bind("assetNum", $assetNum);
            $asset = $this->db->single();
            if (!$asset || $asset["assetStat"] !== "IN_STOCK") {
                throw new RuntimeException("Only an in-stock asset can be submitted for scrap. Return assigned assets first.");
            }
            $this->db->query("SELECT 1 FROM asset_scrap_request
                WHERE assetNum=:assetNum AND status='PENDING_IT_APPROVAL'");
            $this->db->bind("assetNum", $assetNum);
            if ($this->db->single()) {
                throw new RuntimeException("This asset already has a pending scrap request.");
            }
            $requestNo = "SCR-" . date("Ymd-His") . "-" . random_int(100, 999);
            $this->db->query("INSERT INTO asset_scrap_request
                (scrap_request_no,assetNum,requested_by,reason)
                VALUES (:requestNo,:assetNum,:actor,:reason)");
            $this->db->bind("requestNo", $requestNo);
            $this->db->bind("assetNum", $assetNum);
            $this->db->bind("actor", $actor);
            $this->db->bind("reason", substr($reason, 0, 500));
            $this->db->executeOrFail();
            $requestId = (int) $this->db->lastInsertId();
            $this->addEvent($requestId, "REQUESTED", null, "PENDING_IT_APPROVAL", $actor, $reason);
            $this->db->commit();
            return $requestId;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function approve(int $requestId, string $comment, string $actor): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query("SELECT sr.assetNum,sr.status,a.assetStat
                FROM asset_scrap_request sr JOIN assetdata a ON a.assetNum=sr.assetNum
                WHERE sr.scrap_request_id=:requestId FOR UPDATE");
            $this->db->bind("requestId", $requestId);
            $request = $this->db->single();
            if (!$request || $request["status"] !== "PENDING_IT_APPROVAL") {
                throw new RuntimeException("This scrap request is no longer pending.");
            }
            if ($request["assetStat"] !== "IN_STOCK") {
                throw new RuntimeException("The asset is no longer in stock and cannot be scrapped.");
            }
            $this->db->query("UPDATE asset_scrap_request SET status='APPROVED',reviewed_by=:actor,
                reviewed_at=NOW(),review_comment=:comment WHERE scrap_request_id=:requestId");
            $this->db->bind("actor", $actor);
            $this->db->bind("comment", trim($comment) ?: null);
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            $this->db->query("UPDATE assetdata SET assetStat='SCRAPPED',empId=NULL,deptId=NULL
                WHERE assetNum=:assetNum AND assetStat='IN_STOCK'");
            $this->db->bind("assetNum", $request["assetNum"]);
            $this->db->executeOrFail();
            if ($this->db->rowCount() !== 1) {
                throw new RuntimeException("The asset status changed before approval.");
            }
            $this->addEvent($requestId, "IT_APPROVED", "PENDING_IT_APPROVAL", "APPROVED", $actor, $comment);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function reject(int $requestId, string $reason, string $actor): void
    {
        $reason = trim($reason);
        if ($reason === "") {
            throw new InvalidArgumentException("Enter a rejection reason.");
        }
        $this->db->beginTransaction();
        try {
            $this->db->query("UPDATE asset_scrap_request SET status='REJECTED',reviewed_by=:actor,
                reviewed_at=NOW(),review_comment=:reason
                WHERE scrap_request_id=:requestId AND status='PENDING_IT_APPROVAL'");
            $this->db->bind("actor", $actor);
            $this->db->bind("reason", substr($reason, 0, 500));
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            if ($this->db->rowCount() !== 1) {
                throw new RuntimeException("This scrap request is no longer pending.");
            }
            $this->addEvent($requestId, "IT_REJECTED", "PENDING_IT_APPROVAL", "REJECTED", $actor, $reason);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function addEvent(int $requestId, string $event, ?string $from, ?string $to,
        string $actor, ?string $remarks): void
    {
        $this->db->query("INSERT INTO asset_scrap_event
            (scrap_request_id,event_type,from_status,to_status,actor_username,remarks)
            VALUES (:requestId,:event,:fromStatus,:toStatus,:actor,:remarks)");
        $this->db->bind("requestId", $requestId);
        $this->db->bind("event", $event);
        $this->db->bind("fromStatus", $from);
        $this->db->bind("toStatus", $to);
        $this->db->bind("actor", $actor);
        $this->db->bind("remarks", trim((string) $remarks) ?: null);
        $this->db->executeOrFail();
    }
}
