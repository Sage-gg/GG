<?php
// send_to_core2.php - Forward budget data to Core2 system
require_once 'db.php';

header('Content-Type: application/json');

// Get the budget ID and crane ID from request
$budget_id = $_POST['budget_id'] ?? null;
$crane_id = $_POST['crane_id'] ?? null;

if (!$budget_id) {
    echo json_encode(['success' => false, 'message' => 'Budget ID is required']);
    exit;
}

if (!$crane_id) {
    echo json_encode(['success' => false, 'message' => 'Please select a crane']);
    exit;
}

try {
    // Fetch the budget record
    $stmt = $conn->prepare("SELECT * FROM budgets WHERE id = ?");
    $stmt->bind_param('i', $budget_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();

    if (!$budget) {
        echo json_encode(['success' => false, 'message' => 'Budget not found']);
        exit;
    }

    // Prepare data to send to Core2
    $dataToSend = [
        'asset_type' => 'Crane',
        'manufacturer' => $budget['department'] ?? '',
        'year_manufactured' => date('Y'),
        'status' => 'Active',
        'current_location' => $budget['cost_center'] ?? '',
        'assigned_project' => $budget['description'] ?? '',
        'operational_hours' => 0,
        'load_capacity' => 0,
        'last_inspection' => date('Y-m-d'),
        'next_maintenance' => date('Y-m-d', strtotime('+1 month')),
        'safety_certificate_valid_until' => date('Y-m-d', strtotime('+1 year')),
        'remarks' => 'Budget: ₱' . number_format($budget['amount_allocated'], 2) . ' (' . $budget['period'] . ') - ' . ($budget['approval_status'] ?? 'Pending'),
        'crane_id' => (int)$crane_id,
        'budget_frequency' => $budget['period'] ?? 'Monthly'
    ];

    // Send to Core2 API
    $ch = curl_init('https://core2.cranecali-ms.com/api/insert_asset.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataToSend));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing, remove in production
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        echo json_encode([
            'success' => true,
            'message' => 'Budget data sent to Core2 successfully!',
            'core2_response' => $responseData
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send to Core2 (HTTP ' . $httpCode . ')',
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $curlError
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
