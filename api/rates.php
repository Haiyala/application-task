<?php

// Log all API requests to debug.log for troubleshooting
// commented by MNhaiyala
file_put_contents(__DIR__.'/debug.log', date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " - Payload: " . file_get_contents('php://input') . "\n", FILE_APPEND);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

// Only allow POST requests
// commented by MNhaiyala
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed. Use POST."]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON payload"]);
    exit;
}

// ----------------------------------------------------------- Required fields Start---------------------------------------------------------------------
$required = ["Unit Name", "Arrival", "Departure", "Occupants", "Ages"];
foreach ($required as $r) {
    if (!array_key_exists($r, $data)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required field: $r"]);
        exit;
    }
}
// ----------------------------------------------------------- Required fields End---------------------------------------------------------------------

// Convert date from dd/mm/yyyy → yyyy-mm-dd
// commented by MNhaiyala
function convertDate($d) {
    $d = trim($d);
    $parts = preg_split('/[\/\-\.]/', $d);
    if (count($parts) !== 3) return false;
    [$day, $month, $year] = $parts;
    if (!checkdate((int)$month, (int)$day, (int)$year)) return false;
    return sprintf("%04d-%02d-%02d", $year, $month, $day);
}

// Map Unit Name to Unit Type ID
// commented by MNhaiyala
$unitMap = [
    "Unit A" => -2147483637,
    "Unit B" => -2147483456,
];
$unitName = $data["Unit Name"];
$unitTypeId = $unitMap[$unitName] ?? (is_numeric($unitName) ? (int)$unitName : -2147483637);

// Validate dates
// commented by MNhaiyala
$arrival = convertDate($data["Arrival"]);
$departure = convertDate($data["Departure"]);
if ($arrival === false || $departure === false) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid date format. Use dd/mm/yyyy"]);
    exit;
}

// Build Guests array from ages
// commented by MNhaiyala
$ages = is_array($data["Ages"]) ? $data["Ages"] : [];
$guests = [];
foreach ($ages as $a) {
    $ageInt = (int)$a;
    $group = ($ageInt >= 18) ? "Adult" : "Child";
    $guests[] = ["Age Group" => $group];
}
if (empty($guests)) {
    for ($i = 0; $i < max(1, (int)$data["Occupants"]); $i++) {
        $guests[] = ["Age Group" => "Adult"];
    }
}

// Prepare payload for remote API
// commented by MNhaiyala
$remotePayload = [
    "Unit Type ID" => $unitTypeId,
    "Arrival"      => $arrival,
    "Departure"    => $departure,
    "Guests"       => $guests
];

// Call remote API
// commented by MNhaiyala
$remoteUrl = "https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php";
$ch = curl_init($remoteUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($remotePayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        "error" => "Failed to connect to remote API",
        "details" => $curlErr
    ]);
    exit;
}

$remoteData = json_decode($response, true);

// Send final response back to frontend
// commented by MNhaiyala
http_response_code($httpStatus >= 200 && $httpStatus < 300 ? 200 : $httpStatus);
echo json_encode([
    "requested" => $remotePayload,
    "remote_status" => $httpStatus,
    "remote_response" => $remoteData ?? $response
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit; // ensures nothing else is output
