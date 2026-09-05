<?php
header('Content-Type: application/json');

echo json_encode([
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown Station'
]);
