<?php
// ============================================================
// api.php — Canteen Meal Attendance API
// ============================================================

require_once 'config.php';

header("Content-Type: application/json; charset=UTF-8");
// SECURITY: Restrict to your domain in production.
// Replace '*' with your actual origin, e.g. 'https://canteen.yourdomain.com'
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// ============================================================
// HELPER: Forward request to Google Apps Script via cURL
// ============================================================
function callGAS($method, $params = [], $body = null) {
    $url = GAS_URL;
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false || $error) {
        return ['status' => 'error', 'message' => 'Upstream request failed: ' . $error];
    }
    return json_decode($response, true) ?? ['status' => 'error', 'message' => 'Invalid upstream response'];
}

// ============================================================
// HELPER: Sanitize string input
// ============================================================
function sanitize($val) {
    return htmlspecialchars(strip_tags(trim((string)$val)), ENT_QUOTES, 'UTF-8');
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// POST — log, register, update_employee, verify_pin
// ============================================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Malformed JSON body.']);
        exit();
    }

    $action = sanitize($data['action'] ?? '');

    // --- VERIFY PIN (handled in PHP — never reaches GAS) ---
    if ($action === 'verify_pin') {
        $pin = (string)($data['pin'] ?? '');
        if ($pin === ADMIN_PIN) {
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid PIN.']);
        }
        exit();
    }

    // --- LOG SCAN ---
    if ($action === 'log') {
        $empId    = sanitize($data['emp_id'] ?? '');
        $mealType = sanitize($data['meal_type'] ?? '');

        if (empty($empId)) {
            echo json_encode(['status' => 'error', 'message' => 'emp_id is required.']); exit();
        }
        if (!in_array($mealType, ['Breakfast', 'Lunch'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid meal_type.']); exit();
        }

        echo json_encode(callGAS('POST', [], [
            'action'          => 'log',
            'token'           => API_TOKEN,
            'emp_id'          => $empId,
            'meal_type'       => $mealType,
            'amount_consumed' => sanitize($data['amount_consumed'] ?? '0'),
            'amount_day'      => sanitize($data['amount_day'] ?? '120'),
        ]));

    // --- REGISTER ---
    } elseif ($action === 'register') {
        $empId = sanitize($data['emp_id'] ?? '');
        $name  = sanitize($data['name']   ?? '');
        $dept  = sanitize($data['dept']   ?? '');

        if (empty($empId) || empty($name) || empty($dept)) {
            echo json_encode(['status' => 'error', 'message' => 'emp_id, name, and dept are required.']); exit();
        }

        echo json_encode(callGAS('POST', [], [
            'action'        => 'register',
            'token'         => API_TOKEN,
            'emp_id'        => $empId,
            'name'          => $name,
            'dept'          => $dept,
            'qr_raw'        => sanitize($data['qr_raw'] ?? $empId),
            'emp_type'      => sanitize($data['emp_type'] ?? 'Regular'),
            'amount_day'    => sanitize($data['amount_day'] ?? '120'),
            'amount_month'  => sanitize($data['amount_month'] ?? '3120'),
        ]));

    // --- UPDATE EMPLOYEE ---
    } elseif ($action === 'update_employee') {
        $originalId = sanitize($data['original_id'] ?? '');
        $empId      = sanitize($data['emp_id']      ?? '');
        $name       = sanitize($data['name']        ?? '');

        if (empty($originalId) || empty($empId) || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'original_id, emp_id, and name are required.']); exit();
        }

        echo json_encode(callGAS('POST', [], [
            'action'        => 'update_employee',
            'token'         => API_TOKEN,
            'original_id'   => $originalId,
            'emp_id'        => $empId,
            'name'          => $name,
            'dept'          => sanitize($data['dept']         ?? ''),
            'meal_type'     => sanitize($data['meal_type']    ?? ''),
            'amount_day'    => sanitize($data['amount_day']   ?? ''),
            'amount_month'  => sanitize($data['amount_month'] ?? ''),
        ]));

    // --- DELETE EMPLOYEE ---
    } elseif ($action === 'delete_employee') {
        $empId = sanitize($data['emp_id'] ?? '');
        if (empty($empId)) {
            echo json_encode(['status' => 'error', 'message' => 'emp_id is required.']); exit();
        }
        echo json_encode(callGAS('POST', [], [
            'action' => 'delete_employee',
            'token'  => API_TOKEN,
            'emp_id' => $empId,
        ]));

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    }

// ============================================================
// GET — get (logs/stats), employees
// ============================================================
} elseif ($method === 'GET') {
    $action = sanitize($_GET['action'] ?? '');

    if ($action === 'get') {
        $range = sanitize($_GET['range'] ?? 'today');
        if (!in_array($range, ['today','week','month','all'], true)) $range = 'today';
        echo json_encode(callGAS('GET', ['action' => 'get', 'token' => API_TOKEN, 'range' => $range]));

    } elseif ($action === 'employees') {
        echo json_encode(callGAS('GET', ['action' => 'employees', 'token' => API_TOKEN]));

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    }

} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
