<?php

class Agent extends Controller
{
    private $agent;
    public function __construct()
    {
        if (!defined("AGENT_ENABLED") || !AGENT_ENABLED) {
            http_response_code(404);
            exit("Not Found");
        }
        if (!isset($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
        $this->agent = $this->model("Agent_model");
        if (empty($_SESSION["csrf_agent"])) {
            $_SESSION["csrf_agent"] = bin2hex(random_bytes(32));
        }
    }
    public function index()
    {
        $q = substr(trim((string) ($_GET["q"] ?? "")), 0, 100);
        $status = strtoupper(trim((string) ($_GET["status"] ?? "")));
        $link = strtoupper(trim((string) ($_GET["link"] ?? "")));
        if (!in_array($status, ["", "CURRENT", "STALE", "OFFLINE"], true)) {
            $status = "";
        }
        if (!in_array($link, ["", "LINKED", "UNLINKED"], true)) {
            $link = "";
        }
        $data = [
            "title" => "Automatic PC Inventory",
            "menu" => "Automatic Inventory",
            "menu-dsc" => "Windows computers synchronized by the Agent",
            "agentPage" => true,
            "search" => $q,
            "status" => $status,
            "link" => $link,
            "summary" => [],
            "devices" => [],
            "loadError" => "",
            "csrfToken" => $_SESSION["csrf_agent"],
        ];
        try {
            $data["summary"] = $this->agent->summary();
            $data["devices"] = $this->agent->devices($q, $status, $link);
        } catch (Throwable $e) {
            error_log("Agent list failed: " . $e->getMessage());
            $data["loadError"] =
                "Agent data could not be loaded. Import the latest database/install.sql.";
        }
        $this->view("templates/header_a", $data);
        $this->view("agent/index", $data);
        $this->view("templates/footer_a");
    }
    public function device($id = 0)
    {
        $id = (int) $id;
        if ($id < 1) {
            http_response_code(400);
            exit("Invalid device ID.");
        }
        $search = substr(trim((string) ($_GET["software"] ?? "")), 0, 100);
        try {
            $device = $this->agent->device($id);
            if (!$device) {
                http_response_code(404);
                exit("Agent device not found.");
            }
            $data = [
                "title" => "Agent - " . $device["hostname"],
                "menu" => $device["hostname"],
                "menu-dsc" => "Automatic hardware and software inventory",
                "agentPage" => true,
                "device" => $device,
                "disks" => $this->agent->disks($id),
                "software" => $this->agent->software($id, $search),
                "syncHistory" => $this->agent->syncHistory($id),
                "assets" => $this->agent->assets(),
                "softwareSearch" => $search,
                "csrfToken" => $_SESSION["csrf_agent"],
            ];
        } catch (Throwable $e) {
            error_log("Agent device failed: " . $e->getMessage());
            Flasher::setMessage("Agent", "Device information could not be loaded.", "danger");
            header("location: " . BASEURL . "/Agent");
            exit();
        }
        $this->view("templates/header_a", $data);
        $this->view("agent/device", $data);
        $this->view("templates/footer_a");
    }
    public function link($id = 0)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        if (
            empty($_POST["csrf_token"]) ||
            !hash_equals($_SESSION["csrf_agent"], (string) $_POST["csrf_token"])
        ) {
            Flasher::setMessage("Agent", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Agent/device/" . (int) $id);
            exit();
        }
        $asset =
            ($_POST["action"] ?? "") === "unlink"
                ? ""
                : strtoupper(trim((string) ($_POST["assetNum"] ?? "")));
        try {
            if ($asset !== "" && !preg_match('/^[A-Z0-9_-]{1,30}$/', $asset)) {
                throw new InvalidArgumentException("Asset number format is invalid.");
            }
            $this->agent->linkAsset((int) $id, $asset === "" ? null : $asset);
            Flasher::setMessage(
                "Agent",
                $asset === "" ? "Asset link removed." : "Linked to manual asset " . $asset . ".",
                "success",
            );
        } catch (InvalidArgumentException $e) {
            Flasher::setMessage("Agent", $e->getMessage(), "danger");
        } catch (Throwable $e) {
            error_log("Agent link failed: " . $e->getMessage());
            Flasher::setMessage("Agent", "The asset link could not be updated.", "danger");
        }
        header("location: " . BASEURL . "/Agent/device/" . (int) $id);
        exit();
    }
    public function deleteSelected()
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        if (
            empty($_POST["csrf_token"]) ||
            !hash_equals($_SESSION["csrf_agent"], (string) $_POST["csrf_token"])
        ) {
            Flasher::setMessage("Agent", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Agent");
            exit();
        }
        $ids = array_values(
            array_unique(
                array_filter(array_map("intval", (array) ($_POST["device_ids"] ?? [])), function (
                    $id,
                ) {
                    return $id > 0;
                }),
            ),
        );
        if (!$ids) {
            Flasher::setMessage("Agent", "Select at least one device to delete.", "warning");
            header("location: " . BASEURL . "/Agent");
            exit();
        }
        if (count($ids) > 500) {
            Flasher::setMessage(
                "Agent",
                "A maximum of 500 devices can be deleted at one time.",
                "danger",
            );
            header("location: " . BASEURL . "/Agent");
            exit();
        }
        try {
            $deleted = $this->agent->deleteDevices($ids);
            Flasher::setMessage(
                "Agent",
                $deleted . " selected device" . ($deleted === 1 ? "" : "s") . " deleted.",
                "success",
            );
        } catch (Throwable $e) {
            error_log("Agent bulk delete failed: " . $e->getMessage());
            Flasher::setMessage("Agent", "The selected devices could not be deleted.", "danger");
        }
        header("location: " . BASEURL . "/Agent");
        exit();
    }
}
