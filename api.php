<?php
// CORS Headers for secure API access
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========================================================
// CONFIGURATION
// ========================================================
// Paste your Google Apps Script Web App URL below:
$google_apps_script_url = "https://script.google.com/macros/s/AKfycbyVDo8_JxFKB40xxy6LKITod4gV80Dmp1B-QsQOcInVWQw7LjnNC73Jd9YmJo4W6xiLRw/exec";

// Optional MySQL Configuration (Uncomment to enable local database backups)
/*
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "canteen_db";
*/

// ========================================================
// HANDLE REQUESTS
// ========================================================
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST request (Logging a new scan)
if ($method === 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (isset($data['action']) && $data['action'] === 'log') {
        
        // 1. Forward to Google Sheets via cURL
        $ch = curl_init($google_apps_script_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input); // Forward exact JSON payload
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Apps script returns 302 redirect on POST
        $response = curl_exec($ch);
        curl_close($ch);

        // 2. Optional: Save backup to local MySQL Database
        /*
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO attendance_logs (emp_id, meal_type, scanned_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $data['emp_id'], $data['meal_type']);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
        */

        echo $response; // Return GAS response to frontend
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action."]);
    }
} 
// Handle GET request (Fetching Dashboard Stats)
else if ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get') {
        // Fetch from Google Apps Script
        $ch = curl_init($google_apps_script_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action."]);
    }
}
?>
