<?php
// collections_export.php
// Professional Excel export WITHOUT external libraries
include 'db.php';
requireLogin();

// Get filter parameters
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');
$fiscalYear = trim($_GET['fiscal_year'] ?? '');
$format = trim($_GET['format'] ?? 'excel');

// Build WHERE clause
$conditions = [];

if ($fiscalYear !== '') {
    $conditions[] = "fiscal_year = '" . $conn->real_escape_string($fiscalYear) . "'";
}

if ($dateFrom !== '') {
    $conditions[] = "billing_date >= '" . $conn->real_escape_string($dateFrom) . "'";
}

if ($dateTo !== '') {
    $conditions[] = "billing_date <= '" . $conn->real_escape_string($dateTo) . "'";
}

if ($paymentStatus !== '' && $paymentStatus !== 'All') {
    $conditions[] = "payment_status = '" . $conn->real_escape_string($paymentStatus) . "'";
}

$where = "";
if (!empty($conditions)) {
    $where = "WHERE " . implode(" AND ", $conditions);
}

// Get all records
$sql = "SELECT * FROM collections $where ORDER BY billing_date DESC, id DESC";
$result = $conn->query($sql);

$timestamp = date('Y-m-d_His');
$filename = "Collections_Export_{$timestamp}";

if ($format === 'excel') {
    // Excel XML format - opens perfectly in Excel with proper formatting
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for proper character encoding
    
    // Start Excel XML
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<?mso-application progid="Excel.Sheet"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:x="urn:schemas-microsoft-com:office:excel"
        xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:html="http://www.w3.org/TR/REC-html40">';
    
    // Styles
    echo '<Styles>';
    
    // Title style
    echo '<Style ss:ID="Title">
        <Font ss:Bold="1" ss:Size="16" ss:Color="#FFFFFF"/>
        <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    </Style>';
    
    // Header style
    echo '<Style ss:ID="Header">
        <Font ss:Bold="1" ss:Color="#FFFFFF"/>
        <Interior ss:Color="#0070C0" ss:Pattern="Solid"/>
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>';
    
    // Currency style
    echo '<Style ss:ID="Currency">
        <NumberFormat ss:Format="₱#,##0.00"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>';
    
    // Normal data style with borders
    echo '<Style ss:ID="Data">
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>';
    
    // Status styles
    echo '<Style ss:ID="Paid">
        <Font ss:Color="#008000" ss:Bold="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>';
    
    echo '<Style ss:ID="Partial">
        <Font ss:Color="#FF8C00" ss:Bold="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>';
    
    echo '<Style ss:ID="Unpaid">
        <Font ss:Color="#FF0000" ss:Bold="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>';
    
    // Summary label style
    echo '<Style ss:ID="SummaryLabel">
        <Font ss:Bold="1"/>
        <Interior ss:Color="#E7E6E6" ss:Pattern="Solid"/>
    </Style>';
    
    // Summary value style
    echo '<Style ss:ID="SummaryValue">
        <NumberFormat ss:Format="₱#,##0.00"/>
        <Font ss:Bold="1"/>
    </Style>';
    
    echo '</Styles>';
    
    // Worksheet
    echo '<Worksheet ss:Name="Collections Report">';
    echo '<Table>';
    
    // Set column widths
    echo '<Column ss:Width="50"/>'; // ID
    echo '<Column ss:Width="100"/>'; // Fiscal Year
    echo '<Column ss:Width="200"/>'; // Client Name
    echo '<Column ss:Width="150"/>'; // Invoice Number
    echo '<Column ss:Width="100"/>'; // Billing Date
    echo '<Column ss:Width="100"/>'; // Due Date
    echo '<Column ss:Width="120"/>'; // Base Amount
    echo '<Column ss:Width="100"/>'; // VAT Applied
    echo '<Column ss:Width="100"/>'; // VAT Rate
    echo '<Column ss:Width="120"/>'; // VAT Amount
    echo '<Column ss:Width="130"/>'; // Total Amount Due
    echo '<Column ss:Width="120"/>'; // Amount Paid
    echo '<Column ss:Width="130"/>'; // Remaining Balance
    echo '<Column ss:Width="100"/>'; // Penalty
    echo '<Column ss:Width="150"/>'; // Mode of Payment
    echo '<Column ss:Width="120"/>'; // Payment Status
    echo '<Column ss:Width="150"/>'; // Receipt Type
    echo '<Column ss:Width="180"/>'; // Collector Name
    echo '<Column ss:Width="200"/>'; // Receipt Attachment
    echo '<Column ss:Width="150"/>'; // Created At
    
    // Title row
    echo '<Row ss:Height="30">
        <Cell ss:MergeAcross="19" ss:StyleID="Title">
            <Data ss:Type="String">COLLECTIONS MANAGEMENT REPORT</Data>
        </Cell>
    </Row>';
    
    // Generation date
    echo '<Row>
        <Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Generated On:</Data></Cell>
        <Cell><Data ss:Type="String">' . date('F d, Y h:i A') . '</Data></Cell>
    </Row>';
    
    // Filter info
    if (!empty($conditions)) {
        echo '<Row>
            <Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Filters Applied:</Data></Cell>
        </Row>';
        
        if ($fiscalYear !== '') {
            echo '<Row>
                <Cell><Data ss:Type="String">Fiscal Year:</Data></Cell>
                <Cell><Data ss:Type="String">' . htmlspecialchars($fiscalYear) . '</Data></Cell>
            </Row>';
        }
        if ($dateFrom !== '') {
            echo '<Row>
                <Cell><Data ss:Type="String">From Date:</Data></Cell>
                <Cell><Data ss:Type="String">' . htmlspecialchars($dateFrom) . '</Data></Cell>
            </Row>';
        }
        if ($dateTo !== '') {
            echo '<Row>
                <Cell><Data ss:Type="String">To Date:</Data></Cell>
                <Cell><Data ss:Type="String">' . htmlspecialchars($dateTo) . '</Data></Cell>
            </Row>';
        }
        if ($paymentStatus !== '') {
            echo '<Row>
                <Cell><Data ss:Type="String">Payment Status:</Data></Cell>
                <Cell><Data ss:Type="String">' . htmlspecialchars($paymentStatus) . '</Data></Cell>
            </Row>';
        }
    }
    
    // Empty row
    echo '<Row/>';
    
    // Header row
    echo '<Row ss:Height="25">';
    $headers = [
        'ID', 'Fiscal Year', 'Client Name', 'Invoice Number', 'Billing Date', 'Due Date',
        'Base Amount', 'VAT Applied', 'VAT Rate (%)', 'VAT Amount', 'Total Amount Due',
        'Amount Paid', 'Remaining Balance', 'Penalty', 'Mode of Payment', 'Payment Status',
        'Receipt Type', 'Collector Name', 'Receipt Attachment', 'Created At'
    ];
    
    foreach ($headers as $header) {
        echo '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>';
    }
    echo '</Row>';
    
    // Data rows
    $totalCollected = 0;
    $totalPending = 0;
    $totalOverdue = 0;
    $today = date('Y-m-d');
    $rowCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $rowCount++;
        $remainingBalance = $row['amount_due'] - $row['amount_paid'];
        
        // Calculate totals
        $totalCollected += (float)$row['amount_paid'];
        $pending = max(0, (float)$row['amount_due'] - (float)$row['amount_paid']);
        $totalPending += $pending;
        
        if ($row['payment_status'] !== 'Paid' && $row['due_date'] < $today) {
            $totalOverdue += $pending;
        }
        
        // Determine status style
        $statusStyle = 'Data';
        if ($row['payment_status'] === 'Paid') {
            $statusStyle = 'Paid';
        } elseif ($row['payment_status'] === 'Partial') {
            $statusStyle = 'Partial';
        } elseif ($row['payment_status'] === 'Unpaid') {
            $statusStyle = 'Unpaid';
        }
        
        echo '<Row>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="Number">' . $row['id'] . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['fiscal_year'] ?? 'N/A') . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['client_name']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['invoice_no']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['billing_date']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['due_date']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $row['amount_base'] . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['vat_applied']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="Number">' . $row['vat_rate'] . '</Data></Cell>';
        echo '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $row['vat_amount'] . '</Data></Cell>';
        echo '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $row['amount_due'] . '</Data></Cell>';
        echo '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $row['amount_paid'] . '</Data></Cell>';
        echo '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $remainingBalance . '</Data></Cell>';
        echo '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $row['penalty'] . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['mode_of_payment']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $statusStyle . '"><Data ss:Type="String">' . htmlspecialchars($row['payment_status']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['receipt_type']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['collector_name']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['receipt_attachment'] ?? 'No attachment') . '</Data></Cell>';
        echo '<Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($row['created_at']) . '</Data></Cell>';
        echo '</Row>';
    }
    
    // Empty rows
    echo '<Row/><Row/>';
    
    // Summary section
    echo '<Row>
        <Cell ss:MergeAcross="1" ss:StyleID="SummaryLabel">
            <Data ss:Type="String">SUMMARY</Data>
        </Cell>
    </Row>';
    
    echo '<Row>
        <Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Total Records:</Data></Cell>
        <Cell ss:StyleID="SummaryLabel"><Data ss:Type="Number">' . $rowCount . '</Data></Cell>
    </Row>';
    
    echo '<Row>
        <Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Total Collected:</Data></Cell>
        <Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $totalCollected . '</Data></Cell>
    </Row>';
    
    echo '<Row>
        <Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Total Pending:</Data></Cell>
        <Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $totalPending . '</Data></Cell>
    </Row>';
    
    echo '<Row>
        <Cell ss:StyleID="SummaryLabel"><Data ss:Type="String">Total Overdue:</Data></Cell>
        <Cell ss:StyleID="SummaryValue"><Data ss:Type="Number">' . $totalOverdue . '</Data></Cell>
    </Row>';
    
    echo '</Table>';
    echo '</Worksheet>';
    echo '</Workbook>';
    
} else {
    // CSV format
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    
    $output = fopen('php://output', 'w');
    
    // Headers
    $headers = [
        'ID', 'Fiscal Year', 'Client Name', 'Invoice Number', 'Billing Date', 'Due Date',
        'Base Amount', 'VAT Applied', 'VAT Rate (%)', 'VAT Amount', 'Total Amount Due',
        'Amount Paid', 'Remaining Balance', 'Penalty', 'Mode of Payment', 'Payment Status',
        'Receipt Type', 'Collector Name', 'Receipt Attachment', 'Created At'
    ];
    
    fputcsv($output, $headers);
    
    // Data
    $totalCollected = 0;
    $totalPending = 0;
    $totalOverdue = 0;
    $today = date('Y-m-d');
    
    while ($row = $result->fetch_assoc()) {
        $remainingBalance = $row['amount_due'] - $row['amount_paid'];
        
        $totalCollected += (float)$row['amount_paid'];
        $pending = max(0, (float)$row['amount_due'] - (float)$row['amount_paid']);
        $totalPending += $pending;
        
        if ($row['payment_status'] !== 'Paid' && $row['due_date'] < $today) {
            $totalOverdue += $pending;
        }
        
        $dataRow = [
            $row['id'],
            $row['fiscal_year'] ?? 'N/A',
            $row['client_name'],
            $row['invoice_no'],
            $row['billing_date'],
            $row['due_date'],
            number_format($row['amount_base'], 2, '.', ''),
            $row['vat_applied'],
            number_format($row['vat_rate'], 2, '.', ''),
            number_format($row['vat_amount'], 2, '.', ''),
            number_format($row['amount_due'], 2, '.', ''),
            number_format($row['amount_paid'], 2, '.', ''),
            number_format($remainingBalance, 2, '.', ''),
            number_format($row['penalty'], 2, '.', ''),
            $row['mode_of_payment'],
            $row['payment_status'],
            $row['receipt_type'],
            $row['collector_name'],
            $row['receipt_attachment'] ?? 'No attachment',
            $row['created_at']
        ];
        fputcsv($output, $dataRow);
    }
    
    // Summary
    fputcsv($output, []);
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Records', $result->num_rows]);
    fputcsv($output, ['Total Collected', '₱ ' . number_format($totalCollected, 2)]);
    fputcsv($output, ['Total Pending', '₱ ' . number_format($totalPending, 2)]);
    fputcsv($output, ['Total Overdue', '₱ ' . number_format($totalOverdue, 2)]);
    
    fclose($output);
}

exit;
?>
