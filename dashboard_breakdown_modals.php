<!-- dashboard_breakdown_modals.php -->

<!-- Total Collected Breakdown Modal -->
<div class="modal fade" id="collectedBreakdownModal" tabindex="-1" aria-labelledby="collectedBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="collectedBreakdownLabel">
          <i class="bi bi-cash-coin me-2"></i>Total Collected Breakdown
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- Explanation Section -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-info-circle me-2"></i>What is "Total Collected"?
          </div>
          <p class="mb-2">
            <strong>Total Collected</strong> represents the sum of all payments that have been received from clients, regardless of whether the invoice is fully paid or partially paid.
          </p>
          <div class="formula-box">
            <strong>Formula:</strong><br>
            Total Collected = SUM of all `amount_paid` from collections table<br>
            <em>This includes payments from Paid invoices AND Partial payments from invoices that are not yet fully settled.</em>
          </div>
          <p class="mb-0 mt-2">
            <strong>Purpose:</strong> This shows your actual cash inflow from collections. It helps you understand how much money has actually entered your business from client payments.
          </p>
        </div>

        <!-- Summary Statistics -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-bar-chart me-2"></i>Collection Statistics
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-success mb-0"><?php echo formatCurrency($collectionsBreakdown['total_collected']['amount']); ?></div>
                <small class="text-muted">Total Amount</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-primary mb-0"><?php echo count($collectionsBreakdown['total_collected']['records']); ?></div>
                <small class="text-muted">Payment Transactions</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-success mb-0"><?php echo $collectionsBreakdown['summary_stats']['paid_count']; ?></div>
                <small class="text-muted">Fully Paid Invoices</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-warning mb-0"><?php echo $collectionsBreakdown['summary_stats']['partial_count']; ?></div>
                <small class="text-muted">Partial Payments</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-list-ul me-2"></i>Detailed Payment Records
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table table-hover">
              <thead>
                <tr>
                  <th>Client Name</th>
                  <th>Invoice No.</th>
                  <th>Status</th>
                  <th>Billing Date</th>
                  <th>Due Date</th>
                  <th class="text-end">Amount Due</th>
                  <th class="text-end">Amount Paid</th>
                  <th class="text-end">Payment %</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $runningTotal = 0;
                foreach ($collectionsBreakdown['total_collected']['records'] as $record): 
                  $runningTotal += $record['amount_paid'];
                  $paymentPct = ($record['amount_due'] > 0) ? ($record['amount_paid'] / $record['amount_due']) * 100 : 0;
                  $statusClass = $record['payment_status'] === 'Paid' ? 'success' : 'warning';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['client']); ?></td>
                  <td><code><?php echo htmlspecialchars($record['invoice']); ?></code></td>
                  <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $record['payment_status']; ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($record['billing_date'])); ?></td>
                  <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                  <td class="text-end"><?php echo formatCurrency($record['amount_due']); ?></td>
                  <td class="text-end"><strong class="text-success"><?php echo formatCurrency($record['amount_paid']); ?></strong></td>
                  <td class="text-end">
                    <span class="stat-badge bg-light"><?php echo number_format($paymentPct, 1); ?>%</span>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($collectionsBreakdown['total_collected']['records'])): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-3">No payment records found</td>
                </tr>
                <?php endif; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="6" class="text-end">TOTAL COLLECTED:</th>
                  <th class="text-end"><strong class="text-success"><?php echo formatCurrency($collectionsBreakdown['total_collected']['amount']); ?></strong></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="financial_collections.php" class="btn btn-success">Go to Collections Module</a>
      </div>
    </div>
  </div>
</div>

<!-- Pending Collections Breakdown Modal -->
<div class="modal fade" id="pendingBreakdownModal" tabindex="-1" aria-labelledby="pendingBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="pendingBreakdownLabel">
          <i class="bi bi-clock-history me-2"></i>Pending Collections Breakdown
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- Explanation Section -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-info-circle me-2"></i>What is "Pending Collections"?
          </div>
          <p class="mb-2">
            <strong>Pending Collections</strong> represents the amount of money that clients still owe you but haven't paid yet. This includes both unpaid invoices and partially paid invoices.
          </p>
          <div class="formula-box">
            <strong>Formula:</strong><br>
            Pending Amount = Amount Due - Amount Paid<br>
            Total Pending = SUM of all (amount_due - amount_paid) WHERE result > 0<br>
            <em>This calculation is done for each invoice individually, then summed up.</em>
          </div>
          <p class="mb-0 mt-2">
            <strong>Purpose:</strong> This shows how much money you're expecting to receive. It's your accounts receivable - money that should come in the future. This helps with cash flow planning.
          </p>
        </div>

        <!-- Summary Statistics -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-bar-chart me-2"></i>Pending Statistics
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="text-center">
                <div class="h3 text-warning mb-0"><?php echo formatCurrency($collectionsBreakdown['total_pending']['amount']); ?></div>
                <small class="text-muted">Total Pending</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <div class="h3 text-danger mb-0"><?php echo $collectionsBreakdown['summary_stats']['unpaid_count']; ?></div>
                <small class="text-muted">Unpaid Invoices</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <div class="h3 text-warning mb-0"><?php echo $collectionsBreakdown['summary_stats']['partial_count']; ?></div>
                <small class="text-muted">Partially Paid</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-list-ul me-2"></i>Detailed Pending Records
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table table-hover">
              <thead>
                <tr>
                  <th>Client Name</th>
                  <th>Invoice No.</th>
                  <th>Status</th>
                  <th>Due Date</th>
                  <th class="text-end">Amount Due</th>
                  <th class="text-end">Amount Paid</th>
                  <th class="text-end">Pending Amount</th>
                  <th>Days Until/Past Due</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                foreach ($collectionsBreakdown['total_pending']['records'] as $record): 
                  $statusClass = $record['payment_status'] === 'Unpaid' ? 'danger' : 'warning';
                  $daysUntilDue = round($record['days_until_due']);
                  $dueStatus = $daysUntilDue >= 0 ? 
                    '<span class="badge bg-info">' . $daysUntilDue . ' days left</span>' : 
                    '<span class="badge bg-danger">' . abs($daysUntilDue) . ' days overdue</span>';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['client']); ?></td>
                  <td><code><?php echo htmlspecialchars($record['invoice']); ?></code></td>
                  <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $record['payment_status']; ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                  <td class="text-end"><?php echo formatCurrency($record['amount_due']); ?></td>
                  <td class="text-end"><?php echo formatCurrency($record['amount_paid']); ?></td>
                  <td class="text-end"><strong class="text-warning"><?php echo formatCurrency($record['pending_amount']); ?></strong></td>
                  <td><?php echo $dueStatus; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($collectionsBreakdown['total_pending']['records'])): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-3">No pending collections found</td>
                </tr>
                <?php endif; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="6" class="text-end">TOTAL PENDING:</th>
                  <th class="text-end"><strong class="text-warning"><?php echo formatCurrency($collectionsBreakdown['total_pending']['amount']); ?></strong></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Calculation Example -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-calculator me-2"></i>Example Calculation
          </div>
          <?php if (!empty($collectionsBreakdown['total_pending']['records'])): 
            $example = $collectionsBreakdown['total_pending']['records'][0];
          ?>
          <div class="card">
            <div class="card-body">
              <h6>Invoice: <?php echo htmlspecialchars($example['invoice']); ?></h6>
              <p class="mb-1">Client: <?php echo htmlspecialchars($example['client']); ?></p>
              <div class="formula-box mt-2">
                Amount Due: <?php echo formatCurrency($example['amount_due']); ?><br>
                Amount Paid: <?php echo formatCurrency($example['amount_paid']); ?><br>
                <hr class="my-2">
                <strong>Pending Amount = <?php echo formatCurrency($example['amount_due']); ?> - <?php echo formatCurrency($example['amount_paid']); ?> = <?php echo formatCurrency($example['pending_amount']); ?></strong>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="financial_collections.php" class="btn btn-warning">Go to Collections Module</a>
      </div>
    </div>
  </div>
</div>

<!-- Overdue Collections Breakdown Modal -->
<div class="modal fade" id="overdueBreakdownModal" tabindex="-1" aria-labelledby="overdueBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="overdueBreakdownLabel">
          <i class="bi bi-exclamation-triangle me-2"></i>Overdue Collections Breakdown
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- Explanation Section -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-info-circle me-2"></i>What is "Overdue Collections"?
          </div>
          <p class="mb-2">
            <strong>Overdue Collections</strong> represents pending amounts (unpaid or partially paid) that have passed their due date. These are critical because they affect your cash flow and may require follow-up action.
          </p>
          <div class="formula-box">
            <strong>Formula:</strong><br>
            Overdue Amount = (Amount Due - Amount Paid)<br>
            WHERE:<br>
            - Payment Status ≠ 'Paid'<br>
            - Due Date < Today's Date<br>
            - Pending Amount > 0<br>
            <br>
            Total Overdue = SUM of all qualifying pending amounts
          </div>
          <p class="mb-0 mt-2">
            <strong>Purpose:</strong> This alerts you to collections that need immediate attention. Overdue amounts may incur penalties and require follow-up with clients. This is a subset of "Pending Collections."
          </p>
        </div>

        <!-- Summary Statistics -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-bar-chart me-2"></i>Overdue Statistics
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-danger mb-0"><?php echo formatCurrency($collectionsBreakdown['total_overdue']['amount']); ?></div>
                <small class="text-muted">Total Overdue</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-danger mb-0"><?php echo $collectionsBreakdown['summary_stats']['overdue_count']; ?></div>
                <small class="text-muted">Overdue Invoices</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <?php 
                $totalPenalties = array_sum(array_column($collectionsBreakdown['total_overdue']['records'], 'penalty'));
                ?>
                <div class="h3 text-warning mb-0"><?php echo formatCurrency($totalPenalties); ?></div>
                <small class="text-muted">Total Penalties</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <?php 
                $avgDaysOverdue = 0;
                if (count($collectionsBreakdown['total_overdue']['records']) > 0) {
                  $avgDaysOverdue = round(array_sum(array_column($collectionsBreakdown['total_overdue']['records'], 'days_overdue')) / count($collectionsBreakdown['total_overdue']['records']));
                }
                ?>
                <div class="h3 text-info mb-0"><?php echo $avgDaysOverdue; ?></div>
                <small class="text-muted">Avg Days Overdue</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-list-ul me-2"></i>Detailed Overdue Records
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table table-hover">
              <thead>
                <tr>
                  <th>Client Name</th>
                  <th>Invoice No.</th>
                  <th>Status</th>
                  <th>Due Date</th>
                  <th class="text-end">Amount Due</th>
                  <th class="text-end">Amount Paid</th>
                  <th class="text-end">Overdue Amount</th>
                  <th class="text-end">Penalty</th>
                  <th>Days Overdue</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                // Sort by days overdue (most overdue first)
                usort($collectionsBreakdown['total_overdue']['records'], function($a, $b) {
                  return $b['days_overdue'] - $a['days_overdue'];
                });
                
                foreach ($collectionsBreakdown['total_overdue']['records'] as $record): 
                  $statusClass = $record['payment_status'] === 'Unpaid' ? 'danger' : 'warning';
                  $urgencyClass = $record['days_overdue'] > 30 ? 'danger' : ($record['days_overdue'] > 7 ? 'warning' : 'info');
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['client']); ?></td>
                  <td><code><?php echo htmlspecialchars($record['invoice']); ?></code></td>
                  <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $record['payment_status']; ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                  <td class="text-end"><?php echo formatCurrency($record['amount_due']); ?></td>
                  <td class="text-end"><?php echo formatCurrency($record['amount_paid']); ?></td>
                  <td class="text-end"><strong class="text-danger"><?php echo formatCurrency($record['overdue_amount']); ?></strong></td>
                  <td class="text-end text-warning"><?php echo formatCurrency($record['penalty']); ?></td>
                  <td><span class="badge bg-<?php echo $urgencyClass; ?>"><?php echo $record['days_overdue']; ?> days</span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($collectionsBreakdown['total_overdue']['records'])): ?>
                <tr>
                  <td colspan="9" class="text-center text-success py-3">
                    <i class="bi bi-check-circle me-2"></i>No overdue collections - Great job!
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="6" class="text-end">TOTAL OVERDUE:</th>
                  <th class="text-end"><strong class="text-danger"><?php echo formatCurrency($collectionsBreakdown['total_overdue']['amount']); ?></strong></th>
                  <th class="text-end"><strong class="text-warning"><?php echo formatCurrency($totalPenalties); ?></strong></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Relationship Explanation -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-diagram-3 me-2"></i>Relationship to Other Metrics
          </div>
          <div class="alert alert-info mb-0">
            <strong>Important Note:</strong> Overdue Collections is a subset of Pending Collections.<br>
            <ul class="mb-0 mt-2">
              <li><strong>Pending Collections</strong> (<?php echo formatCurrency($collectionsBreakdown['total_pending']['amount']); ?>) = All money not yet collected</li>
              <li><strong>Overdue Collections</strong> (<?php echo formatCurrency($collectionsBreakdown['total_overdue']['amount']); ?>) = Pending amounts past due date</li>
              <li><strong>On-Time Pending</strong> = <?php echo formatCurrency($collectionsBreakdown['total_pending']['amount'] - $collectionsBreakdown['total_overdue']['amount']); ?> (still within due date)</li>
            </ul>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="financial_collections.php" class="btn btn-danger">Go to Collections Module</a>
      </div>
    </div>
  </div>
</div>

<!-- Total Budget Breakdown Modal -->
<div class="modal fade" id="budgetBreakdownModal" tabindex="-1" aria-labelledby="budgetBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="budgetBreakdownLabel">
          <i class="bi bi-pie-chart me-2"></i>Total Budget Breakdown
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- Explanation Section -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-info-circle me-2"></i>What is "Total Budget"?
          </div>
          <p class="mb-2">
            <strong>Total Budget</strong> represents the sum of all allocated budget amounts across all departments, cost centers, and time periods in your system.
          </p>
          <div class="formula-box">
            <strong>Formula:</strong><br>
            Total Budget = SUM of all `amount_allocated` from budgets table<br>
            <em>This includes all budget periods (Daily, Monthly, Annually) and all approval statuses.</em>
          </div>
          <p class="mb-0 mt-2">
            <strong>Purpose:</strong> This shows your total planned spending. It's the upper limit of what you've authorized for spending across your organization. This helps with financial planning and resource allocation.
          </p>
        </div>

        <!-- Summary Statistics -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-bar-chart me-2"></i>Budget Statistics
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-primary mb-0"><?php echo formatCurrency($budgetBreakdown['total_budget']['amount']); ?></div>
                <small class="text-muted">Total Allocated</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-info mb-0"><?php echo $budgetBreakdown['summary_stats']['total_budgets']; ?></div>
                <small class="text-muted">Budget Entries</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-success mb-0"><?php echo $budgetBreakdown['summary_stats']['approved_count']; ?></div>
                <small class="text-muted">Approved</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="h3 text-warning mb-0"><?php echo $budgetBreakdown['summary_stats']['pending_count']; ?></div>
                <small class="text-muted">Pending</small>
              </div>
            </div>
          </div>
        </div>

        <!-- By Department -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-building me-2"></i>Budget by Department
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table">
              <thead>
                <tr>
                  <th>Department</th>
                  <th class="text-end">Allocated</th>
                  <th class="text-end">Used</th>
                  <th class="text-end">Remaining</th>
                  <th class="text-end">% of Total</th>
                  <th class="text-center">Budget Count</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($budgetBreakdown['by_department'] as $dept => $data): 
                  $pctOfTotal = $budgetBreakdown['total_budget']['amount'] > 0 ? 
                    ($data['allocated'] / $budgetBreakdown['total_budget']['amount']) * 100 : 0;
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($dept); ?></strong></td>
                  <td class="text-end"><?php echo formatCurrency($data['allocated']); ?></td>
                  <td class="text-end"><?php echo formatCurrency($data['used']); ?></td>
                  <td class="text-end"><?php echo formatCurrency($data['remaining']); ?></td>
                  <td class="text-end"><span class="stat-badge bg-light"><?php echo number_format($pctOfTotal, 1); ?>%</span></td>
                  <td class="text-center"><span class="badge bg-secondary"><?php echo $data['count']; ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- By Period -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-calendar3 me-2"></i>Budget by Period
          </div>
          <div class="row">
            <?php foreach ($budgetBreakdown['by_period'] as $period => $data): 
              $pctOfTotal = $budgetBreakdown['total_budget']['amount'] > 0 ? 
                ($data['allocated'] / $budgetBreakdown['total_budget']['amount']) * 100 : 0;
            ?>
            <div class="col-md-4 mb-3">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title"><?php echo $period; ?></h6>
                  <p class="mb-1"><strong>Allocated:</strong> <?php echo formatCurrency($data['allocated']); ?></p>
                  <p class="mb-1"><strong>Used:</strong> <?php echo formatCurrency($data['used']); ?></p>
                  <p class="mb-1"><strong>Remaining:</strong> <?php echo formatCurrency($data['remaining']); ?></p>
                  <p class="mb-0"><strong>Count:</strong> <?php echo $data['count']; ?> budgets (<?php echo number_format($pctOfTotal, 1); ?>%)</p>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Detailed List -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-list-ul me-2"></i>All Budget Allocations
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table table-hover">
              <thead>
                <tr>
                  <th>Department</th>
                  <th>Cost Center</th>
                  <th>Period</th>
                  <th class="text-end">Allocated</th>
                  <th>Status</th>
                  <th>Approved By</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($budgetBreakdown['total_budget']['records'] as $record): 
                  $statusClass = $record['approval_status'] === 'Approved' ? 'success' : 
                                ($record['approval_status'] === 'Pending' ? 'warning' : 'danger');
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['department']); ?></td>
                  <td><?php echo htmlspecialchars($record['cost_center']); ?></td>
                  <td><span class="badge bg-info"><?php echo $record['period']; ?></span></td>
                  <td class="text-end"><strong class="text-primary"><?php echo formatCurrency($record['allocated']); ?></strong></td>
                  <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $record['approval_status']; ?></span></td>
                  <td><?php echo htmlspecialchars($record['approved_by'] ?: 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="3" class="text-end">TOTAL BUDGET:</th>
                  <th class="text-end"><strong class="text-primary"><?php echo formatCurrency($budgetBreakdown['total_budget']['amount']); ?></strong></th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="financial_budgeting.php" class="btn btn-primary">Go to Budgeting Module</a>
      </div>
    </div>
  </div>
</div>

<!-- Total Spent Breakdown Modal -->
<div class="modal fade" id="spentBreakdownModal" tabindex="-1" aria-labelledby="spentBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="spentBreakdownLabel">
          <i class="bi bi-cash-stack me-2"></i>Total Spent Breakdown
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- Explanation Section -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-info-circle me-2"></i>What is "Total Spent"?
          </div>
          <p class="mb-2">
            <strong>Total Spent</strong> represents the sum of all money that has actually been used from your budgets. This is your actual expenditure tracked against allocated budgets.
          </p>
          <div class="formula-box">
            <strong>Formula:</strong><br>
            Total Spent = SUM of all `amount_used` from budgets table<br>
            <em>This tracks how much of your allocated budget has been consumed by actual expenses.</em>
          </div>
          <p class="mb-0 mt-2">
            <strong>Purpose:</strong> This shows your actual spending. Comparing this with Total Budget helps you understand budget utilization and whether you're staying within planned limits.
          </p>
        </div>

        <!-- Summary Statistics -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-bar-chart me-2"></i>Spending Statistics
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="text-center">
                <div class="h3 text-danger mb-0"><?php echo formatCurrency($budgetBreakdown['total_used']['amount']); ?></div>
                <small class="text-muted">Total Spent</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <div class="h3 text-info mb-0"><?php echo number_format($budgetBreakdown['summary_stats']['overall_utilization'], 1); ?>%</div>
                <small class="text-muted">Overall Utilization</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <div class="h3 text-primary mb-0"><?php echo formatCurrency($budgetBreakdown['total_budget']['amount']); ?></div>
                <small class="text-muted">Total Budget</small>
              </div>
            </div>
          </div>
        </div>

        <!-- By Department -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-building me-2"></i>Spending by Department
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table">
              <thead>
                <tr>
                  <th>Department</th>
                  <th class="text-end">Allocated</th>
                  <th class="text-end">Spent</th>
                  <th class="text-end">Utilization %</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($budgetBreakdown['by_department'] as $dept => $data): 
                  $utilization = $data['allocated'] > 0 ? ($data['used'] / $data['allocated']) * 100 : 0;
                  $statusClass = $utilization > 100 ? 'danger' : ($utilization > 90 ? 'warning' : 'success');
                  $statusText = $utilization > 100 ? 'Overspent' : ($utilization > 90 ? 'High Usage' : 'On Track');
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($dept); ?></strong></td>
                  <td class="text-end"><?php echo formatCurrency($data['allocated']); ?></td>
                  <td class="text-end"><strong class="text-danger"><?php echo formatCurrency($data['used']); ?></strong></td>
                  <td class="text-end"><span class="stat-badge bg-light"><?php echo number_format($utilization, 1); ?>%</span></td>
                  <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Detailed List -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-list-ul me-2"></i>All Spending Records
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table table-hover">
              <thead>
                <tr>
                  <th>Department</th>
                  <th>Cost Center</th>
                  <th>Period</th>
                  <th class="text-end">Allocated</th>
                  <th class="text-end">Used</th>
                  <th class="text-end">Utilization %</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($budgetBreakdown['total_used']['records'] as $record): 
                  $utilization = $record['utilization_pct'];
                  $statusClass = $utilization > 100 ? 'danger' : ($utilization > 90 ? 'warning' : 'success');
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['department']); ?></td>
                  <td><?php echo htmlspecialchars($record['cost_center']); ?></td>
                  <td><span class="badge bg-info"><?php echo $record['period']; ?></span></td>
                  <td class="text-end"><?php echo formatCurrency($record['allocated']); ?></td>
                  <td class="text-end"><strong class="text-danger"><?php echo formatCurrency($record['used']); ?></strong></td>
                  <td class="text-end">
                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo number_format($utilization, 1); ?>%</span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="4" class="text-end">TOTAL SPENT:</th>
                  <th class="text-end"><strong class="text-danger"><?php echo formatCurrency($budgetBreakdown['total_used']['amount']); ?></strong></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Calculation Example -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-calculator me-2"></i>Utilization Calculation
          </div>
          <div class="formula-box">
            <strong>How Utilization is Calculated:</strong><br>
            Utilization % = (Amount Used ÷ Amount Allocated) × 100<br><br>
            <strong>Overall Utilization:</strong><br>
            (<?php echo formatCurrency($budgetBreakdown['total_used']['amount']); ?> ÷ <?php echo formatCurrency($budgetBreakdown['total_budget']['amount']); ?>) × 100 = 
            <strong><?php echo number_format($budgetBreakdown['summary_stats']['overall_utilization'], 2); ?>%</strong>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="financial_budgeting.php" class="btn btn-danger">Go to Budgeting Module</a>
      </div>
    </div>
  </div>
</div>

<!-- Remaining Budget Breakdown Modal -->
<div class="modal fade" id="remainingBreakdownModal" tabindex="-1" aria-labelledby="remainingBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="remainingBreakdownLabel">
          <i class="bi bi-wallet2 me-2"></i>Remaining Budget Breakdown
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- Explanation Section -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-info-circle me-2"></i>What is "Remaining Budget"?
          </div>
          <p class="mb-2">
            <strong>Remaining Budget</strong> represents how much money is still available to spend from your allocated budgets. This is what you have left after actual spending.
          </p>
          <div class="formula-box">
            <strong>Formula:</strong><br>
            Remaining Budget = Amount Allocated - Amount Used<br>
            Total Remaining = SUM of all (amount_allocated - amount_used)<br>
            <em>A negative value indicates overspending beyond the allocated amount.</em>
          </div>
          <p class="mb-0 mt-2">
            <strong>Purpose:</strong> This shows your spending capacity. It helps you understand how much more you can spend without exceeding your budget limits. Critical for cash flow and expense management.
          </p>
        </div>

        <!-- Summary Statistics -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-bar-chart me-2"></i>Remaining Budget Statistics
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="text-center">
                <div class="h3 text-success mb-0"><?php echo formatCurrency($budgetBreakdown['total_remaining']['amount']); ?></div>
                <small class="text-muted">Total Remaining</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <?php 
                $remainingPct = $budgetBreakdown['total_budget']['amount'] > 0 ? 
                  ($budgetBreakdown['total_remaining']['amount'] / $budgetBreakdown['total_budget']['amount']) * 100 : 0;
                ?>
                <div class="h3 text-info mb-0"><?php echo number_format($remainingPct, 1); ?>%</div>
                <small class="text-muted">% Unspent</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <?php 
                $overspentCount = count(array_filter($budgetBreakdown['total_remaining']['records'], function($r) {
                  return $r['remaining'] < 0;
                }));
                ?>
                <div class="h3 text-danger mb-0"><?php echo $overspentCount; ?></div>
                <small class="text-muted">Overspent Budgets</small>
              </div>
            </div>
          </div>
        </div>

        <!-- By Department -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-building me-2"></i>Remaining Budget by Department
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table">
              <thead>
                <tr>
                  <th>Department</th>
                  <th class="text-end">Allocated</th>
                  <th class="text-end">Used</th>
                  <th class="text-end">Remaining</th>
                  <th class="text-end">% Remaining</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($budgetBreakdown['by_department'] as $dept => $data): 
                  $remainingPct = $data['allocated'] > 0 ? ($data['remaining'] / $data['allocated']) * 100 : 0;
                  $textClass = $data['remaining'] < 0 ? 'danger' : 'success';
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($dept); ?></strong></td>
                  <td class="text-end"><?php echo formatCurrency($data['allocated']); ?></td>
                  <td class="text-end"><?php echo formatCurrency($data['used']); ?></td>
                  <td class="text-end"><strong class="text-<?php echo $textClass; ?>"><?php echo formatCurrency($data['remaining']); ?></strong></td>
                  <td class="text-end"><span class="stat-badge bg-light"><?php echo number_format($remainingPct, 1); ?>%</span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Detailed List -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-list-ul me-2"></i>All Budget Balances
          </div>
          <div class="table-responsive">
            <table class="table table-sm detail-table table-hover">
              <thead>
                <tr>
                  <th>Department</th>
                  <th>Cost Center</th>
                  <th>Period</th>
                  <th class="text-end">Allocated</th>
                  <th class="text-end">Remaining</th>
                  <th class="text-end">% Remaining</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                // Sort by remaining amount (lowest first to highlight critical budgets)
                usort($budgetBreakdown['total_remaining']['records'], function($a, $b) {
                  return $a['remaining'] - $b['remaining'];
                });
                
                foreach ($budgetBreakdown['total_remaining']['records'] as $record): 
                  $remainingPct = $record['remaining_pct'];
                  if ($record['remaining'] < 0) {
                    $statusClass = 'danger';
                    $statusText = 'Overspent';
                  } elseif ($remainingPct < 10) {
                    $statusClass = 'warning';
                    $statusText = 'Critical Low';
                  } elseif ($remainingPct < 25) {
                    $statusClass = 'info';
                    $statusText = 'Low';
                  } else {
                    $statusClass = 'success';
                    $statusText = 'Healthy';
                  }
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['department']); ?></td>
                  <td><?php echo htmlspecialchars($record['cost_center']); ?></td>
                  <td><span class="badge bg-info"><?php echo $record['period']; ?></span></td>
                  <td class="text-end"><?php echo formatCurrency($record['allocated']); ?></td>
                  <td class="text-end">
                    <strong class="text-<?php echo $record['remaining'] < 0 ? 'danger' : 'success'; ?>">
                      <?php echo formatCurrency($record['remaining']); ?>
                    </strong>
                  </td>
                  <td class="text-end"><span class="stat-badge bg-light"><?php echo number_format($remainingPct, 1); ?>%</span></td>
                  <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="4" class="text-end">TOTAL REMAINING:</th>
                  <th class="text-end"><strong class="text-success"><?php echo formatCurrency($budgetBreakdown['total_remaining']['amount']); ?></strong></th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Budget Relationship -->
        <div class="breakdown-section">
          <div class="breakdown-header">
            <i class="bi bi-diagram-3 me-2"></i>Budget Relationship
          </div>
          <div class="alert alert-info mb-0">
            <strong>How the Three Budget Metrics Work Together:</strong><br>
            <div class="formula-box mt-2 mb-0">
              <strong>Total Budget</strong> (<?php echo formatCurrency($budgetBreakdown['total_budget']['amount']); ?>) = What you planned to spend<br>
              <strong>Total Spent</strong> (<?php echo formatCurrency($budgetBreakdown['total_used']['amount']); ?>) = What you actually spent<br>
              <strong>Remaining Budget</strong> (<?php echo formatCurrency($budgetBreakdown['total_remaining']['amount']); ?>) = What you can still spend<br><br>
              <strong>Verification:</strong><br>
              Total Budget - Total Spent = Remaining Budget<br>
              <?php echo formatCurrency($budgetBreakdown['total_budget']['amount']); ?> - <?php echo formatCurrency($budgetBreakdown['total_used']['amount']); ?> = <?php echo formatCurrency($budgetBreakdown['total_remaining']['amount']); ?> ✓
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="financial_budgeting.php" class="btn btn-success">Go to Budgeting Module</a>
      </div>
    </div>
  </div>
</div>
