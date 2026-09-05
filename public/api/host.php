<?php
header('Content-Type: application/json');

// 1. System Load
$loadavg = @file_get_contents('/proc/loadavg');
$load = [0, 0, 0];
if ($loadavg) {
    $parts = explode(' ', trim($loadavg));
    if (count($parts) >= 3) {
        $load = [(float)$parts[0], (float)$parts[1], (float)$parts[2]];
    }
}

// 2. Memory Allocation
$freeOutput = shell_exec('free -b');
$memory = [
    'total' => 0,
    'used' => 0,
    'cached' => 0
];
if ($freeOutput) {
    $lines = explode("\n", trim($freeOutput));
    foreach ($lines as $line) {
        if (strpos($line, 'Mem:') === 0) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 6) {
                $memory['total'] = (int)$parts[1];
                $memory['used'] = (int)$parts[2];
                // In 'free' output, buff/cache is usually the 6th column (index 5)
                $memory['cached'] = (int)$parts[5];
            }
        }
    }
}

// 3. Thermal Monitoring
$sensorsOutput = shell_exec('sensors');
$temperature = null;
if ($sensorsOutput) {
    // Look for a line containing 'Package', 'Core', 'Tdie', or 'CPU' followed by a temperature reading
    if (preg_match('/Package[^:]*:\s*\+([0-9\.]+)/i', $sensorsOutput, $matches)) {
        $temperature = (float)$matches[1];
    } elseif (preg_match('/(?:Tdie|Tctl|CPU|Core 0)[^:]*:\s*\+([0-9\.]+)/i', $sensorsOutput, $matches)) {
        $temperature = (float)$matches[1];
    } elseif (preg_match('/temp1:\s*\+([0-9\.]+)/i', $sensorsOutput, $matches)) {
        $temperature = (float)$matches[1];
    }
}

// 4. System Uptime
$uptimeStr = @file_get_contents('/proc/uptime');
$uptime = 0;
if ($uptimeStr) {
    $parts = explode(' ', trim($uptimeStr));
    $uptime = (float)$parts[0];
}

echo json_encode([
    'load' => $load,
    'memory' => $memory,
    'temperature' => $temperature,
    'uptime' => $uptime
]);
