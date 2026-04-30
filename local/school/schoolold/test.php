<?php

require_once('../../config.php');
$stateCodes = [
    "Andhra Pradesh" => "AP",
    "Arunachal Pradesh" => "AR",
    "Assam" => "AS",
    "Bihar" => "BR",
    "Chhattisgarh" => "CG",
    "Goa" => "GA",
    "Gujarat" => "GJ",
    "Haryana" => "HR",
    "Himachal Pradesh" => "HP",
    "Jharkhand" => "JH",
    "Karnataka" => "KA",
    "Kerala" => "KL",
    "Madhya Pradesh" => "MP",
    "Maharashtra" => "MH",
    "Manipur" => "MN",
    "Meghalaya" => "ML",
    "Mizoram" => "MZ",
    "Nagaland" => "NL",
    "Odisha" => "OR",
    "Punjab" => "PB",
    "Rajasthan" => "RJ",
    "Sikkim" => "SK",
    "Tamil Nadu" => "TN",
    "Telangana" => "TS",
    "Tripura" => "TR",
    "Uttar Pradesh" => "UP",
    "Uttarakhand" => "UK",
    "West Bengal" => "WB",
    "Andaman and Nicobar Islands" => "AN",
    "Chandigarh" => "CH",
    "Dadra and Nagar Haveli and Daman and Diu" => "DN",
    "Lakshadweep" => "LD",
    "Delhi" => "DL",
    "Puducherry" => "PY",
    "Ladakh" => "LA",
    "Jammu and Kashmir" => "JK"
];

$stateName = 'Uttar Pradesh';
function getStateCode($stateName, $stateCodes) {
    // Debug: Print the input state name
    error_log("Input state name: " . $stateName);

    // Normalize input to title case
    $stateName = ucwords(strtolower($stateName)); 

    // Debug: Print the normalized state name
    error_log("Normalized state name: " . $stateName);

    if (array_key_exists($stateName, $stateCodes)) {
        return $stateCodes[$stateName];
    } else {
        return "State name not found.";
    }
}
$stateName = $DB->get_record_sql('SELECT state_name FROM {school} WHERE state_name = "Uttar Pradesh"');
// Check if the 'state' parameter is set in the query string
if (isset($_GET['state'])) {
    $stateName = $_GET['state'];

    // Debug: Print the received state parameter
    error_log("Received state parameter: " . $stateName);

    $stateCode = getStateCode($stateName, $stateCodes);

    // Return the result as JSON
    header('Content-Type: application/json');
    echo json_encode(["stateName" => $stateName, "stateCode" => $stateCode]);
} else {
    // Return an error message if the 'state' parameter is not set
    header('Content-Type: application/json');
    echo json_encode(["error" => "Please provide a state name using the 'state' query parameter."]);
}

?>

