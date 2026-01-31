<?php
/**
 * Expense API Sender/Client
 * Send expense data to the API endpoint
 * 
 * Usage examples at the bottom of this file
 */

class ExpenseAPIClient {
    private $apiUrl;
    private $apiKey;
    
    /**
     * Constructor
     * 
     * @param string $apiUrl The URL of the API endpoint
     * @param string $apiKey The API key for authentication
     */
    public function __construct($apiUrl, $apiKey) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }
    
    /**
     * Make API request
     * 
     * @param string $method HTTP method (GET or POST)
     * @param array $data Request data
     * @return array Response data
     */
    private function makeRequest($method, $data = []) {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ];
        
        if ($method === 'GET' && !empty($data)) {
            $url = $this->apiUrl . '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error
            ];
        }
        
        $result = json_decode($response, true);
        $result['http_code'] = $httpCode;
        
        return $result;
    }
    
    /**
     * Add a new expense
     * 
     * @param array $expenseData Expense data
     * @return array Response
     */
    public function addExpense($expenseData) {
        $data = array_merge(['action' => 'add'], $expenseData);
        return $this->makeRequest('POST', $data);
    }
    
    /**
     * Update an expense
     * 
     * @param int $id Expense ID
     * @param array $expenseData Expense data to update
     * @return array Response
     */
    public function updateExpense($id, $expenseData) {
        $data = array_merge(['action' => 'update', 'id' => $id], $expenseData);
        return $this->makeRequest('POST', $data);
    }
    
    /**
     * Delete an expense
     * 
     * @param int $id Expense ID
     * @return array Response
     */
    public function deleteExpense($id) {
        $data = ['action' => 'delete', 'id' => $id];
        return $this->makeRequest('POST', $data);
    }
    
    /**
     * Get a single expense
     * 
     * @param int $id Expense ID
     * @return array Response
     */
    public function getExpense($id) {
        $data = ['action' => 'get', 'id' => $id];
        return $this->makeRequest('GET', $data);
    }
    
    /**
     * Get list of expenses
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @return array Response
     */
    public function listExpenses($page = 1, $limit = 10, $search = '') {
        $data = [
            'action' => 'list',
            'page' => $page,
            'limit' => $limit,
            'search' => $search
        ];
        return $this->makeRequest('GET', $data);
    }
    
    /**
     * Get expense summary
     * 
     * @param string $search Search term
     * @return array Response
     */
    public function getSummary($search = '') {
        $data = [
            'action' => 'summary',
            'search' => $search
        ];
        return $this->makeRequest('GET', $data);
    }
    
    /**
     * Convert file to base64 for upload
     * 
     * @param string $filePath Path to file
     * @return array|false Array with base64 data and extension, or false on failure
     */
    public static function fileToBase64($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $fileData = file_get_contents($filePath);
        $base64 = base64_encode($fileData);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        return [
            'receipt_file_base64' => $base64,
            'receipt_file_extension' => $extension
        ];
    }
}

// ============================================================================
// USAGE EXAMPLES
// ============================================================================

// Uncomment the examples below to test the API


// Initialize the API client
$apiUrl = 'https://financials.cranecali-ms.com/expense_api_receiver.php';
$apiKey = '02012026';
$api = new ExpenseAPIClient($apiUrl, $apiKey);

// Example 1: Add a new expense
echo "=== Example 1: Add New Expense ===\n";
$newExpense = [
    'expense_date' => '2026-01-31',
    'category' => 'Fuel',
    'vendor' => 'Shell Gas Station',
    'amount' => 1500.00,
    'remarks' => 'Diesel for crane truck',
    'tax_type' => 'VAT',
    'payment_method' => 'Cash',
    'vehicle' => 'Crane Truck 001',
    'job_linked' => 'Project Alpha',
    'approved_by' => 'Manager John',
    'status' => 'Approved'
];

// Optional: Add receipt file
$receiptPath = 'path/to/receipt.jpg';
if (file_exists($receiptPath)) {
    $receiptData = ExpenseAPIClient::fileToBase64($receiptPath);
    $newExpense = array_merge($newExpense, $receiptData);
}

$result = $api->addExpense($newExpense);
print_r($result);

// Example 2: Update an expense
echo "\n=== Example 2: Update Expense ===\n";
$expenseId = 1; // Replace with actual expense ID
$updateData = [
    'amount' => 1650.00,
    'remarks' => 'Diesel for crane truck - Updated amount',
    'status' => 'Approved'
];
$result = $api->updateExpense($expenseId, $updateData);
print_r($result);

// Example 3: Get single expense
echo "\n=== Example 3: Get Single Expense ===\n";
$expenseId = 1;
$result = $api->getExpense($expenseId);
print_r($result);

// Example 4: List expenses with pagination
echo "\n=== Example 4: List Expenses ===\n";
$result = $api->listExpenses($page = 1, $limit = 10, $search = '');
print_r($result);

// Example 5: Search expenses
echo "\n=== Example 5: Search Expenses ===\n";
$result = $api->listExpenses($page = 1, $limit = 10, $search = 'fuel');
print_r($result);

// Example 6: Get summary
echo "\n=== Example 6: Get Summary ===\n";
$result = $api->getSummary();
print_r($result);

// Example 7: Delete an expense
echo "\n=== Example 7: Delete Expense ===\n";
$expenseId = 1;
$result = $api->deleteExpense($expenseId);
print_r($result);

?>
