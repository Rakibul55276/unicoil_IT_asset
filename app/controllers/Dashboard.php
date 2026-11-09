<?php
class Dashboard extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
    }

    public function index()
    {
        // Home builds the complete role-aware dashboard and page layout.
        header("location: " . BASEURL);
        exit();
    }

    // ✅ must be public to match parent Controller
}
