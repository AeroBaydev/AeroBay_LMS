<?php
// webhook_listener.php

// Get the raw POST data
$rawData = file_get_contents("php://input");

// Log the raw POST data to a file (for debugging purposes)
file_put_contents('webhook_raw_log.txt', $rawData . "\n", FILE_APPEND);

// Decode the JSON data
$data = json_decode($rawData, true);

// Check if the data is valid JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo "Invalid JSON";
    exit;
}

// Handle the webhook event
// This is where you can add your custom logic based on the webhook payload
// For example, log the data to a file or process the event
file_put_contents('webhook_log.txt', print_r($data, true), FILE_APPEND);

// Respond to the webhook source
http_response_code(200); // OK
echo "Webhook received successfully\n";

// Optionally, you can send a custom text response
echo "Thank you for sending the webhook.\n";
?>
