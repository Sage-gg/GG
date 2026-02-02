<?php
require_once 'db.php';
// index.php - Enhanced with AI Analytics
// Require login to access dashboard
requireModuleAccess('dashboard');

// ORIGINAL FUNCTIONS - Keep for backward compatibility
function getCollectionsSummary() {
  global $conn;
  
  $total_collected = 0;
  $total_pending = 0;
  $total_overdue = 0;
  
  $today = date('Y-m-d');
  
  try {
    $sql = "SELECT * FROM collections";
    $res = $conn->query($sql);
    
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $total_collected += (float)$r['amount_paid'];
        
        $pending = max(0, (float)$r['amount_due'] - (float)$r['amount_paid']);
        $total_pending += $pending;

        if ($r['payment_status'] !== 'Paid' && $r['due_date'] < $today && $pending > 0) {
          $total_overdue += $pending;
        }
      }
    }
  } catch (Exception $e) {
    error_log("Dashboard collections summary error: " . $e->getMessage());
  }
  
  return [
    'total_collected' => round($total_collected, 2),
    'total_pending' => round($total_pending, 2),
    'total_overdue' => round($total_overdue, 2)
  ];
}

// Function to get budget summary data
function getBudgetSummary() {
  global $conn;
  
  $total_budget = 0;
  $total_used = 0;
  $total_remaining = 0;
  
  try {
    $sql = "SELECT 
              COALESCE(SUM(amount_allocated),0) AS total_budget,
              COALESCE(SUM(amount_used),0) AS total_used,
              COALESCE(SUM(amount_allocated - amount_used),0) AS total_remaining
            FROM budgets";
    
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
      $total_budget = (float)$row['total_budget'];
      $total_used = (float)$row['total_used'];
      $total_remaining = (float)$row['total_remaining'];
    }
  } catch (Exception $e) {
    error_log("Dashboard budget summary error: " . $e->getMessage());
  }
  
  return [
    'total_budget' => round($total_budget, 2),
    'total_used' => round($total_used, 2),
    'total_remaining' => round($total_remaining, 2)
  ];
}

// NEW AI ANALYTICS FUNCTIONS - ENHANCED WITH DETAILED REASONING

/**
 * AI-Powered Collections Analysis
 * Provides insights, trends, and predictions for collections
 * 
 * ENHANCED WITH:
 * - Clear risk thresholds and basis
 * - Detailed reasoning for each risk level
 * - Specific, actionable recommendations
 * 
 * RISK LEVEL THRESHOLDS:
 * - LOW RISK: Overdue < 15% of total invoices
 *   Basis: Industry standard for healthy accounts receivable management
 * - MEDIUM RISK: Overdue 15-30% of total invoices
 *   Basis: Above acceptable threshold, requires proactive intervention
 * - HIGH RISK: Overdue > 30% of total invoices
 *   Basis: Critical level indicating systemic collection problems
 */
function getCollectionsAnalytics() {
  global $conn;
  
  $analytics = [
    'trend' => 'stable',
    'trend_percentage' => 0,
    'prediction' => '',
    'insight' => '',
    'risk_level' => 'low',
    'risk_reasoning' => '',  // NEW: Detailed explanation of risk assessment
    'recommended_actions' => [],
    'monthly_comparison' => [],
    'data_quality' => 'good',
    'data_note' => ''
  ];
  
  try {
    // Get current month and last month collections
    $sql = "SELECT 
              DATE_FORMAT(billing_date, '%Y-%m') as month,
              COALESCE(SUM(amount_paid), 0) as total_paid,
              COUNT(*) as invoice_count,
              SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
              SUM(CASE WHEN payment_status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_count,
              COALESCE(AVG(DATEDIFF(CURDATE(), due_date)), 0) as avg_days_overdue
            FROM collections
            WHERE billing_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month DESC
            LIMIT 6";
    
    $result = $conn->query($sql);
    $monthlyData = [];
    
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $monthlyData[] = $row;
      }
    }
    
    // Assess data quality
    if (count($monthlyData) < 3) {
      $analytics['data_quality'] = 'poor';
      $analytics['data_note'] = 'Need at least 3 months of data for accurate analysis';
    } else {
      $hasGaps = false;
      for ($i = 0; $i < count($monthlyData) - 1; $i++) {
        $date1 = strtotime($monthlyData[$i]['month'] . '-01');
        $date2 = strtotime($monthlyData[$i + 1]['month'] . '-01');
        $monthGap = abs(($date1 - $date2) / (86400 * 30));
        
        if ($monthGap > 2) {
          $hasGaps = true;
          break;
        }
      }
      
      if ($hasGaps) {
        $analytics['data_quality'] = 'fair';
        $analytics['data_note'] = 'Data gaps detected - insights may be less accurate';
      }
    }
    
    // Calculate trend
    if (count($monthlyData) >= 2) {
      $currentMonth = (float)$monthlyData[0]['total_paid'];
      $lastMonth = (float)$monthlyData[1]['total_paid'];
      
      $currentDate = strtotime($monthlyData[0]['month'] . '-01');
      $lastDate = strtotime($monthlyData[1]['month'] . '-01');
      $daysDiff = abs(($currentDate - $lastDate) / 86400);
      
      if ($lastMonth > 0 && $daysDiff <= 45) {
        $analytics['trend_percentage'] = round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
        
        if ($analytics['trend_percentage'] > 10) {
          $analytics['trend'] = 'increasing';
        } elseif ($analytics['trend_percentage'] < -10) {
          $analytics['trend'] = 'decreasing';
        }
      } else {
        $avgPrevious = 0;
        $countPrevious = 0;
        for ($i = 1; $i < count($monthlyData); $i++) {
          $avgPrevious += (float)$monthlyData[$i]['total_paid'];
          $countPrevious++;
        }
        if ($countPrevious > 0) {
          $avgPrevious = $avgPrevious / $countPrevious;
          if ($avgPrevious > 0) {
            $analytics['trend_percentage'] = round((($currentMonth - $avgPrevious) / $avgPrevious) * 100, 1);
            if ($analytics['trend_percentage'] > 10) {
              $analytics['trend'] = 'increasing';
            } elseif ($analytics['trend_percentage'] < -10) {
              $analytics['trend'] = 'decreasing';
            }
          }
        }
      }
    }
    
    // Calculate average collection rate
    $totalPaid = array_sum(array_column($monthlyData, 'total_paid'));
    $avgMonthly = count($monthlyData) > 0 ? $totalPaid / count($monthlyData) : 0;
    
    // Predict next month
    if (count($monthlyData) >= 3) {
      $hasGoodData = true;
      
      for ($i = 0; $i < count($monthlyData) - 1; $i++) {
        $date1 = strtotime($monthlyData[$i]['month'] . '-01');
        $date2 = strtotime($monthlyData[$i + 1]['month'] . '-01');
        $monthGap = abs(($date1 - $date2) / (86400 * 30));
        
        if ($monthGap > 2) {
          $hasGoodData = false;
          break;
        }
      }
      
      if ($hasGoodData) {
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $n = count($monthlyData);
        
        foreach ($monthlyData as $i => $data) {
          $x = $i;
          $y = (float)$data['total_paid'];
          $sumX += $x;
          $sumY += $y;
          $sumXY += $x * $y;
          $sumX2 += $x * $x;
        }
        
        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if ($denominator != 0) {
          $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
          $intercept = ($sumY - $slope * $sumX) / $n;
          $nextMonthPrediction = $slope * (-1) + $intercept;
          
          if ($nextMonthPrediction > 0 && $nextMonthPrediction < ($avgMonthly * 3)) {
            $analytics['prediction'] = '₱' . number_format(max(0, $nextMonthPrediction), 2);
          } else {
            $analytics['prediction'] = 'Insufficient data for reliable prediction';
          }
        }
      } else {
        $analytics['prediction'] = 'Data gaps detected - prediction unavailable';
      }
    }
    
    // Analyze overdue risk - ENHANCED WITH DETAILED REASONING
    $overdueQuery = "SELECT 
                      COUNT(*) as overdue_count, 
                      COALESCE(SUM(amount_due - amount_paid), 0) as overdue_amount
                     FROM collections 
                     WHERE payment_status != 'Paid' AND due_date < CURDATE()";
    $overdueResult = $conn->query($overdueQuery);
    
    if (!$overdueResult) {
      error_log("Overdue query failed: " . $conn->error);
      $overdueData = ['overdue_count' => 0, 'overdue_amount' => 0];
    } else {
      $overdueData = $overdueResult->fetch_assoc();
    }
    
    $overduePercentage = 0;
    $totalInvoices = 0;
    
    if (count($monthlyData) > 0 && isset($monthlyData[0]['invoice_count']) && $monthlyData[0]['invoice_count'] > 0) {
      $totalInvoices = (int)$monthlyData[0]['invoice_count'];
      $overduePercentage = ((int)$overdueData['overdue_count'] / $totalInvoices) * 100;
    }
    
    // ENHANCED RISK ASSESSMENT WITH THRESHOLDS AND DETAILED REASONING
    // 
    // THRESHOLD DEFINITIONS AND BASIS:
    // - LOW RISK (< 15% overdue):
    //   * Industry benchmark for healthy AR management
    //   * Indicates effective collection processes
    //   * Normal business operations expected
    //
    // - MEDIUM RISK (15-30% overdue):
    //   * Above acceptable industry threshold
    //   * Signals need for process improvement
    //   * Proactive intervention can prevent escalation
    //
    // - HIGH RISK (> 30% overdue):
    //   * Critical level indicating systemic issues
    //   * Significant cash flow impact
    //   * Immediate corrective action required
    
    if ($overduePercentage > 30) {
      $analytics['risk_level'] = 'high';
      $analytics['risk_reasoning'] = sprintf(
        "CRITICAL: Over 30%% of invoices are currently overdue (%d out of %d total invoices = %.1f%%), " .
        "totaling ₱%s in outstanding receivables. " .
        "This exceeds the critical threshold and indicates significant collection challenges. " .
        "Industry standard for healthy accounts receivable is to maintain overdue invoices below 15%%. " .
        "At this level, there is substantial impact on cash flow and increased risk of bad debt write-offs. " .
        "Immediate escalation and intensive collection efforts are required to prevent further deterioration.",
        (int)$overdueData['overdue_count'],
        $totalInvoices,
        $overduePercentage,
        number_format($overdueData['overdue_amount'], 2)
      );
    } elseif ($overduePercentage > 15) {
      $analytics['risk_level'] = 'medium';
      $analytics['risk_reasoning'] = sprintf(
        "WARNING: Between 15-30%% of invoices are overdue (%d out of %d total invoices = %.1f%%), " .
        "totaling ₱%s in outstanding receivables. " .
        "This is above the recommended industry threshold of 15%% and requires closer monitoring. " .
        "While not yet critical, this level indicates emerging collection issues that need proactive attention. " .
        "Studies show that collection probability decreases significantly as accounts age beyond 30 days. " .
        "Enhanced collection efforts now can prevent escalation to high-risk status and minimize bad debt exposure.",
        (int)$overdueData['overdue_count'],
        $totalInvoices,
        $overduePercentage,
        number_format($overdueData['overdue_amount'], 2)
      );
    } else {
      $analytics['risk_level'] = 'low';
      $analytics['risk_reasoning'] = sprintf(
        "HEALTHY: Less than 15%% of invoices are overdue (%d out of %d total invoices = %.1f%%), " .
        "totaling ₱%s in outstanding receivables. " .
        "Collection performance is within acceptable industry standards (benchmark: <15%% overdue rate). " .
        "This indicates effective accounts receivable management and healthy customer payment behavior. " .
        "Current collection practices are working well and should be maintained. " .
        "Continue monitoring to ensure this healthy status persists and catch any emerging trends early.",
        (int)$overdueData['overdue_count'],
        $totalInvoices,
        $overduePercentage,
        number_format($overdueData['overdue_amount'], 2)
      );
    }
    
    // Generate insights
    if ($analytics['trend'] === 'increasing') {
      $analytics['insight'] = "Collections are trending upward by {$analytics['trend_percentage']}%. Great performance!";
    } elseif ($analytics['trend'] === 'decreasing') {
      if (count($monthlyData) >= 2) {
        $currentDate = strtotime($monthlyData[0]['month'] . '-01');
        $lastDate = strtotime($monthlyData[1]['month'] . '-01');
        $daysDiff = abs(($currentDate - $lastDate) / 86400);
        
        if ($daysDiff > 45) {
          $analytics['insight'] = "Recent collections lower than historical average. Note: Data gaps detected in collection history.";
        } else {
          $analytics['insight'] = "Collections declining by " . abs($analytics['trend_percentage']) . "%. Review collection strategies.";
        }
      } else {
        $analytics['insight'] = "Collections declining by " . abs($analytics['trend_percentage']) . "%. Review collection strategies.";
      }
    } else {
      if (count($monthlyData) >= 3) {
        $analytics['insight'] = "Collections are stable. Maintain current practices.";
      } else {
        $analytics['insight'] = "Limited historical data. Continue monitoring collection patterns.";
      }
    }
    
    // ENHANCED Recommended actions - specific, actionable, risk-appropriate
    if ($analytics['risk_level'] === 'high') {
      $analytics['recommended_actions'][] = "URGENT: Immediately follow up on " . $overdueData['overdue_count'] . " overdue invoices totaling ₱" . number_format($overdueData['overdue_amount'], 2);
      $analytics['recommended_actions'][] = "Implement daily collection call schedule for all accounts over 30 days past due";
      $analytics['recommended_actions'][] = "Send formal demand letters to accounts 60+ days overdue with payment deadline";
      $analytics['recommended_actions'][] = "For accounts 90+ days overdue: Consider engaging professional collection agency";
      $analytics['recommended_actions'][] = "Implement stricter payment terms for new invoices (e.g., 50% deposit, Net 15 days)";
      $analytics['recommended_actions'][] = "Review and tighten credit approval process to prevent future high-risk accounts";
      $analytics['recommended_actions'][] = "Escalate to senior management - this requires executive attention and possible policy changes";
    } elseif ($analytics['risk_level'] === 'medium') {
      $analytics['recommended_actions'][] = "Actively monitor " . $overdueData['overdue_count'] . " overdue accounts totaling ₱" . number_format($overdueData['overdue_amount'], 2);
      $analytics['recommended_actions'][] = "Send formal payment reminder emails twice weekly to all overdue accounts";
      $analytics['recommended_actions'][] = "Schedule systematic collection calls for accounts 15+ days overdue";
      $analytics['recommended_actions'][] = "Document all collection communication attempts for escalation tracking";
      $analytics['recommended_actions'][] = "Offer structured payment plans to clients experiencing temporary cash flow issues";
      $analytics['recommended_actions'][] = "Consider offering early payment incentives (e.g., 2% discount for payment within 10 days)";
      $analytics['recommended_actions'][] = "Review customer creditworthiness before extending additional credit";
    } else {
      $analytics['recommended_actions'][] = "Maintain current collection practices - performance is healthy and within industry benchmarks";
      $analytics['recommended_actions'][] = "Continue sending automated payment reminders 5 days before due date";
      $analytics['recommended_actions'][] = "Monitor for any increases in overdue percentages in weekly review cycles";
      $analytics['recommended_actions'][] = "Document successful collection strategies for process optimization";
      $analytics['recommended_actions'][] = "Consider implementing early payment discount program to improve cash flow velocity";
    }
    
    if ($analytics['trend'] === 'decreasing') {
      $analytics['recommended_actions'][] = "TREND ALERT: Investigate root cause of declining collections - analyze by client segment, industry, and invoice size";
      $analytics['recommended_actions'][] = "Review accounts receivable aging report weekly to identify patterns and problem accounts";
      $analytics['recommended_actions'][] = "Assess market conditions and client financial health for systemic issues";
      $analytics['recommended_actions'][] = "Consider revising pricing strategy or payment terms if market conditions have changed";
    }
    
    $analytics['monthly_comparison'] = $monthlyData;
    
  } catch (Exception $e) {
    error_log("Collections analytics error: " . $e->getMessage());
  }
  
  return $analytics;
}

/**
 * AI-Powered Budget Analysis
 * 
 * ENHANCED WITH:
 * - Clear utilization thresholds and basis
 * - Detailed reasoning for each risk level
 * - Department-specific tracking and alerts
 * 
 * RISK LEVEL THRESHOLDS:
 * - LOW RISK: Utilization < 75%
 *   Basis: Healthy buffer for unexpected expenses and contingencies
 * - MEDIUM RISK: Utilization 75-90%
 *   Basis: Approaching critical threshold, requires spending controls
 * - HIGH RISK: Utilization > 90%
 *   Basis: Minimal buffer remaining, high risk of budget overrun
 */
function getBudgetAnalytics() {
  global $conn;
  
  $analytics = [
    'utilization_rate' => 0,
    'trend' => 'stable',
    'insight' => '',
    'risk_level' => 'low',
    'risk_reasoning' => '',  // NEW: Detailed explanation of risk assessment
    'prediction' => '',
    'recommended_actions' => [],
    'top_spending_departments' => [],
    'burn_rate' => 0
  ];
  
  try {
    $sql = "SELECT 
              SUM(amount_allocated) as total_allocated,
              SUM(amount_used) as total_used
            FROM budgets
            WHERE approval_status = 'Approved'";
    
    $result = $conn->query($sql);
    $overall = $result->fetch_assoc();
    
    if ($overall['total_allocated'] > 0) {
      $analytics['utilization_rate'] = round(($overall['total_used'] / $overall['total_allocated']) * 100, 1);
    }
    
    // Department analysis
    $deptSql = "SELECT 
                  department,
                  SUM(amount_allocated) as allocated,
                  SUM(amount_used) as used,
                  (SUM(amount_used) / SUM(amount_allocated) * 100) as utilization
                FROM budgets
                WHERE approval_status = 'Approved' AND amount_allocated > 0
                GROUP BY department
                ORDER BY used DESC
                LIMIT 5";
    
    $deptResult = $conn->query($deptSql);
    while ($row = $deptResult->fetch_assoc()) {
      $analytics['top_spending_departments'][] = [
        'department' => $row['department'],
        'used' => $row['used'],
        'allocated' => $row['allocated'],
        'utilization' => round($row['utilization'], 1)
      ];
    }
    
    // Calculate monthly burn rate
    $burnSql = "SELECT 
                  SUM(amount_used) as monthly_spend
                FROM budgets
                WHERE period = 'Monthly' AND approval_status = 'Approved'";
    
    $burnResult = $conn->query($burnSql);
    $burnData = $burnResult->fetch_assoc();
    $analytics['burn_rate'] = round($burnData['monthly_spend'], 2);
    
    // Prediction
    if ($analytics['burn_rate'] > 0 && $overall['total_allocated'] > $overall['total_used']) {
      $remaining = $overall['total_allocated'] - $overall['total_used'];
      $monthsRemaining = $remaining / $analytics['burn_rate'];
      $analytics['prediction'] = round($monthsRemaining, 1) . " months until budget depletion at current burn rate of ₱" . number_format($analytics['burn_rate'], 2) . "/month";
    }
    
    // ENHANCED RISK ASSESSMENT WITH THRESHOLDS AND DETAILED REASONING
    //
    // THRESHOLD DEFINITIONS AND BASIS:
    // - LOW RISK (< 75% utilized):
    //   * Recommended buffer: 25%+ for contingencies
    //   * Allows flexibility for unexpected expenses
    //   * Healthy spending velocity
    //
    // - MEDIUM RISK (75-90% utilized):
    //   * Warning zone - limited buffer remaining
    //   * Need to slow spending velocity
    //   * Enhanced approvals recommended
    //
    // - HIGH RISK (> 90% utilized):
    //   * Critical threshold - minimal buffer
    //   * High probability of budget overrun
    //   * Immediate spending freeze often required
    
    $remaining_amount = $overall['total_allocated'] - $overall['total_used'];
    $remaining_pct = 100 - $analytics['utilization_rate'];
    
    if ($analytics['utilization_rate'] > 90) {
      $analytics['risk_level'] = 'high';
      $analytics['risk_reasoning'] = sprintf(
        "CRITICAL: Budget utilization at %.1f%% with only %.1f%% (₱%s) remaining of ₱%s total allocated budget. " .
        "At this critical level (>90%%), there is minimal buffer for unexpected expenses or emergencies. " .
        "Based on current burn rate of ₱%s/month, remaining funds may be exhausted within %.1f months. " .
        "Industry best practice is to maintain at least 10%% reserve for contingencies and unexpected costs. " .
        "Immediate spending freeze on non-essential expenses is strongly recommended to prevent budget overrun. " .
        "Any overage will require emergency budget increases or reallocation from other departments.",
        $analytics['utilization_rate'],
        $remaining_pct,
        number_format($remaining_amount, 2),
        number_format($overall['total_allocated'], 2),
        number_format($analytics['burn_rate'], 2),
        $analytics['burn_rate'] > 0 ? $remaining_amount / $analytics['burn_rate'] : 0
      );
      $analytics['insight'] = "Critical: {$analytics['utilization_rate']}% budget utilized. Immediate action required!";
      $analytics['recommended_actions'][] = "IMMEDIATE: Implement spending freeze on all non-essential expenses effective immediately";
      $analytics['recommended_actions'][] = "URGENT: Request emergency budget increase or reallocation from senior management";
      $analytics['recommended_actions'][] = "Review all pending purchase orders and cancel/postpone non-critical items";
      $analytics['recommended_actions'][] = "Require executive approval for ANY new expenses, regardless of amount";
      $analytics['recommended_actions'][] = "Conduct daily budget monitoring and send alerts if utilization exceeds 95%";
      $analytics['recommended_actions'][] = "Identify opportunities for cost savings or expense deferrals to next period";
      $analytics['recommended_actions'][] = "Prepare contingency plan and communicate budget constraints to all stakeholders";
    } elseif ($analytics['utilization_rate'] > 75) {
      $analytics['risk_level'] = 'medium';
      $analytics['risk_reasoning'] = sprintf(
        "WARNING: Budget utilization at %.1f%% with %.1f%% (₱%s) remaining of ₱%s total allocated budget. " .
        "Approaching the 90%% critical threshold. Proactive spending controls needed now to avoid escalation. " .
        "At current burn rate of ₱%s/month, projected to reach 90%% utilization within %.1f months. " .
        "Standard financial practice is to implement enhanced controls when crossing 75%% utilization. " .
        "This provides adequate time to slow spending velocity and make necessary adjustments. " .
        "Weekly budget review meetings recommended to monitor trends and implement corrective actions.",
        $analytics['utilization_rate'],
        $remaining_pct,
        number_format($remaining_amount, 2),
        number_format($overall['total_allocated'], 2),
        number_format($analytics['burn_rate'], 2),
        $analytics['burn_rate'] > 0 ? ($overall['total_allocated'] * 0.9 - $overall['total_used']) / $analytics['burn_rate'] : 0
      );
      $analytics['insight'] = "Warning: {$analytics['utilization_rate']}% budget utilized. Monitor spending closely.";
      $analytics['recommended_actions'][] = "Implement enhanced approval process for all new expenses over ₱10,000";
      $analytics['recommended_actions'][] = "Require department head justification for non-budgeted expenses";
      $analytics['recommended_actions'][] = "Review and prioritize all remaining planned expenses - defer non-critical items to next period";
      $analytics['recommended_actions'][] = "Conduct weekly budget review meetings with all department heads";
      $analytics['recommended_actions'][] = "Reduce discretionary spending by 20-30% to create buffer";
      $analytics['recommended_actions'][] = "Prepare contingency plan in case utilization reaches 90% threshold";
      $analytics['recommended_actions'][] = "Identify opportunities for cost optimization and vendor renegotiation";
    } else {
      $analytics['risk_level'] = 'low';
      $analytics['risk_reasoning'] = sprintf(
        "HEALTHY: Budget utilization at %.1f%% with %.1f%% (₱%s) remaining of ₱%s total allocated budget. " .
        "Spending is well-controlled with healthy reserves for contingencies and unexpected expenses. " .
        "At current burn rate of ₱%s/month, adequate buffer exists to accommodate normal business operations. " .
        "Best practice target is 70-80%% utilization by end of budget period, which allows for optimal resource usage " .
        "while maintaining appropriate reserves. Current performance indicates effective budget management. " .
        "Continue monitoring to maintain this healthy utilization rate and catch any emerging spending trends.",
        $analytics['utilization_rate'],
        $remaining_pct,
        number_format($remaining_amount, 2),
        number_format($overall['total_allocated'], 2),
        number_format($analytics['burn_rate'], 2)
      );
      $analytics['insight'] = "Budget utilization at {$analytics['utilization_rate']}%. On track.";
      $analytics['recommended_actions'][] = "Continue current spending controls - budget health is good and within target range";
      $analytics['recommended_actions'][] = "Monitor monthly spending trends to ensure predictable and sustainable burn rate";
      $analytics['recommended_actions'][] = "Plan ahead for end-of-period spending to optimize budget usage without waste";
      $analytics['recommended_actions'][] = "Document successful budget management practices for future periods";
      $analytics['recommended_actions'][] = "Consider strategic investments or initiatives with remaining budget capacity";
    }
    
    // ENHANCED: Check individual department overspending with specific alerts
    foreach ($analytics['top_spending_departments'] as $dept) {
      if ($dept['utilization'] > 100) {
        $overage_amount = $dept['used'] - $dept['allocated'];
        $overage_pct = $dept['utilization'] - 100;
        $analytics['recommended_actions'][] = sprintf(
          "🚨 CRITICAL: %s department has EXCEEDED budget by %.1f%% - spent ₱%s against ₱%s allocated (₱%s over budget). " .
          "Require immediate investigation, spending freeze, and corrective action plan from department head.",
          $dept['department'],
          $overage_pct,
          number_format($dept['used'], 2),
          number_format($dept['allocated'], 2),
          number_format($overage_amount, 2)
        );
      } elseif ($dept['utilization'] > 90) {
        $remaining_dept = $dept['allocated'] - $dept['used'];
        $analytics['recommended_actions'][] = sprintf(
          "⚠️ WARNING: %s department at %.1f%% utilization - only ₱%s remaining of ₱%s allocated. " .
          "Implement immediate spending controls to prevent budget overrun.",
          $dept['department'],
          $dept['utilization'],
          number_format($remaining_dept, 2),
          number_format($dept['allocated'], 2)
        );
      } elseif ($dept['utilization'] > 75) {
        $analytics['recommended_actions'][] = sprintf(
          "📊 MONITOR: %s department at %.1f%% utilization. Trending toward high utilization - increase oversight.",
          $dept['department'],
          $dept['utilization']
        );
      }
    }
    
  } catch (Exception $e) {
    error_log("Budget analytics error: " . $e->getMessage());
  }
  
  return $analytics;
}

/**
 * AI-Powered Overdue Analysis
 * 
 * ENHANCED WITH:
 * - Clear aging thresholds and collection probability
 * - Detailed reasoning for risk assessment
 * - Specific collection strategies by risk level
 * 
 * RISK LEVEL THRESHOLDS:
 * - LOW RISK: Average days overdue < 30 days
 *   Basis: Recent overdues, high collection probability (85%+)
 * - MEDIUM RISK: Average days overdue 30-60 days
 *   Basis: Moderate aging, declining collection probability (70-85%)
 * - HIGH RISK: Average days overdue > 60 days
 *   Basis: Severely aged, low collection probability (<70%)
 * 
 * COLLECTION PROBABILITY CALCULATION:
 * Base rate calculated from historical collection success
 * Adjusted by days overdue:
 * - 0-30 days: Base rate (no reduction)
 * - 31-60 days: Base rate minus 10%
 * - 61-90 days: Base rate minus 25%
 * - 90+ days: Base rate minus 40%
 * 
 * Basis: Industry research shows sharp decline in collectability after 60 days
 */
function getOverdueAnalytics() {
  global $conn;
  
  $analytics = [
    'average_days_overdue' => 0,
    'collection_probability' => 0,
    'trend' => 'stable',
    'insight' => '',
    'risk_level' => 'low',
    'risk_reasoning' => '',  // NEW: Detailed explanation of risk assessment
    'recommended_actions' => [],
    'aging_analysis' => []
  ];
  
  try {
    // Aging analysis
    $agingSql = "SELECT 
                   CASE 
                     WHEN DATEDIFF(CURDATE(), due_date) <= 30 THEN '0-30 days'
                     WHEN DATEDIFF(CURDATE(), due_date) <= 60 THEN '31-60 days'
                     WHEN DATEDIFF(CURDATE(), due_date) <= 90 THEN '61-90 days'
                     ELSE '90+ days'
                   END as age_bracket,
                   COUNT(*) as count,
                   COALESCE(SUM(amount_due - amount_paid), 0) as total_amount,
                   COALESCE(AVG(DATEDIFF(CURDATE(), due_date)), 0) as avg_days
                 FROM collections
                 WHERE payment_status != 'Paid' AND due_date < CURDATE()
                 GROUP BY age_bracket
                 ORDER BY avg_days ASC";
    
    $agingResult = $conn->query($agingSql);
    
    if ($agingResult && $agingResult->num_rows > 0) {
      while ($row = $agingResult->fetch_assoc()) {
        $analytics['aging_analysis'][] = [
          'bracket' => $row['age_bracket'],
          'count' => (int)$row['count'],
          'amount' => (float)$row['total_amount'],
          'avg_days' => round((float)$row['avg_days'], 0)
        ];
      }
    }
    
    // Calculate average days overdue
    $avgSql = "SELECT COALESCE(AVG(DATEDIFF(CURDATE(), due_date)), 0) as avg_overdue
               FROM collections
               WHERE payment_status != 'Paid' AND due_date < CURDATE()";
    
    $avgResult = $conn->query($avgSql);
    
    if ($avgResult && $avgData = $avgResult->fetch_assoc()) {
      $analytics['average_days_overdue'] = round((float)$avgData['avg_overdue'], 0);
    }
    
    // Calculate collection probability based on historical data and aging
    $historicalSql = "SELECT 
                        COUNT(CASE WHEN payment_status = 'Paid' THEN 1 END) as eventually_paid,
                        COUNT(*) as total
                      FROM collections
                      WHERE due_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $histResult = $conn->query($historicalSql);
    
    if ($histResult && $histData = $histResult->fetch_assoc()) {
      if ($histData['total'] > 0) {
        $baseCollectionRate = ($histData['eventually_paid'] / $histData['total']) * 100;
        
        // COLLECTION PROBABILITY FORMULA:
        // Base rate = Historical collection success rate
        // Adjusted by aging factor based on industry research:
        // - Accounts 0-30 days overdue: No reduction (recent, high probability)
        // - Accounts 31-60 days overdue: -10% (collection effort intensifies)
        // - Accounts 61-90 days overdue: -25% (significant decline in collectability)
        // - Accounts 90+ days overdue: -40% (very low collection probability)
        
        if ($analytics['average_days_overdue'] > 90) {
          $analytics['collection_probability'] = max(0, $baseCollectionRate - 40);
        } elseif ($analytics['average_days_overdue'] > 60) {
          $analytics['collection_probability'] = max(0, $baseCollectionRate - 25);
        } elseif ($analytics['average_days_overdue'] > 30) {
          $analytics['collection_probability'] = max(0, $baseCollectionRate - 10);
        } else {
          $analytics['collection_probability'] = $baseCollectionRate;
        }
        
        $analytics['collection_probability'] = round($analytics['collection_probability'], 1);
      }
    }
    
    // Count accounts in each aging bucket for detailed analysis
    $agingCounts = [
      '0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0
    ];
    $agingAmounts = [
      '0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0
    ];
    
    foreach ($analytics['aging_analysis'] as $bucket) {
      $key = str_replace(' days', '', $bucket['bracket']);
      if (isset($agingCounts[$key])) {
        $agingCounts[$key] = $bucket['count'];
        $agingAmounts[$key] = $bucket['amount'];
      }
    }
    
    // ENHANCED RISK ASSESSMENT WITH THRESHOLDS AND DETAILED REASONING
    //
    // THRESHOLD DEFINITIONS AND BASIS:
    // - LOW RISK (< 30 days average):
    //   * Recent overdues with high collection probability (typically 85%+)
    //   * Standard follow-up processes effective
    //   * Cash flow impact minimal
    //
    // - MEDIUM RISK (30-60 days average):
    //   * Moderate aging with declining collection probability (70-85%)
    //   * Requires intensified collection efforts
    //   * Noticeable cash flow impact
    //
    // - HIGH RISK (> 60 days average):
    //   * Severely aged with low collection probability (<70%)
    //   * Industry data shows sharp decline after 60 days
    //   * Significant cash flow impact and bad debt risk
    
    if ($analytics['average_days_overdue'] > 60) {
      $analytics['risk_level'] = 'high';
      $analytics['risk_reasoning'] = sprintf(
        "HIGH RISK: Average overdue period of %d days significantly exceeds the 60-day critical threshold. " .
        "Collection probability estimated at %.1f%% (base historical rate %.1f%% minus 25-40%% aging penalty). " .
        "Industry research consistently shows that collection success rates drop sharply after 60 days overdue, " .
        "with accounts over 90 days having less than 50%% probability of full collection. " .
        "Aging breakdown: %d accounts 0-30 days (₱%s), %d accounts 31-60 days (₱%s), " .
        "%d accounts 61-90 days (₱%s), %d accounts 90+ days (₱%s). " .
        "Accounts in 90+ category require immediate legal action consideration as recovery probability diminishes rapidly. " .
        "This level represents substantial bad debt risk and significant negative impact on cash flow and working capital.",
        $analytics['average_days_overdue'],
        $analytics['collection_probability'],
        $histData['total'] > 0 ? ($histData['eventually_paid'] / $histData['total']) * 100 : 0,
        $agingCounts['0-30'], number_format($agingAmounts['0-30'], 2),
        $agingCounts['31-60'], number_format($agingAmounts['31-60'], 2),
        $agingCounts['61-90'], number_format($agingAmounts['61-90'], 2),
        $agingCounts['90+'], number_format($agingAmounts['90+'], 2)
      );
      $analytics['insight'] = "High risk: Average {$analytics['average_days_overdue']} days overdue. {$analytics['collection_probability']}% collection probability.";
      
      $analytics['recommended_actions'][] = "🚨 ESCALATE: Immediately escalate to senior management - this requires executive-level attention and emergency collection protocol";
      
      if ($agingCounts['90+'] > 0) {
        $analytics['recommended_actions'][] = sprintf(
          "⚖️ LEGAL ACTION: For %d accounts over 90 days overdue (₱%s total), issue final demand letters with explicit legal action warning and 7-day payment deadline",
          $agingCounts['90+'],
          number_format($agingAmounts['90+'], 2)
        );
        $analytics['recommended_actions'][] = "Consider engaging professional collection agency for accounts over 120 days - recovery rate improves with specialist intervention";
      }
      
      if ($agingCounts['61-90'] > 0) {
        $analytics['recommended_actions'][] = sprintf(
          "📞 INTENSIVE FOLLOW-UP: For %d accounts 61-90 days overdue (₱%s), implement daily collection calls and document all communication",
          $agingCounts['61-90'],
          number_format($agingAmounts['61-90'], 2)
        );
      }
      
      $analytics['recommended_actions'][] = "💰 PAYMENT PLANS: Offer structured settlement options (e.g., 50% immediate payment + 6-month installments for remainder) - recovering partial payment is better than write-off";
      $analytics['recommended_actions'][] = "📋 POLICY REVIEW: Comprehensive review of credit approval and collection policies to prevent future high-risk aging";
      $analytics['recommended_actions'][] = "💵 WRITE-OFF PREPARATION: Begin assessing which accounts may require bad debt write-off and establish reserves accordingly";
      $analytics['recommended_actions'][] = "📊 CASH FLOW IMPACT: Quantify cash flow impact and adjust financial projections - these aged receivables should not be counted as liquid assets";
      
    } elseif ($analytics['average_days_overdue'] > 30) {
      $analytics['risk_level'] = 'medium';
      $analytics['risk_reasoning'] = sprintf(
        "MODERATE RISK: Average overdue period of %d days falls in the 30-60 day moderate risk range. " .
        "Collection probability estimated at %.1f%% (base historical rate %.1f%% minus 10%% aging penalty). " .
        "While not yet critical, accounts in this range show declining collection probability with each passing week. " .
        "Industry data indicates collection success drops 10-15%% for each additional month of delay beyond 30 days. " .
        "Aging breakdown: %d accounts 0-30 days (₱%s), %d accounts 31-60 days (₱%s), " .
        "%d accounts 61-90 days (₱%s), %d accounts 90+ days (₱%s). " .
        "Proactive and intensified collection efforts now can prevent escalation to high-risk category. " .
        "Accounts still have good recovery potential with systematic follow-up and professional collection approach.",
        $analytics['average_days_overdue'],
        $analytics['collection_probability'],
        $histData['total'] > 0 ? ($histData['eventually_paid'] / $histData['total']) * 100 : 0,
        $agingCounts['0-30'], number_format($agingAmounts['0-30'], 2),
        $agingCounts['31-60'], number_format($agingAmounts['31-60'], 2),
        $agingCounts['61-90'], number_format($agingAmounts['61-90'], 2),
        $agingCounts['90+'], number_format($agingAmounts['90+'], 2)
      );
      $analytics['insight'] = "Moderate risk: {$analytics['average_days_overdue']} days average overdue. {$analytics['collection_probability']}% likely to collect.";
      
      $analytics['recommended_actions'][] = "📧 FORMAL DEMANDS: Send formal demand letters to all accounts 30+ days overdue - shift from friendly reminders to serious collection tone";
      $analytics['recommended_actions'][] = sprintf(
        "📞 SYSTEMATIC CALLS: Implement structured weekly collection call program for all %d overdue accounts - document every contact attempt and response",
        array_sum($agingCounts)
      );
      $analytics['recommended_actions'][] = "📝 DOCUMENTATION: Maintain detailed records of all collection communication - critical for potential legal proceedings if accounts age further";
      $analytics['recommended_actions'][] = "💳 PAYMENT OPTIONS: Offer early settlement incentives (e.g., 5% discount for full payment within 7 days, 3% for payment within 14 days)";
      $analytics['recommended_actions'][] = "⏰ ESCALATION PATH: Establish clear timeline - if no response within 15 days, escalate to supervisor; within 30 days, move to formal collections";
      $analytics['recommended_actions'][] = "🔒 CREDIT HOLD: Place future orders on credit hold for customers with balances over 45 days until account brought current";
      $analytics['recommended_actions'][] = "📊 TREND MONITORING: Weekly review of aging report to catch accounts moving from 30-60 days to 60-90 days for immediate intensive action";
      
    } else {
      $analytics['risk_level'] = 'low';
      $analytics['risk_reasoning'] = sprintf(
        "LOW RISK: Average overdue period of %d days is within acceptable range (< 30 days). " .
        "Collection probability estimated at %.1f%% based on historical collection rate with no aging penalty applied. " .
        "Recent overdues (less than 30 days past due) typically resolve through standard collection processes. " .
        "Aging breakdown: %d accounts 0-30 days (₱%s), %d accounts 31-60 days (₱%s), " .
        "%d accounts 61-90 days (₱%s), %d accounts 90+ days (₱%s). " .
        "At this level, normal business operations and standard accounts receivable follow-up is appropriate. " .
        "Focus should be on preventing accounts from aging into higher risk categories through timely, consistent follow-up. " .
        "Maintain systematic reminder processes to keep accounts from progressing beyond 30 days overdue.",
        $analytics['average_days_overdue'],
        $analytics['collection_probability'],
        $agingCounts['0-30'], number_format($agingAmounts['0-30'], 2),
        $agingCounts['31-60'], number_format($agingAmounts['31-60'], 2),
        $agingCounts['61-90'], number_format($agingAmounts['61-90'], 2),
        $agingCounts['90+'], number_format($agingAmounts['90+'], 2)
      );
      $analytics['insight'] = "Low risk: Recent overdues. {$analytics['collection_probability']}% collection probability.";
      
      $analytics['recommended_actions'][] = "✉️ FRIENDLY REMINDERS: Send courteous payment reminder emails to all overdue accounts - maintain positive customer relationships";
      $analytics['recommended_actions'][] = "📞 COURTESY CALLS: Follow up with friendly phone calls for accounts 15+ days overdue to check if there are any issues or disputes";
      $analytics['recommended_actions'][] = "⚠️ EARLY DETECTION: Monitor closely to catch any accounts approaching 30 days overdue threshold for more intensive follow-up";
      $analytics['recommended_actions'][] = "🔄 PROCESS OPTIMIZATION: Document successful collection strategies and automate reminder sequences for efficiency";
      $analytics['recommended_actions'][] = "📋 DISPUTE RESOLUTION: Quickly address any invoice disputes or payment issues before they cause account aging";
      $analytics['recommended_actions'][] = "📈 TREND ANALYSIS: Track week-over-week changes in average days overdue to catch emerging patterns early";
    }
    
  } catch (Exception $e) {
    error_log("Overdue analytics error: " . $e->getMessage());
  }
  
  return $analytics;
}

// Chart data functions
function getCollectionsByStatus() {
  global $conn;
  $data = ['Paid' => 0, 'Partial' => 0, 'Unpaid' => 0];
  
  try {
    $sql = "SELECT payment_status, COUNT(*) as count FROM collections GROUP BY payment_status";
    $res = $conn->query($sql);
    
    if ($res && $res->num_rows > 0) {
      while ($r = $res->fetch_assoc()) {
        if (isset($data[$r['payment_status']])) {
          $data[$r['payment_status']] = (int)$r['count'];
        }
      }
    }
  } catch (Exception $e) {
    error_log("Collections by status error: " . $e->getMessage());
  }
  
  return $data;
}

function getBudgetByDepartment() {
  global $conn;
  $labels = [];
  $allocated = [];
  $used = [];
  
  try {
    $sql = "SELECT department, 
                   COALESCE(SUM(amount_allocated), 0) as total_allocated,
                   COALESCE(SUM(amount_used), 0) as total_used
            FROM budgets 
            GROUP BY department 
            ORDER BY total_allocated DESC 
            LIMIT 6";
    $res = $conn->query($sql);
    
    if ($res && $res->num_rows > 0) {
      while ($r = $res->fetch_assoc()) {
        $labels[] = $r['department'];
        $allocated[] = (float)$r['total_allocated'];
        $used[] = (float)$r['total_used'];
      }
    }
  } catch (Exception $e) {
    error_log("Budget by department error: " . $e->getMessage());
  }
  
  return ['labels' => $labels, 'allocated' => $allocated, 'used' => $used];
}

function getMonthlyCollectionTrends() {
  global $conn;
  $labels = [];
  $amounts = [];
  
  try {
    $sql = "SELECT DATE_FORMAT(billing_date, '%Y-%m') as month,
                   COALESCE(SUM(amount_paid), 0) as total
            FROM collections
            WHERE billing_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month ASC";
    $res = $conn->query($sql);
    
    if ($res && $res->num_rows > 0) {
      while ($r = $res->fetch_assoc()) {
        $labels[] = date('M Y', strtotime($r['month'] . '-01'));
        $amounts[] = (float)$r['total'];
      }
    }
  } catch (Exception $e) {
    error_log("Monthly trends error: " . $e->getMessage());
  }
  
  return ['labels' => $labels, 'amounts' => $amounts];
}

function getBudgetUtilization() {
  global $conn;
  $data = [];
  
  try {
    $sql = "SELECT period, 
                   COALESCE(SUM(amount_allocated), 0) as allocated,
                   COALESCE(SUM(amount_used), 0) as used
            FROM budgets 
            WHERE approval_status = 'Approved'
            GROUP BY period";
    $res = $conn->query($sql);
    
    if ($res && $res->num_rows > 0) {
      while ($r = $res->fetch_assoc()) {
        $data[$r['period']] = [
          'allocated' => (float)$r['allocated'],
          'used' => (float)$r['used']
        ];
      }
    }
  } catch (Exception $e) {
    error_log("Budget utilization error: " . $e->getMessage());
  }
  
  return $data;
}

// DETAILED BREAKDOWN FUNCTIONS
function getCollectionsBreakdown() {
  global $conn;
  
  $breakdown = [
    'total_collected' => ['amount' => 0, 'records' => []],
    'total_pending' => ['amount' => 0, 'records' => []],
    'total_overdue' => ['amount' => 0, 'records' => []],
    'summary_stats' => []
  ];
  
  $today = date('Y-m-d');
  
  try {
    $sql = "SELECT * FROM collections ORDER BY due_date ASC";
    $res = $conn->query($sql);
    
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $amountPaid = (float)$r['amount_paid'];
        if ($amountPaid > 0) {
          $breakdown['total_collected']['amount'] += $amountPaid;
          $breakdown['total_collected']['records'][] = [
            'client' => $r['client_name'],
            'invoice' => $r['invoice_no'],
            'amount_due' => $r['amount_due'],
            'amount_paid' => $amountPaid,
            'payment_status' => $r['payment_status'],
            'billing_date' => $r['billing_date'],
            'due_date' => $r['due_date']
          ];
        }
        
        $pending = max(0, (float)$r['amount_due'] - (float)$r['amount_paid']);
        if ($pending > 0) {
          $breakdown['total_pending']['amount'] += $pending;
          $breakdown['total_pending']['records'][] = [
            'client' => $r['client_name'],
            'invoice' => $r['invoice_no'],
            'amount_due' => $r['amount_due'],
            'amount_paid' => $r['amount_paid'],
            'pending_amount' => $pending,
            'payment_status' => $r['payment_status'],
            'due_date' => $r['due_date'],
            'days_until_due' => (strtotime($r['due_date']) - strtotime($today)) / 86400
          ];
        }
        
        if ($r['payment_status'] !== 'Paid' && $r['due_date'] < $today && $pending > 0) {
          $breakdown['total_overdue']['amount'] += $pending;
          $breakdown['total_overdue']['records'][] = [
            'client' => $r['client_name'],
            'invoice' => $r['invoice_no'],
            'amount_due' => $r['amount_due'],
            'amount_paid' => $r['amount_paid'],
            'overdue_amount' => $pending,
            'payment_status' => $r['payment_status'],
            'due_date' => $r['due_date'],
            'days_overdue' => floor((strtotime($today) - strtotime($r['due_date'])) / 86400),
            'penalty' => $r['penalty']
          ];
        }
      }
      
      $breakdown['summary_stats'] = [
        'total_invoices' => $res->num_rows,
        'paid_count' => count(array_filter($breakdown['total_collected']['records'], function($r) {
          return $r['payment_status'] === 'Paid';
        })),
        'partial_count' => count(array_filter($breakdown['total_pending']['records'], function($r) {
          return $r['payment_status'] === 'Partial';
        })),
        'unpaid_count' => count(array_filter($breakdown['total_pending']['records'], function($r) {
          return $r['payment_status'] === 'Unpaid';
        })),
        'overdue_count' => count($breakdown['total_overdue']['records'])
      ];
    }
  } catch (Exception $e) {
    error_log("Collections breakdown error: " . $e->getMessage());
  }
  
  return $breakdown;
}

function getBudgetBreakdown() {
  global $conn;
  
  $breakdown = [
    'total_budget' => ['amount' => 0, 'records' => []],
    'total_used' => ['amount' => 0, 'records' => []],
    'total_remaining' => ['amount' => 0, 'records' => []],
    'by_department' => [],
    'by_period' => [],
    'summary_stats' => []
  ];
  
  try {
    $sql = "SELECT * FROM budgets ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $allocated = (float)$row['amount_allocated'];
        $used = (float)$row['amount_used'];
        $remaining = $allocated - $used;
        $utilizationPct = $allocated > 0 ? ($used / $allocated) * 100 : 0;
        
        $breakdown['total_budget']['amount'] += $allocated;
        $breakdown['total_budget']['records'][] = [
          'department' => $row['department'],
          'cost_center' => $row['cost_center'],
          'period' => $row['period'],
          'allocated' => $allocated,
          'approval_status' => $row['approval_status'],
          'approved_by' => $row['approved_by']
        ];
        
        $breakdown['total_used']['amount'] += $used;
        $breakdown['total_used']['records'][] = [
          'department' => $row['department'],
          'cost_center' => $row['cost_center'],
          'period' => $row['period'],
          'used' => $used,
          'allocated' => $allocated,
          'utilization_pct' => $utilizationPct
        ];
        
        $breakdown['total_remaining']['amount'] += $remaining;
        $breakdown['total_remaining']['records'][] = [
          'department' => $row['department'],
          'cost_center' => $row['cost_center'],
          'period' => $row['period'],
          'remaining' => $remaining,
          'allocated' => $allocated,
          'remaining_pct' => $allocated > 0 ? ($remaining / $allocated) * 100 : 0
        ];
        
        if (!isset($breakdown['by_department'][$row['department']])) {
          $breakdown['by_department'][$row['department']] = [
            'allocated' => 0,
            'used' => 0,
            'remaining' => 0,
            'count' => 0
          ];
        }
        $breakdown['by_department'][$row['department']]['allocated'] += $allocated;
        $breakdown['by_department'][$row['department']]['used'] += $used;
        $breakdown['by_department'][$row['department']]['remaining'] += $remaining;
        $breakdown['by_department'][$row['department']]['count']++;
        
        if (!isset($breakdown['by_period'][$row['period']])) {
          $breakdown['by_period'][$row['period']] = [
            'allocated' => 0,
            'used' => 0,
            'remaining' => 0,
            'count' => 0
          ];
        }
        $breakdown['by_period'][$row['period']]['allocated'] += $allocated;
        $breakdown['by_period'][$row['period']]['used'] += $used;
        $breakdown['by_period'][$row['period']]['remaining'] += $remaining;
        $breakdown['by_period'][$row['period']]['count']++;
      }
      
      $breakdown['summary_stats'] = [
        'total_budgets' => $result->num_rows,
        'approved_count' => 0,
        'pending_count' => 0,
        'rejected_count' => 0,
        'overall_utilization' => $breakdown['total_budget']['amount'] > 0 ? 
          ($breakdown['total_used']['amount'] / $breakdown['total_budget']['amount']) * 100 : 0
      ];
      
      foreach ($breakdown['total_budget']['records'] as $rec) {
        if ($rec['approval_status'] === 'Approved') $breakdown['summary_stats']['approved_count']++;
        elseif ($rec['approval_status'] === 'Pending') $breakdown['summary_stats']['pending_count']++;
        elseif ($rec['approval_status'] === 'Rejected') $breakdown['summary_stats']['rejected_count']++;
      }
    }
  } catch (Exception $e) {
    error_log("Budget breakdown error: " . $e->getMessage());
  }
  
  return $breakdown;
}

// Get all data
$collectionsSummary = getCollectionsSummary();
$budgetSummary = getBudgetSummary();
$collectionsBreakdown = getCollectionsBreakdown();
$budgetBreakdown = getBudgetBreakdown();

// Get AI analytics
$collectionsAnalytics = getCollectionsAnalytics();
$budgetAnalytics = getBudgetAnalytics();
$overdueAnalytics = getOverdueAnalytics();

// Get chart data
$collectionsByStatus = getCollectionsByStatus();
$budgetByDepartment = getBudgetByDepartment();
$monthlyTrends = getMonthlyCollectionTrends();
$budgetUtilization = getBudgetUtilization();

// Format currency function
function formatCurrency($amount) {
  return '₱' . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AI-Enhanced Dashboard - Financial System</title>
  
  <!-- ADD THIS BLOCK -->
  <script>
    // Pass PHP session configuration to JavaScript
    window.SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT * 1000; ?>; // Convert to milliseconds
  </script>
  <!-- END OF ADDED BLOCK -->

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css" />
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
  
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
      --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      --light-bg: #f8f9fa;
      --card-bg: #ffffff;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --border-color: rgba(0, 0, 0, 0.08);
      --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
      --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
    }
    
    body {
      background: linear-gradient(to bottom right, #f1f5f9, #e2e8f0);
      min-height: 100vh;
      color: var(--text-primary);
    }
    
    .main-content {
      padding: 2rem;
      position: relative;
      z-index: auto;
    }

    .container-fluid {
      position: relative;
      z-index: auto;
    }
    
    h2, h4 {
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid var(--border-color);
    }
    
    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
    }
    
    /* Enhanced Cards */
    .stat-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 2rem;
      height: 100%;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      z-index: 1;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--card-border, #cbd5e1);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg);
      border-color: rgba(0, 0, 0, 0.12);
    }
    
    .stat-card:hover::before {
      opacity: 1;
    }
    
    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      margin-bottom: 1.25rem;
      background: var(--icon-bg, #e2e8f0);
      color: var(--icon-color, #475569);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .stat-label {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
    }
    
    .stat-value {
      font-size: 2rem;
      font-weight: 800;
      line-height: 1;
      margin-bottom: 0.5rem;
      color: #1e293b;
    }
    
    .stat-value.value-hidden {
      letter-spacing: 0.2rem;
      font-size: 1.5rem;
    }
    
    .stat-trend {
      font-size: 0.875rem;
      color: var(--text-secondary);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    /* AI Insights Badge */
    .ai-badge {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 0.35rem 0.75rem;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
      z-index: 100;
      display: flex;
      align-items: center;
      gap: 0.4rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .ai-badge:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
    }
    
    .ai-badge i {
      font-size: 0.8rem;
    }
    
    /* Unified AI Insights Modal */
    .ai-insights-modal {
      position: fixed !important;
      top: 50% !important;
      left: 50% !important;
      transform: translate(-50%, -50%) scale(0.9) !important;
      background: white;
      border: 2px solid rgba(102, 126, 234, 0.3);
      border-radius: 20px;
      padding: 2rem;
      width: 600px;
      max-width: 90vw;
      max-height: 85vh;
      overflow-y: auto;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
      z-index: 99999 !important;
      opacity: 0;
      visibility: hidden;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .ai-insights-modal.show {
      opacity: 1;
      visibility: visible;
      transform: translate(-50%, -50%) scale(1) !important;
    }
    
    .ai-insights-overlay {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      z-index: 99998 !important;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      pointer-events: none;
    }
    
    .ai-insights-overlay.show {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }
    
    .modal-header-custom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid rgba(102, 126, 234, 0.2);
    }
    
    .modal-title-custom {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      color: #667eea;
      font-weight: 700;
      font-size: 1.5rem;
    }
    
    .modal-title-custom i {
      font-size: 1.75rem;
    }
    
    .modal-close-btn {
      background: none;
      border: none;
      color: var(--text-secondary);
      font-size: 1.75rem;
      cursor: pointer;
      padding: 0;
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }
    
    .modal-close-btn:hover {
      background: rgba(0, 0, 0, 0.05);
      color: var(--text-primary);
    }
    
    .insight-section {
      margin-bottom: 2rem;
    }
    
    .insight-section:last-child {
      margin-bottom: 0;
    }
    
    .insight-section-title {
      font-size: 1rem;
      font-weight: 600;
      color: #667eea;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .insight-card {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
      border-left: 4px solid #667eea;
      border-radius: 12px;
      padding: 1.25rem;
      margin-bottom: 1rem;
    }
    
    .insight-text {
      font-size: 0.95rem;
      color: var(--text-primary);
      line-height: 1.6;
      margin-bottom: 0;
    }
    
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
      margin-top: 1rem;
    }
    
    .metric-item {
      background: rgba(0, 0, 0, 0.02);
      border-radius: 10px;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .metric-label {
      font-size: 0.8rem;
      color: var(--text-secondary);
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .metric-value {
      font-size: 1.25rem;
      color: var(--text-primary);
      font-weight: 700;
    }
    
    .risk-badge {
      display: inline-block;
      padding: 0.4rem 0.9rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .risk-low {
      background: #d1fae5;
      color: #065f46;
    }
    
    .risk-medium {
      background: #fef3c7;
      color: #92400e;
    }
    
    .risk-high {
      background: #fee2e2;
      color: #991b1b;
    }
    
    .action-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .action-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 0.75rem;
      background: rgba(0, 0, 0, 0.02);
      border-radius: 8px;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
      color: var(--text-primary);
    }
    
    .action-item:last-child {
      margin-bottom: 0;
    }
    
    .action-item i {
      color: #667eea;
      margin-top: 0.15rem;
      flex-shrink: 0;
      font-size: 1rem;
    }
    
    .data-quality-alert {
      background: #fef3c7;
      border-left: 4px solid #f59e0b;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      font-size: 0.875rem;
      color: #92400e;
    }
    
    .data-quality-alert i {
      margin-right: 0.5rem;
    }
    
    /* Privacy Toggle */
    .privacy-toggle {
      position: absolute;
      bottom: 1rem;
      right: 1rem;
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: rgba(0, 0, 0, 0.04);
      border: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 100;
      color: var(--text-secondary);
    }
    
    .privacy-toggle:hover {
      background: rgba(0, 0, 0, 0.08);
      transform: scale(1.1);
      color: var(--text-primary);
    }
    
    /* Chart Cards */
    .chart-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 2rem;
      height: 100%;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm);
      position: relative;
    }
    
    .chart-card:hover {
      box-shadow: var(--shadow-md);
      border-color: rgba(0, 0, 0, 0.12);
    }
    
    .chart-title {
      font-size: 1.125rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: var(--text-primary);
    }
    
    .chart-container {
      position: relative;
      height: 300px;
    }
    
    /* Module Cards */
    .module-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.75rem;
      text-decoration: none;
      color: var(--text-primary);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      height: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }
    
    .module-card::after {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100px;
      height: 100px;
      background: radial-gradient(circle, rgba(102, 126, 234, 0.08) 0%, transparent 70%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .module-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
      border-color: rgba(0, 0, 0, 0.12);
      color: var(--text-primary);
    }
    
    .module-card:hover::after {
      opacity: 1;
    }
    
    .module-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1rem;
      background: var(--icon-bg, #e2e8f0);
      color: var(--icon-color, #475569);
    }
    
    .module-title {
      font-size: 1.125rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: var(--text-primary);
    }
    
    .module-description {
      font-size: 0.875rem;
      color: var(--text-secondary);
      line-height: 1.6;
      flex-grow: 1;
    }
    
    /* Trend Indicator */
    .trend-indicator {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.25rem 0.5rem;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .trend-up {
      background: #d1fae5;
      color: #065f46;
    }
    
    .trend-down {
      background: #fee2e2;
      color: #991b1b;
    }
    
    .trend-stable {
      background: #e0e7ff;
      color: #4338ca;
    }
    
    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.9);
      }
      to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
      }
    }
    
    .animate-in {
      animation: fadeInUp 0.6s ease-out;
    }
    
    .animate-delay-1 { animation-delay: 0.1s; animation-fill-mode: both; }
    .animate-delay-2 { animation-delay: 0.2s; animation-fill-mode: both; }
    .animate-delay-3 { animation-delay: 0.3s; animation-fill-mode: both; }
    .animate-delay-4 { animation-delay: 0.4s; animation-fill-mode: both; }
    .animate-delay-5 { animation-delay: 0.5s; animation-fill-mode: both; }
    .animate-delay-6 { animation-delay: 0.6s; animation-fill-mode: both; }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: var(--light-bg);
    }
    
    ::-webkit-scrollbar-thumb {
      background: rgba(0, 0, 0, 0.1);
      border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(0, 0, 0, 0.2);
    }
    
    /* Button Styles */
    .btn-outline-primary {
      border: 2px solid rgba(102, 126, 234, 0.5);
      color: #667eea;
      font-weight: 600;
      padding: 0.5rem 1.25rem;
      border-radius: 10px;
      transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
      background: var(--primary-gradient);
      border-color: transparent;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }
  </style>
</head>
<body>

  <?php include 'sidebar_navbar.php'; ?>

  <!-- AI Insights Overlay -->
  <div class="ai-insights-overlay" id="aiInsightsOverlay" onclick="closeAIModal()"></div>

  <!-- Unified AI Insights Modal -->
  <div class="ai-insights-modal" id="aiInsightsModal">
    <div class="modal-header-custom">
      <div class="modal-title-custom">
        <i class="bi bi-robot"></i>
        <span id="modalTitle">AI Financial Insights</span>
      </div>
      <button class="modal-close-btn" onclick="closeAIModal()">
        <i class="bi bi-x"></i>
      </button>
    </div>
    
    <div id="modalContent">
      <!-- Content will be dynamically loaded here -->
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid mt-4 px-4">
      
      <?php if (isset($_GET['access_denied'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <strong>Access Denied!</strong> You don't have permission to access that resource.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <!-- Page Header -->
      <div class="mb-5">
        <h2 class="fw-bold mb-2">
          <i class="bi bi-robot me-2" style="color: #667eea;"></i>
          AI-Enhanced Financial Dashboard
        </h2>
        <p class="text-secondary mb-0">Monitor your financial performance with intelligent insights and predictions</p>
      </div>
      
      <!-- Collections Summary -->
      <div class="section-header">
        <div class="section-title">Collections Overview</div>
        <a href="financial_collections.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-right me-1"></i> View Details
        </a>
      </div>
      
      <div class="row g-4 mb-5">
        <!-- Total Collected Card -->
        <div class="col-md-4 animate-in animate-delay-1">
          <div class="stat-card" style="--card-border: #10b981; --icon-bg: #d1fae5; --icon-color: #065f46;">
            <span class="ai-badge" onclick="showAIInsights('collections')">
              <i class="bi bi-stars"></i> AI Insights
            </span>
            <div class="stat-icon">
              <i class="bi bi-cash-coin"></i>
            </div>
            <div class="stat-label">Total Collected</div>
            <div class="stat-value value-hidden" id="value-collected" data-value="<?php echo formatCurrency($collectionsSummary['total_collected']); ?>">
              ₱ ••••••••
            </div>
            <div class="stat-trend">
              <?php if ($collectionsAnalytics['trend'] === 'increasing'): ?>
                <span class="trend-indicator trend-up">
                  <i class="bi bi-arrow-up"></i> <?php echo abs($collectionsAnalytics['trend_percentage']); ?>%
                </span>
              <?php elseif ($collectionsAnalytics['trend'] === 'decreasing'): ?>
                <span class="trend-indicator trend-down">
                  <i class="bi bi-arrow-down"></i> <?php echo abs($collectionsAnalytics['trend_percentage']); ?>%
                </span>
              <?php else: ?>
                <span class="trend-indicator trend-stable">
                  <i class="bi bi-dash"></i> Stable
                </span>
              <?php endif; ?>
            </div>
            
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'collected')" title="Show Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
        </div>
        
        <!-- Pending Collections Card -->
        <div class="col-md-4 animate-in animate-delay-2">
          <div class="stat-card" style="--card-border: #f59e0b; --icon-bg: #fef3c7; --icon-color: #92400e;">
            <span class="ai-badge" onclick="showAIInsights('pending')">
              <i class="bi bi-stars"></i> AI Insights
            </span>
            <div class="stat-icon">
              <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-label">Pending Collections</div>
            <div class="stat-value value-hidden" id="value-pending" data-value="<?php echo formatCurrency($collectionsSummary['total_pending']); ?>">
              ₱ ••••••••
            </div>
            <div class="stat-trend">
              <i class="bi bi-clock"></i>
              <span>Awaiting payment</span>
            </div>
            
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'pending')" title="Show Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
        </div>
        
        <!-- Overdue Collections Card -->
        <div class="col-md-4 animate-in animate-delay-3">
          <div class="stat-card" style="--card-border: #ef4444; --icon-bg: #fee2e2; --icon-color: #991b1b;">
            <span class="ai-badge" onclick="showAIInsights('overdue')">
              <i class="bi bi-stars"></i> AI Insights
            </span>
            <div class="stat-icon">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-label">Overdue Collections</div>
            <div class="stat-value value-hidden" id="value-overdue" data-value="<?php echo formatCurrency($collectionsSummary['total_overdue']); ?>">
              ₱ ••••••••
            </div>
            <div class="stat-trend">
              <span class="risk-badge risk-<?php echo $overdueAnalytics['risk_level']; ?>">
                <?php echo strtoupper($overdueAnalytics['risk_level']); ?> RISK
              </span>
            </div>
            
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'overdue')" title="Show Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Charts Row 1: Collections -->
      <div class="row g-4 mb-5">
        <div class="col-md-6 animate-in animate-delay-4">
          <div class="chart-card">
            <div class="chart-title">Collections by Status</div>
            <div class="chart-container">
              <canvas id="collectionsStatusChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-6 animate-in animate-delay-5">
          <div class="chart-card">
            <div class="chart-title">Monthly Collection Trends</div>
            <div class="chart-container">
              <canvas id="monthlyTrendsChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Budget Summary -->
      <div class="section-header">
        <div class="section-title">Budget Overview</div>
        <a href="financial_budgeting.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-right me-1"></i> View Details
        </a>
      </div>
      
      <div class="row g-4 mb-5">
        <!-- Total Budget Card -->
        <div class="col-md-4 animate-in animate-delay-1">
          <div class="stat-card" style="--card-border: #3b82f6; --icon-bg: #dbeafe; --icon-color: #1e40af;">
            <span class="ai-badge" onclick="showAIInsights('budget')">
              <i class="bi bi-stars"></i> AI Insights
            </span>
            <div class="stat-icon">
              <i class="bi bi-pie-chart"></i>
            </div>
            <div class="stat-label">Total Budget</div>
            <div class="stat-value value-hidden" id="value-budget" data-value="<?php echo formatCurrency($budgetSummary['total_budget']); ?>">
              ₱ ••••••••
            </div>
            <div class="stat-trend">
              <i class="bi bi-wallet2"></i>
              <span>Allocated funds</span>
            </div>
            
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'budget')" title="Show Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
        </div>
        
        <!-- Total Spent Card -->
        <div class="col-md-4 animate-in animate-delay-2">
          <div class="stat-card" style="--card-border: #ef4444; --icon-bg: #fee2e2; --icon-color: #991b1b;">
            <span class="ai-badge" onclick="showAIInsights('budget')">
              <i class="bi bi-stars"></i> AI Insights
            </span>
            <div class="stat-icon">
              <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-label">Total Spent</div>
            <div class="stat-value value-hidden" id="value-spent" data-value="<?php echo formatCurrency($budgetSummary['total_used']); ?>">
              ₱ ••••••••
            </div>
            <div class="stat-trend">
              <span class="trend-indicator trend-down">
                <i class="bi bi-graph-down"></i> <?php echo $budgetAnalytics['utilization_rate']; ?>% used
              </span>
            </div>
            
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'spent')" title="Show Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
        </div>
        
        <!-- Remaining Budget Card -->
        <div class="col-md-4 animate-in animate-delay-3">
          <div class="stat-card" style="--card-border: #10b981; --icon-bg: #d1fae5; --icon-color: #065f46;">
            <span class="ai-badge" onclick="showAIInsights('budget')">
              <i class="bi bi-stars"></i> AI Insights
            </span>
            <div class="stat-icon">
              <i class="bi bi-wallet2"></i>
            </div>
            <div class="stat-label">Remaining Budget</div>
            <div class="stat-value value-hidden" id="value-remaining" data-value="<?php echo formatCurrency($budgetSummary['total_remaining']); ?>">
              ₱ ••••••••
            </div>
            <div class="stat-trend">
              <?php 
              $remainingPct = $budgetSummary['total_budget'] > 0 ? 
                round(($budgetSummary['total_remaining'] / $budgetSummary['total_budget']) * 100, 1) : 0;
              ?>
              <span class="trend-indicator trend-up">
                <i class="bi bi-arrow-up"></i> <?php echo $remainingPct; ?>% available
              </span>
            </div>
            
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'remaining')" title="Show Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Charts Row 2: Budget -->
      <div class="row g-4 mb-5">
        <div class="col-md-8 animate-in animate-delay-4">
          <div class="chart-card">
            <div class="chart-title">Budget by Department</div>
            <div class="chart-container">
              <canvas id="budgetDepartmentChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-4 animate-in animate-delay-5">
          <div class="chart-card">
            <div class="chart-title">Budget Utilization</div>
            <div class="chart-container">
              <canvas id="budgetUtilizationChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Module Navigation Cards -->
      <div class="section-header">
        <div class="section-title">Financial Modules</div>
      </div>
      
      <div class="row g-4 mb-5">
        <div class="col-md-4 animate-in animate-delay-1">
          <a href="index.php" class="module-card">
            <div class="module-icon" style="--icon-bg: #dbeafe; --icon-color: #1e40af;">
              <i class="bi bi-speedometer2"></i>
            </div>
            <div class="module-title">Dashboard</div>
            <div class="module-description">Get a quick overview of key financial metrics for crane and trucking operations.</div>
          </a>
        </div>

        <div class="col-md-4 animate-in animate-delay-2">
          <a href="financial_collections.php" class="module-card">
            <div class="module-icon" style="--icon-bg: #d1fae5; --icon-color: #065f46;">
              <i class="bi bi-collection"></i>
            </div>
            <div class="module-title">Collections Management</div>
            <div class="module-description">Track and manage income from services, rentals, deliveries, and other receivables.</div>
          </a>
        </div>

        <div class="col-md-4 animate-in animate-delay-3">
          <a href="financial_budgeting.php" class="module-card">
            <div class="module-icon" style="--icon-bg: #dbeafe; --icon-color: #1e40af;">
              <i class="bi bi-pie-chart"></i>
            </div>
            <div class="module-title">Budgeting & Cost Allocation</div>
            <div class="module-description">Plan operational budgets and allocate costs across vehicle fleets and project sites.</div>
          </a>
        </div>

        <div class="col-md-4 animate-in animate-delay-4">
          <a href="financial_expense.php" class="module-card">
            <div class="module-icon" style="--icon-bg: #fef3c7; --icon-color: #92400e;">
              <i class="bi bi-receipt"></i>
            </div>
            <div class="module-title">Expense Tracking & Tax Management</div>
            <div class="module-description">Monitor fuel, maintenance, salaries, and tax-related expenses with accurate logs.</div>
          </a>
        </div>

        <div class="col-md-4 animate-in animate-delay-5">
          <a href="financial_ledger.php" class="module-card">
            <div class="module-icon" style="--icon-bg: #e0e7ff; --icon-color: #4338ca;">
              <i class="bi bi-journal-text"></i>
            </div>
            <div class="module-title">General Ledger Module</div>
            <div class="module-description">Maintain a comprehensive log of all company transactions for internal control.</div>
          </a>
        </div>

        <div class="col-md-4 animate-in animate-delay-6">
          <a href="financial_reporting.php" class="module-card">
            <div class="module-icon" style="--icon-bg: #fce7f3; --icon-color: #9f1239;">
              <i class="bi bi-graph-up"></i>
            </div>
            <div class="module-title">Financial Reporting Module</div>
            <div class="module-description">Generate reports for income, expenditures, and fleet profitability for stakeholders.</div>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Breakdown Modals -->
  <?php include 'dashboard_breakdown_modals.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- ADD THIS LINE -->
  <script src="session_check.js"></script>
  <!-- END OF ADDED LINE -->


  <script>
  // Store analytics data - ENHANCED with risk_reasoning
  const analyticsData = {
    collections: <?php echo json_encode($collectionsAnalytics); ?>,
    budget: <?php echo json_encode($budgetAnalytics); ?>,
    overdue: <?php echo json_encode($overdueAnalytics); ?>
  };

  // Store breakdown data
  const breakdownData = <?php echo json_encode([
    'collections' => $collectionsBreakdown,
    'budget' => $budgetBreakdown
  ]); ?>;

  // Track visibility state for each card
  const visibilityState = {
    'collected': false,
    'pending': false,
    'overdue': false,
    'budget': false,
    'spent': false,
    'remaining': false
  };

  function togglePrivacy(event, cardId) {
    event.stopPropagation();
    
    const valueElement = document.getElementById('value-' + cardId);
    const button = event.currentTarget;
    const icon = button.querySelector('i');
    
    visibilityState[cardId] = !visibilityState[cardId];
    
    if (visibilityState[cardId]) {
      valueElement.textContent = valueElement.getAttribute('data-value');
      valueElement.classList.remove('value-hidden');
      icon.className = 'bi bi-eye';
      button.title = 'Hide Amount';
    } else {
      valueElement.textContent = '₱ ••••••••';
      valueElement.classList.add('value-hidden');
      icon.className = 'bi bi-eye-slash';
      button.title = 'Show Amount';
    }
  }

  // ENHANCED: Show AI insights with risk_reasoning
  function showAIInsights(type) {
    const modal = document.getElementById('aiInsightsModal');
    const overlay = document.getElementById('aiInsightsOverlay');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    
    let content = '';
    
    if (type === 'collections') {
      modalTitle.textContent = 'Collections AI Analysis';
      const data = analyticsData.collections;
      
      content = `
        ${data.data_quality !== 'good' && data.data_note ? `
          <div class="data-quality-alert">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Note:</strong> ${data.data_note}
          </div>
        ` : ''}
        
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-lightbulb"></i>
            Key Insight
          </div>
          <div class="insight-card">
            <p class="insight-text">${data.insight}</p>
          </div>
        </div>
        
        ${data.risk_reasoning ? `
          <div class="insight-section">
            <div class="insight-section-title">
              <i class="bi bi-shield-check"></i>
              Risk Assessment Basis
            </div>
            <div class="insight-card">
              <p class="insight-text">${data.risk_reasoning}</p>
            </div>
          </div>
        ` : ''}
        
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-bar-chart"></i>
            Performance Metrics
          </div>
          <div class="metrics-grid">
            <div class="metric-item">
              <div class="metric-label">Trend</div>
              <div class="metric-value">${data.trend_percentage > 0 ? '+' : ''}${data.trend_percentage}%</div>
            </div>
            ${data.prediction && data.prediction.includes('₱') ? `
              <div class="metric-item">
                <div class="metric-label">Next Month</div>
                <div class="metric-value">${data.prediction}</div>
              </div>
            ` : ''}
            <div class="metric-item">
              <div class="metric-label">Risk Level</div>
              <div class="metric-value">
                <span class="risk-badge risk-${data.risk_level}">${data.risk_level.toUpperCase()}</span>
              </div>
            </div>
          </div>
          ${data.prediction && !data.prediction.includes('₱') ? `
            <div class="insight-card mt-3">
              <p class="insight-text"><i class="bi bi-info-circle me-2"></i>${data.prediction}</p>
            </div>
          ` : ''}
        </div>
        
        ${data.recommended_actions && data.recommended_actions.length > 0 ? `
          <div class="insight-section">
            <div class="insight-section-title">
              <i class="bi bi-check-circle"></i>
              Recommended Actions
            </div>
            <ul class="action-list">
              ${data.recommended_actions.map(action => `
                <li class="action-item">
                  <i class="bi bi-arrow-right-circle"></i>
                  <span>${action}</span>
                </li>
              `).join('')}
            </ul>
          </div>
        ` : ''}
      `;
    } else if (type === 'pending') {
      modalTitle.textContent = 'Pending Payments Analysis';
      
      content = `
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-lightbulb"></i>
            Payment Pattern Insight
          </div>
          <div class="insight-card">
            <p class="insight-text">Monitor upcoming due dates closely. Proactive follow-ups can improve collection rates and reduce overdue accounts.</p>
          </div>
        </div>
        
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-check-circle"></i>
            Recommended Actions
          </div>
          <ul class="action-list">
            <li class="action-item">
              <i class="bi bi-arrow-right-circle"></i>
              <span>Send payment reminders 5 days before due date</span>
            </li>
            <li class="action-item">
              <i class="bi bi-arrow-right-circle"></i>
              <span>Offer early payment incentives or discounts</span>
            </li>
            <li class="action-item">
              <i class="bi bi-arrow-right-circle"></i>
              <span>Set up automated reminder system</span>
            </li>
          </ul>
        </div>
      `;
    } else if (type === 'overdue') {
      modalTitle.textContent = 'Overdue Risk Analysis';
      const data = analyticsData.overdue;
      
      content = `
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-lightbulb"></i>
            Risk Assessment
          </div>
          <div class="insight-card">
            <p class="insight-text">${data.insight}</p>
          </div>
        </div>
        
        ${data.risk_reasoning ? `
          <div class="insight-section">
            <div class="insight-section-title">
              <i class="bi bi-shield-check"></i>
              Risk Assessment Basis
            </div>
            <div class="insight-card">
              <p class="insight-text">${data.risk_reasoning}</p>
            </div>
          </div>
        ` : ''}
        
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-bar-chart"></i>
            Key Metrics
          </div>
          <div class="metrics-grid">
            <div class="metric-item">
              <div class="metric-label">Avg Days Overdue</div>
              <div class="metric-value">${data.average_days_overdue} days</div>
            </div>
            <div class="metric-item">
              <div class="metric-label">Collection Rate</div>
              <div class="metric-value">${data.collection_probability}%</div>
            </div>
            <div class="metric-item">
              <div class="metric-label">Risk Level</div>
              <div class="metric-value">
                <span class="risk-badge risk-${data.risk_level}">${data.risk_level.toUpperCase()}</span>
              </div>
            </div>
          </div>
        </div>
        
        ${data.recommended_actions && data.recommended_actions.length > 0 ? `
          <div class="insight-section">
            <div class="insight-section-title">
              <i class="bi bi-exclamation-circle"></i>
              Recommended Actions
            </div>
            <ul class="action-list">
              ${data.recommended_actions.map(action => `
                <li class="action-item">
                  <i class="bi bi-arrow-right-circle"></i>
                  <span>${action}</span>
                </li>
              `).join('')}
            </ul>
          </div>
        ` : ''}
      `;
    } else if (type === 'budget') {
      modalTitle.textContent = 'Budget AI Analysis';
      const data = analyticsData.budget;
      
      content = `
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-lightbulb"></i>
            Budget Health
          </div>
          <div class="insight-card">
            <p class="insight-text">${data.insight}</p>
          </div>
        </div>
        
        ${data.risk_reasoning ? `
          <div class="insight-section">
            <div class="insight-section-title">
              <i class="bi bi-shield-check"></i>
              Risk Assessment Basis
            </div>
            <div class="insight-card">
              <p class="insight-text">${data.risk_reasoning}</p>
            </div>
          </div>
        ` : ''}
        
        <div class="insight-section">
          <div class="insight-section-title">
            <i class="bi bi-bar-chart"></i>
            Key Metrics
          </div>
          <div class="metrics-grid">
            <div class="metric-item">
              <div class="metric-label">Utilization Rate</div>
              <div class="metric-value">${data.utilization_rate}%</div>
            </div>
            <div class="metric-item">
              <div class="metric-label">Monthly Burn</div>
              <div class="metric-value">₱${data.burn_rate.toLocaleString()}</div>
            </div>
            <div class="metric-item">
              <div class="metric-label">Risk Level</div>
              <div class="metric-value">
                <span class="risk-badge risk-${data.risk_level}">${data.risk_level.toUpperCase()}</span>
              </div>
            </div>
            ${data.prediction ? `
              <div class="metric-item">
                <div class="metric-label">Forecast</div>
                <div class="metric-value" style="font-size: 0.9rem;">${data.prediction}</div>
              </div>
            ` : ''}
          </div>
        </div>
        
        ${data.top_spending_departments && data.top_spending_departments.length > 0 ? `
          <div class="insight-section">
            <div class="insight-section-title">
              <i class="bi bi-building"></i>
              Top Spending Departments
            </div>
            ${data.top_spending_departments.slice(0, 3).map(dept => `
              <div class="metric-item mb-2">
                <div class="metric-label">${dept.department}</div>
                <div class="metric-value">₱${dept.used.toLocaleString()} (${dept.utilization}%)</div>
              </div>
            `).join('')}
          </div>
        ` : ''}
        
        ${data.recommended_actions && data.recommended_actions.length > 0 ? `
          <div class="insight-section">
            <div class="insight-section-title">
              <i class="bi bi-check-circle"></i>
              Recommended Actions
            </div>
            <ul class="action-list">
              ${data.recommended_actions.map(action => `
                <li class="action-item">
                  <i class="bi bi-arrow-right-circle"></i>
                  <span>${action}</span>
                </li>
              `).join('')}
            </ul>
          </div>
        ` : ''}
      `;
    }
    
    modalContent.innerHTML = content;
    modal.classList.add('show');
    overlay.classList.add('show');
  }

  function closeAIModal() {
    const modal = document.getElementById('aiInsightsModal');
    const overlay = document.getElementById('aiInsightsOverlay');
    
    modal.classList.remove('show');
    overlay.classList.remove('show');
  }

  function showBreakdown(type, event) {
    if (event && (event.target.classList.contains('privacy-toggle') || 
        event.target.closest('.privacy-toggle'))) {
      return;
    }
    
    const modalId = type + 'BreakdownModal';
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
  }

  // Chart.js default options
  Chart.defaults.color = '#64748b';
  Chart.defaults.borderColor = 'rgba(0, 0, 0, 0.08)';

  // Collections by Status Chart
  const collectionsStatusCtx = document.getElementById('collectionsStatusChart').getContext('2d');
  new Chart(collectionsStatusCtx, {
    type: 'doughnut',
    data: {
      labels: ['Paid', 'Partial', 'Unpaid'],
      datasets: [{
        data: [
          <?php echo $collectionsByStatus['Paid']; ?>,
          <?php echo $collectionsByStatus['Partial']; ?>,
          <?php echo $collectionsByStatus['Unpaid']; ?>
        ],
        backgroundColor: [
          'rgba(17, 153, 142, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)'
        ],
        borderColor: [
          'rgba(17, 153, 142, 1)',
          'rgba(245, 158, 11, 1)',
          'rgba(239, 68, 68, 1)'
        ],
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            font: { size: 12 }
          }
        }
      }
    }
  });

  // Monthly Collection Trends Chart
  const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
  new Chart(monthlyTrendsCtx, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($monthlyTrends['labels']); ?>,
      datasets: [{
        label: 'Collections',
        data: <?php echo json_encode($monthlyTrends['amounts']); ?>,
        borderColor: 'rgba(102, 126, 234, 1)',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: 'rgba(102, 126, 234, 1)',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '₱' + value.toLocaleString();
            }
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });

  // Budget by Department Chart
  const budgetDepartmentCtx = document.getElementById('budgetDepartmentChart').getContext('2d');
  new Chart(budgetDepartmentCtx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode($budgetByDepartment['labels']); ?>,
      datasets: [{
        label: 'Allocated',
        data: <?php echo json_encode($budgetByDepartment['allocated']); ?>,
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 2,
        borderRadius: 8
      }, {
        label: 'Used',
        data: <?php echo json_encode($budgetByDepartment['used']); ?>,
        backgroundColor: 'rgba(239, 68, 68, 0.8)',
        borderColor: 'rgba(239, 68, 68, 1)',
        borderWidth: 2,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            padding: 15,
            font: { size: 12 }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '₱' + value.toLocaleString();
            }
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });

  // Budget Utilization Chart
  const budgetUtilizationCtx = document.getElementById('budgetUtilizationChart').getContext('2d');
  const utilizationData = <?php echo json_encode($budgetUtilization); ?>;
  const periods = Object.keys(utilizationData);
  const utilizationPcts = periods.map(period => {
    const data = utilizationData[period];
    return data.allocated > 0 ? (data.used / data.allocated * 100).toFixed(1) : 0;
  });

  new Chart(budgetUtilizationCtx, {
    type: 'polarArea',
    data: {
      labels: periods,
      datasets: [{
        data: utilizationPcts,
        backgroundColor: [
          'rgba(102, 126, 234, 0.6)',
          'rgba(79, 172, 254, 0.6)',
          'rgba(17, 153, 142, 0.6)',
          'rgba(240, 147, 251, 0.6)'
        ],
        borderColor: [
          'rgba(102, 126, 234, 1)',
          'rgba(79, 172, 254, 1)',
          'rgba(17, 153, 142, 1)',
          'rgba(240, 147, 251, 1)'
        ],
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 10,
            font: { size: 11 }
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.label + ': ' + context.parsed.r + '%';
            }
          }
        }
      },
      scales: {
        r: {
          beginAtZero: true,
          max: 100,
          ticks: {
            callback: function(value) {
              return value + '%';
            }
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.1)'
          }
        }
      }
    }
  });

  // Session timeout handling
  let sessionTimeout;
  let warningTimeout;
  let lastActivity = Date.now();

  function resetSessionTimer() {
    clearTimeout(sessionTimeout);
    clearTimeout(warningTimeout);
    lastActivity = Date.now();
    
    warningTimeout = setTimeout(function() {
      if (confirm('Your session will expire in 1 minute due to inactivity. Click OK to continue your session.')) {
        fetch(window.location.href, {
          method: 'HEAD',
          credentials: 'same-origin'
        });
        resetSessionTimer();
      }
    }, <?php echo (SESSION_TIMEOUT - 60) * 1000; ?>);
    
    sessionTimeout = setTimeout(function() {
      alert('Your session has expired due to inactivity. You will be redirected to the login page.');
      window.location.href = 'login.php?timeout=1';
    }, <?php echo SESSION_TIMEOUT * 1000; ?>);
  }

  ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
    document.addEventListener(event, function() {
      if (Date.now() - lastActivity > 60000) {
        resetSessionTimer();
      }
    }, { capture: true, passive: true });
  });

  resetSessionTimer();
  </script>

</body>
</html>
