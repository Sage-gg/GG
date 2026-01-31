<?php
// get_core2_cranes.php - Fetch available cranes from Core2 for dropdown
require_once 'db.php';

header('Content-Type: application/json');

try {
    // In a real scenario, you would fetch this from Core2's API
    // For now, we'll create a static list based on their crane_and_equipment table structure
    
    // Option 1: Hardcoded list (if Core2 doesn't have an API to list cranes)
    $cranes = [
        ['crane_id' => 1, 'crane_name' => 'Mobile Crane Alpha', 'serial_number' => 'MC-2024-001', 'crane_type' => 'Mobile Crane'],
        ['crane_id' => 2, 'crane_name' => 'Tower Crane Beta', 'serial_number' => 'TC-2024-002', 'crane_type' => 'Tower Crane'],
        ['crane_id' => 3, 'crane_name' => 'Overhead Crane Gamma', 'serial_number' => 'OC-2024-003', 'crane_type' => 'Overhead Crane'],
        ['crane_id' => 4, 'crane_name' => 'Gantry Crane Delta', 'serial_number' => 'GC-2024-004', 'crane_type' => 'Gantry Crane'],
        ['crane_id' => 5, 'crane_name' => 'Jib Crane Epsilon', 'serial_number' => 'JC-2024-005', 'crane_type' => 'Jib Crane']
    ];
    
    /* Option 2: Fetch from Core2 API (if they create one)
    $ch = curl_init('https://core2.cranecali-ms.com/api/list_cranes.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $cranes = json_decode($response, true);
    } else {
        $cranes = [];
    }
    */
    
    echo json_encode([
        'success' => true,
        'cranes' => $cranes
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'cranes' => []
    ]);
}
