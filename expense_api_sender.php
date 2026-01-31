<?php

define('EXPENSE_API_URL', 'https://financials.cranecali-ms.com/expense_api_receiver.php');
define('EXPENSE_API_KEY', '02012026');

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

?>
