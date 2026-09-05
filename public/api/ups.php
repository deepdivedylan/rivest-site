<?php
// Enforce JSON content type
header('Content-Type: application/json');

// Query the local NUT daemon. Suppress stderr so it doesn't break the JSON on failure.
$command = "upsc cyberpower@localhost 2>/dev/null";
$output = shell_exec($command);

// Handle connection failures gracefully
if ($output === null || trim($output) === '') {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to communicate with the NUT daemon.'
    ]);
    exit;
}

// Parse the raw NUT output into an associative array
$lines = explode("\n", trim($output));
$upsData = [];

foreach ($lines as $line) {
    if (strpos($line, ':') !== false) {
        list($key, $value) = explode(':', $line, 2);
        $upsData[trim($key)] = trim($value);
    }
}

// Package the data into a structured JSON response
$response = [
    'status' => 'success',
    'timestamp' => date('c'),
    'data' => [
        'identity' => [
            'manufacturer' => $upsData['device.mfr'] ?? 'Unknown',
	    'model' => $upsData['device.model'] ?? 'Unknown',
	    'serial' => $upsData['device.serial'] ?? 'Unknown'
        ],
        'battery' => [
            'charge_percent' => isset($upsData['battery.charge']) ? (int)$upsData['battery.charge'] : null,
            'runtime_seconds' => isset($upsData['battery.runtime']) ? (int)$upsData['battery.runtime'] : null,
            'voltage' => isset($upsData['battery.voltage']) ? (float)$upsData['battery.voltage'] : null
        ],
        'grid' => [
            'input_voltage' => isset($upsData['input.voltage']) ? (float)$upsData['input.voltage'] : null,
            'output_voltage' => isset($upsData['output.voltage']) ? (float)$upsData['output.voltage'] : null
        ],
        'load' => [
            'status' => $upsData['ups.status'] ?? null,
            'load_percent' => isset($upsData['ups.load']) ? (int)$upsData['ups.load'] : null,
            'realpower_watts' => isset($upsData['ups.realpower']) ? (int)$upsData['ups.realpower'] : null
        ]
    ]
];

echo json_encode($response);
