<?php
// database_check.php - Check your database structure
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Database Structure Check</h2>
        <p>This page helps identify your database structure and any missing tables.</p>
        
        <?php
        try {
            // Connect to database
            $conn = new mysqli("localhost", "root", "", "fina_financial_system");
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            echo '<div class="alert alert-success">✅ Successfully connected to database: <strong>financial_system</strong></div>';
            
            // Get all tables
            $result = $conn->query("SHOW TABLES");
            
            if ($result) {
                echo '<h4>Tables found in your database:</h4>';
                echo '<div class="row">';
                
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                
                if (empty($tables)) {
                    echo '<div class="col-12"><div class="alert alert-warning">No tables found in the database.</div></div>';
                } else {
                    foreach ($tables as $table) {
                        // Check if table has data
                        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
                        $count = 0;
                        if ($count_result) {
                            $count_row = $count_result->fetch_assoc();
                            $count = $count_row['count'];
                        }
                        
                        echo '<div class="col-md-4 mb-2">';
                        echo '<div class="card">';
                        echo '<div class="card-body">';
                        echo '<h6 class="card-title">' . htmlspecialchars($table) . '</h6>';
                        echo '<p class="card-text small">Records: ' . $count . '</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                
                echo '</div>';
                
                // Check for required tables for financial reporting
                $required_tables = [
                    'collections' => 'For revenue calculations',
                    'expenses' => 'For expense calculations', 
                    'journal_entries' => 'For detailed financial reports',
                    'chart_of_accounts' => 'For account classifications',
                    'assets' => 'For asset calculations (optional)',
                    'budgets' => 'For budget performance reports (optional)',
                    'bank_accounts' => 'For cash flow reports (optional)'
                ];
                
                echo '<h4 class="mt-4">Required Tables Check:</h4>';
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>Table</th><th>Status</th><th>Purpose</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($required_tables as $table => $purpose) {
                    $exists = in_array($table, $tables);
                    $status_class = $exists ? 'success' : 'warning';
                    $status_text = $exists ? '✅ Exists' : '⚠️ Missing';
                    
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($table) . '</code></td>';
                    echo '<td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>';
                    echo '<td class="small">' . htmlspecialchars($purpose) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                
                // Show sample data from key tables
                $key_tables = ['collections', 'expenses'];
                foreach ($key_tables as $table) {
                    if (in_array($table, $tables)) {
                        echo '<h5 class="mt-4">Sample data from ' . htmlspecialchars($table) . ':</h5>';
                        $sample_result = $conn->query("SELECT * FROM `$table` LIMIT 3");
                        if ($sample_result && $sample_result->num_rows > 0) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-sm table-bordered">';
                            
                            // Table headers
                            $fields = $sample_result->fetch_fields();
                            echo '<thead><tr>';
                            foreach ($fields as $field) {
                                echo '<th class="small">' . htmlspecialchars($field->name) . '</th>';
                            }
                            echo '</tr></thead>';
                            
                            // Table data
                            echo '<tbody>';
                            $sample_result->data_seek(0); // Reset pointer
                            while ($row = $sample_result->fetch_assoc()) {
                                echo '<tr>';
                                foreach ($row as $value) {
                                    echo '<td class="small">' . htmlspecialchars($value ?? 'NULL') . '</td>';
                                }
                                echo '</tr>';
                            }
                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">Table exists but has no data.</div>';
                        }
                    }
                }
                
            } else {
                throw new Exception("Could not retrieve table list");
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">❌ Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            
            echo '<h4>Troubleshooting Steps:</h4>';
            echo '<ol>';
            echo '<li>Make sure your XAMPP MySQL service is running</li>';
            echo '<li>Check if your database is named <code>financial_system</code></li>';
            echo '<li>Verify your database connection in <code>db.php</code></li>';
            echo '<li>Import your database structure if missing</li>';
            echo '</ol>';
        }
        ?>
        
        <div class="mt-4">
            <a href="financial_reporting.php" class="btn btn-primary">Back to Financial Reports</a>
            <a href="export_test.php" class="btn btn-secondary">Test Export System</a>
        </div>
    </div>
</body>

</html>
