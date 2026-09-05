<?php
header('Content-Type: application/json');

// 1. Gather the Devices
$mdstat = @file_get_contents('/proc/mdstat');
$devices = [];
if ($mdstat) {
    // Scan for active RAID block and extract sd[a-z] patterns
    if (preg_match('/active\s+raid.*?$/im', $mdstat, $raidMatch)) {
        preg_match_all('/(sd[a-z])/', $raidMatch[0], $matches);
        if (!empty($matches[1])) {
            $devices = array_unique($matches[1]);
        }
    }
    // Fallback if active raid string isn't matched but sd[a-z] is present
    if (empty($devices)) {
        preg_match_all('/(sd[a-z])/', $mdstat, $matches);
        if (!empty($matches[1])) {
            $devices = array_unique($matches[1]);
        }
    }
}

// 2. Sort the Array
sort($devices);

// 3. Calculate RAID Capacity
$dfOutput = shell_exec('df -B1 /raid5');
$capacity = [
    'total' => 0,
    'used' => 0,
    'available' => 0
];
if ($dfOutput) {
    $lines = explode("\n", trim($dfOutput));
    // The second line typically contains the stats, though lines can wrap.
    // If output is two lines:
    // Filesystem           1B-blocks      Used Available Use% Mounted on
    // /dev/md0             100000000 500000000 500000000  50% /raid5
    $statLine = count($lines) > 1 ? $lines[count($lines) - 1] : '';
    if ($statLine) {
        $parts = preg_split('/\s+/', $statLine);
        // Sometimes long filesystem name pushes values to a new line, so we look from the end
        // [ ..., '1000000', '500000', '500000', '50%', '/raid5' ]
        $count = count($parts);
        if ($count >= 6) {
            $capacity['total'] = (int)$parts[$count - 5];
            $capacity['used'] = (int)$parts[$count - 4];
            $capacity['available'] = (int)$parts[$count - 3];
        } elseif ($count >= 5) {
             // Case where filesystem name is on previous line
            $capacity['total'] = (int)$parts[$count - 5];
            $capacity['used'] = (int)$parts[$count - 4];
            $capacity['available'] = (int)$parts[$count - 3];
        }
    }
}

// 4. Poll SMART Data
$smartData = [];
foreach ($devices as $device) {
    $smartOutput = shell_exec("sudo smartctl -H /dev/" . escapeshellarg($device));
    $passed = false;
    if ($smartOutput && strpos($smartOutput, 'SMART overall-health self-assessment test result: PASSED') !== false) {
        $passed = true;
    }
    $smartData[$device] = $passed;
}

echo json_encode([
    'devices' => $devices,
    'capacity' => $capacity,
    'smart' => $smartData
]);
