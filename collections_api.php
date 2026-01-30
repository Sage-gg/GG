<?php
// collections_api.php - API to receive invoices from billing system
header('Content-Type: application/json');
require_once 'db.php';

// Enable error logging
error_log("Collections API called at " . date('Y-m-d H:i:s'));

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

error_log("Received data: " . print_r($data, true));

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Missing action parameter.'
    ]);
    exit;
}

$action = $data['action'];

// Action: Transfer invoice to collections
if ($action === 'transfer_to_collections') {
    // Validate required fields
    $required = ['invoice_number', 'client_name', 'billing_date', 'due_date', 'amount_base'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    try {
        // Extract data
        $invoice_number = $conn->real_escape_string($data['invoice_number']);
        $client_name = $conn->real_escape_string($data['client_name']);
        $billing_date = $conn->real_escape_string($data['billing_date']);
        $due_date = $conn->real_escape_string($data['due_date']);
        $amount_base = floatval($data['amount_base']);
        $vat_applied = isset($data['vat_applied']) && $data['vat_applied'] === 'Yes' ? 'Yes' : 'No';
        $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : '';
        
        // Get fiscal year settings
        $fyRes = $conn->query("SELECT * FROM fiscal_year_settings WHERE is_active = 1 LIMIT 1");
        $fySettings = $fyRes->fetch_assoc() ?? ['start_month' => 1, 'start_day' => 1];
        
        // Calculate fiscal year
        $d = new DateTime($billing_date);
        $year = (int)$d->format('Y');
        $month = (int)$d->format('m');
        $day = (int)$d->format('d');
        $startMonth = (int)$fySettings['start_month'];
        $startDay = (int)$fySettings['start_day'];
        
        if ($month < $startMonth || ($month == $startMonth && $day < $startDay)) {
            $fiscal_year = 'FY' . ($year - 1);
        } else {
            $fiscal_year = 'FY' . $year;
        }
        
        // Calculate VAT and totals
        $VAT_RATE = 12.0;
        $vat_rate = $VAT_RATE;
        $vat_amount = ($vat_applied === 'Yes') ? round($amount_base * ($vat_rate / 100), 2) : 0.00;
        $amount_due = round($amount_base + $vat_amount, 2);
        
        // Default values for new collection
        $amount_paid = 0.00;
        $penalty = 0.00;
        $mode_of_payment = 'Pending';
        $payment_status = 'Unpaid';
        $receipt_type = 'Acknowledgment';
        $collector_name = 'System Transfer';
        $receipt_attachment = null;
        
        // Insert into collections table
        $stmt = $conn->prepare("INSERT INTO collections
            (client_name, invoice_no, billing_date, due_date, amount_base, vat_applied, vat_rate, 
            vat_amount, amount_due, amount_paid, penalty, mode_of_payment, payment_status, 
            receipt_type, collector_name, receipt_attachment, fiscal_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "ssssdsdddddssssss",
            $client_name, $invoice_number, $billing_date, $due_date,
            $amount_base, $vat_applied, $vat_rate, $vat_amount, $amount_due,
            $amount_paid, $penalty, $mode_of_payment, $payment_status, 
            $receipt_type, $collector_name, $receipt_attachment, $fiscal_year
        );
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Invoice successfully transferred to collections',
                'collection_id' => $new_id,
                'invoice_number' => $invoice_number,
                'fiscal_year' => $fiscal_year
            ]);
        } else {
            throw new Exception('Database insert failed: ' . $conn->error);
        }
        
    } catch (Exception $e) {
        error_log("Collections API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Action: Get collection status
if ($action === 'get_status') {
    if (!isset($data['invoice_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing invoice_number'
        ]);
        exit;
    }
    
    $invoice_number = $conn->real_escape_string($data['invoice_number']);
    $result = $conn->query("SELECT payment_status, amount_due, amount_paid, penalty, fiscal_year 
                           FROM collections 
                           WHERE invoice_no = '$invoice_number' 
                           LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'data' => [
                'payment_status' => $row['payment_status'],
                'amount_due' => floatval($row['amount_due']),
                'amount_paid' => floatval($row['amount_paid']),
                'remaining_balance' => floatval($row['amount_due']) - floatval($row['amount_paid']),
                'penalty' => floatval($row['penalty']),
                'fiscal_year' => $row['fiscal_year']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Invoice not found in collections'
        ]);
    }
    exit;
}

// Unknown action
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Unknown action: ' . $action
]);
?>
