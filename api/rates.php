<?php

// log all the activitis in the program so that it shows the errros that the sysm will have
file_put_contents(__DIR__.'/debug.log', date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " - Payload: " . file_get_contents('php://input') . "\n", FILE_APPEND);
 
// api/rates.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");


//Here I will check and make sure only post is allowd
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


//------------------------------------------ Date converter dd/mm/yyyy → yyyy-mm-dd start-----------------------------------------------------------
function convertDate($d) {
    $d = trim($d);
    $parts = preg_split('/[\/\-\.]/', $d);
    if (count($parts) !== 3) return false;
    [$day, $month, $year] = $parts;
    if (!checkdate((int)$month, (int)$day, (int)$year)) return false;
    return sprintf("%04d-%02d-%02d", $year, $month, $day);
}
//------------------------------------------ Date converter dd/mm/yyyy → yyyy-mm-dd end-----------------------------------------------------------

// ---------------------------------------------------------Map Unit Name to Unit Type ID Start---------------------------------------------------------
$unitMap = [
    "Unit A" => -2147483637,
    "Unit B" => -2147483456,
];

$unitName = $data["Unit Name"];
$unitTypeId = $unitMap[$unitName] ?? (is_numeric($unitName) ? (int)$unitName : -2147483637);
// ---------------------------------------------------------Map Unit Name to Unit Type ID Start---------------------------------------------------------


// ---------------------------------------------------Validate dates Start---------------------------------------------------
$arrival = convertDate($data["Arrival"]);
$departure = convertDate($data["Departure"]);
if ($arrival === false || $departure === false) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid date format. Use dd/mm/yyyy"]);
    exit;
}
// ---------------------------------------------------Validate dates End---------------------------------------------------

//-------------------------------------------------- Build Guests array Start--------------------------------------------------
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
//-------------------------------------------------- Build Guests array End--------------------------------------------------

//Pass this data to the Rate.php so that it return the information
// Remote payload
$remotePayload = [
    "Unit Type ID" => $unitTypeId,
    "Arrival"      => $arrival,
    "Departure"    => $departure,
    "Guests"       => $guests
];

// Call remote API  // https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php
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

// Final response
http_response_code($httpStatus >= 200 && $httpStatus < 300 ? 200 : $httpStatus);
echo json_encode([
    "requested" => $remotePayload,
    "remote_status" => $httpStatus,
    "remote_response" => $remoteData ?? $response
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit; // optional, ensures nothing else is output
