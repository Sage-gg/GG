<?php
/**
 * budgets_breakdown.php
 * ─────────────────────
 * Self-contained include: runs its own queries against `budgets` +
 * `budget_allocations`, then renders three Bootstrap-5 modals:
 *   #bdModal_budget    – Total Budget
 *   #bdModal_spent     – Total Spent
 *   #bdModal_remaining – Remaining
 *
 * Expects: $conn (mysqli)  — already open in the parent page.
 *          peso()          — already defined in the parent page.
 */

// ─── 1. Pull every row we need in two queries ──────────────────────────────
$bd_budgets = [];
$bd_allocs  = [];          // keyed by budget_id → array of allocation rows

try {
    $s = $conn->prepare(
        "SELECT id, department, cost_center, period, amount_allocated, amount_used,
                approval_status, approved_by, description, created_at
         FROM budgets ORDER BY created_at DESC"
    );
    $s->execute();
    $bd_budgets = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s->close();
} catch (Exception $e) {
    error_log("budgets_breakdown – budgets query: " . $e->getMessage());
}

try {
    $s = $conn->prepare("SELECT * FROM budget_allocations ORDER BY budget_id, created_at ASC");
    $s->execute();
    foreach ($s->get_result()->fetch_all(MYSQLI_ASSOC) as $a) {
        $bd_allocs[$a['budget_id']][] = $a;
    }
    $s->close();
} catch (Exception $e) {
    error_log("budgets_breakdown – allocations query: " . $e->getMessage());
}

// ─── 2. Aggregate everything in PHP ────────────────────────────────────────
$bd = [
    'total_budget'    => 0,
    'total_used'      => 0,
    'total_remaining' => 0,
    'approved_count'  => 0,
    'pending_count'   => 0,
    'rejected_count'  => 0,
    'by_dept'         => [],   // dept => [allocated, used, remaining, count]
    'by_period'       => [],   // period => [allocated, used, remaining, count]
    'rows'            => [],   // enriched row list used by all three modals
];

foreach ($bd_budgets as $r) {
    $alloc   = (float)$r['amount_allocated'];
    $used    = (float)$r['amount_used'];
    $remain  = $alloc - $used;
    $util    = $alloc > 0 ? ($used / $alloc) * 100 : 0.0;
    $dept    = $r['department'];
    $period  = $r['period'];
    $status  = strtolower($r['approval_status'] ?? 'pending');

    $bd['total_budget']    += $alloc;
    $bd['total_used']      += $used;
    $bd['total_remaining'] += $remain;

    if ($status === 'approved') $bd['approved_count']++;
    elseif ($status === 'rejected') $bd['rejected_count']++;
    else $bd['pending_count']++;

    // by department
    if (!isset($bd['by_dept'][$dept]))
        $bd['by_dept'][$dept] = ['allocated' => 0, 'used' => 0, 'remaining' => 0, 'count' => 0];
    $bd['by_dept'][$dept]['allocated'] += $alloc;
    $bd['by_dept'][$dept]['used']      += $used;
    $bd['by_dept'][$dept]['remaining'] += $remain;
    $bd['by_dept'][$dept]['count']++;

    // by period
    if (!isset($bd['by_period'][$period]))
        $bd['by_period'][$period] = ['allocated' => 0, 'used' => 0, 'remaining' => 0, 'count' => 0];
    $bd['by_period'][$period]['allocated'] += $alloc;
    $bd['by_period'][$period]['used']      += $used;
    $bd['by_period'][$period]['remaining'] += $remain;
    $bd['by_period'][$period]['count']++;

    // enriched row (shared by all three detail tables)
    $bd['rows'][] = [
        'id'               => $r['id'],
        'department'       => $dept,
        'cost_center'      => $r['cost_center'],
        'period'           => $period,
        'allocated'        => $alloc,
        'used'             => $used,
        'remaining'        => $remain,
        'utilization_pct'  => $util,
        'remaining_pct'    => $alloc > 0 ? ($remain / $alloc) * 100 : 0.0,
        'approval_status'  => $r['approval_status'] ?: 'Pending',
        'approved_by'      => $r['approved_by'] ?: '',
        'description'      => $r['description'] ?: '',
        'created_at'       => $r['created_at'],
        'allocations'      => $bd_allocs[$r['id']] ?? [],
    ];
}

$bd['overall_utilization'] = $bd['total_budget'] > 0
    ? ($bd['total_used'] / $bd['total_budget']) * 100 : 0.0;
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     SHARED STYLES  (scoped with bd- prefix where needed)
     ═══════════════════════════════════════════════════════════════════════════ -->
<style>
  /* section wrapper */
  .bd-section {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    overflow: hidden;
  }
  /* section title bar */
  .bd-section-head {
    background: #e9ecef;
    border-bottom: 1px solid #dee2e6;
    padding: 0.5rem 0.85rem;
    font-weight: 600;
    font-size: .88rem;
    color: #495057;
  }
  /* inner padding for every direct child inside a section */
  .bd-section-body { padding: 0.85rem; }

  /* formula / code box */
  .bd-formula {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.6rem 0.85rem;
    font-size: .84rem;
    line-height: 1.65;
  }
  .bd-formula strong { color: #0d6efd; }
  .bd-formula em    { color: #6c757d; }

  /* stat cards row inside modals */
  .bd-stat { text-align: center; }
  .bd-stat .bd-stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.1; }
  .bd-stat .bd-stat-label { font-size: .78rem; color: #6c757d; margin-top: 2px; }

  /* detail tables */
  .bd-table { font-size: .84rem; }
  .bd-table th { background: #f8f9fa; font-weight: 600; white-space: nowrap; }
  .bd-table td { vertical-align: middle; }

  /* small percent pill */
  .bd-pct {
    display: inline-block;
    background: #f0f0f0;
    border-radius: 0.2rem;
    padding: 0.15em 0.5em;
    font-size: .82rem;
    font-weight: 600;
  }
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 1 – TOTAL BUDGET
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="bdModal_budget" tabindex="-1" aria-labelledby="bdModal_budgetLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="bdModal_budgetLabel"><i class="bi bi-pie-chart me-2"></i>Total Budget Breakdown</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- What is it? -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-info-circle me-2"></i>What is "Total Budget"?</div>
          <div class="bd-section-body">
            <p class="mb-2"><strong>Total Budget</strong> is the sum of every <code>amount_allocated</code> value across all budget entries — every department, cost center, and period.</p>
            <div class="bd-formula">
              <strong>Formula:</strong> Total Budget = SUM(amount_allocated) from <code>budgets</code><br>
              <em>Includes all periods (Daily, Bi-weekly, Monthly, Annually) and all approval statuses.</em>
            </div>
          </div>
        </div>

        <!-- Stats row -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-bar-chart me-2"></i>Budget Statistics</div>
          <div class="bd-section-body">
            <div class="row">
              <div class="col-6 col-md-3 bd-stat mb-3">
                <div class="bd-stat-value text-primary"><?= peso($bd['total_budget']) ?></div>
                <div class="bd-stat-label">Total Allocated</div>
              </div>
              <div class="col-6 col-md-3 bd-stat mb-3">
                <div class="bd-stat-value text-secondary"><?= count($bd['rows']) ?></div>
                <div class="bd-stat-label">Budget Entries</div>
              </div>
              <div class="col-6 col-md-3 bd-stat mb-3">
                <div class="bd-stat-value text-success"><?= $bd['approved_count'] ?></div>
                <div class="bd-stat-label">Approved</div>
              </div>
              <div class="col-6 col-md-3 bd-stat mb-3">
                <div class="bd-stat-value text-warning"><?= $bd['pending_count'] ?></div>
                <div class="bd-stat-label">Pending</div>
              </div>
            </div>
          </div>
        </div>

        <!-- By Department -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-building me-2"></i>Budget by Department</div>
          <div class="bd-section-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover bd-table mb-0">
                <thead>
                  <tr>
                    <th>Department</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Used</th>
                    <th class="text-end">Remaining</th>
                    <th class="text-end">% of Total</th>
                    <th class="text-center">Count</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bd['by_dept'] as $dept => $d):
                      $pctOfTotal = $bd['total_budget'] > 0 ? ($d['allocated'] / $bd['total_budget']) * 100 : 0;
                  ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($dept) ?></strong></td>
                    <td class="text-end"><?= peso($d['allocated']) ?></td>
                    <td class="text-end"><?= peso($d['used']) ?></td>
                    <td class="text-end <?= $d['remaining'] < 0 ? 'text-danger' : 'text-success' ?>"><?= peso($d['remaining']) ?></td>
                    <td class="text-end"><span class="bd-pct"><?= number_format($pctOfTotal, 1) ?>%</span></td>
                    <td class="text-center"><span class="badge bg-secondary"><?= $d['count'] ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- By Period -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-calendar3 me-2"></i>Budget by Period</div>
          <div class="bd-section-body">
            <div class="row">
              <?php foreach ($bd['by_period'] as $period => $d):
                  $pctOfTotal = $bd['total_budget'] > 0 ? ($d['allocated'] / $bd['total_budget']) * 100 : 0;
              ?>
              <div class="col-md-4 mb-3">
                <div class="card h-100">
                  <div class="card-body">
                    <h6 class="card-title fw-semibold"><?= htmlspecialchars($period) ?></h6>
                    <p class="mb-1 small"><strong>Allocated:</strong> <?= peso($d['allocated']) ?></p>
                    <p class="mb-1 small"><strong>Used:</strong> <?= peso($d['used']) ?></p>
                    <p class="mb-1 small"><strong>Remaining:</strong> <?= peso($d['remaining']) ?></p>
                    <p class="mb-0 small text-muted"><?= $d['count'] ?> budget<?= $d['count'] !== 1 ? 's' : '' ?> (<?= number_format($pctOfTotal, 1) ?>% of total)</p>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- All budget rows -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-list-ul me-2"></i>All Budget Entries</div>
          <div class="bd-section-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover bd-table mb-0">
                <thead>
                  <tr>
                    <th>Department</th>
                    <th>Cost Center</th>
                    <th>Period</th>
                    <th class="text-end">Allocated</th>
                    <th>Approval</th>
                    <th>Approved By</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bd['rows'] as $row):
                      $sc = match(strtolower($row['approval_status'])) {
                          'approved' => 'success',
                          'rejected' => 'danger',
                          default    => 'warning',
                      };
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['cost_center']) ?></td>
                    <td><span class="badge bg-info text-white"><?= htmlspecialchars($row['period']) ?></span></td>
                    <td class="text-end"><strong class="text-primary"><?= peso($row['allocated']) ?></strong></td>
                    <td><span class="badge bg-<?= $sc ?>"><?= htmlspecialchars($row['approval_status']) ?></span></td>
                    <td><?= htmlspecialchars($row['approved_by'] ?: 'N/A') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <th colspan="3" class="text-end">TOTAL:</th>
                    <th class="text-end"><strong class="text-primary"><?= peso($bd['total_budget']) ?></strong></th>
                    <th colspan="2"></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

      </div><!-- /modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 2 – TOTAL SPENT
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="bdModal_spent" tabindex="-1" aria-labelledby="bdModal_spentLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="bdModal_spentLabel"><i class="bi bi-cash-stack me-2"></i>Total Spent Breakdown</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- What is it? -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-info-circle me-2"></i>What is "Total Spent"?</div>
          <div class="bd-section-body">
            <p class="mb-2"><strong>Total Spent</strong> is the sum of every <code>amount_used</code> value — your actual expenditure tracked against allocated budgets.</p>
            <div class="bd-formula">
              <strong>Formula:</strong> Total Spent = SUM(amount_used) from <code>budgets</code><br>
              <em>Comparing this with Total Budget shows your overall utilization rate.</em>
            </div>
          </div>
        </div>

        <!-- Stats row -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-bar-chart me-2"></i>Spending Statistics</div>
          <div class="bd-section-body">
            <div class="row">
              <div class="col-6 col-md-4 bd-stat mb-3">
                <div class="bd-stat-value text-danger"><?= peso($bd['total_used']) ?></div>
                <div class="bd-stat-label">Total Spent</div>
              </div>
              <div class="col-6 col-md-4 bd-stat mb-3">
                <div class="bd-stat-value text-info"><?= number_format($bd['overall_utilization'], 1) ?>%</div>
                <div class="bd-stat-label">Overall Utilization</div>
              </div>
              <div class="col-6 col-md-4 bd-stat mb-3">
                <div class="bd-stat-value text-primary"><?= peso($bd['total_budget']) ?></div>
                <div class="bd-stat-label">Total Budget</div>
              </div>
            </div>
            <!-- utilization progress bar -->
            <div class="progress" style="height:22px;">
              <?php $uPct = min($bd['overall_utilization'], 100);
                    $uCol = $bd['overall_utilization'] > 100 ? 'danger' : ($bd['overall_utilization'] > 80 ? 'warning' : 'success'); ?>
              <div class="progress-bar bg-<?= $uCol ?> fw-semibold" style="width:<?= $uPct ?>%; font-size:.78rem;"
                   role="progressbar" aria-valuenow="<?= $uPct ?>" aria-valuemin="0" aria-valuemax="100">
                <?= number_format($bd['overall_utilization'], 1) ?>%
              </div>
            </div>
          </div>
        </div>

        <!-- By Department -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-building me-2"></i>Spending by Department</div>
          <div class="bd-section-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover bd-table mb-0">
                <thead>
                  <tr>
                    <th>Department</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Spent</th>
                    <th class="text-end">Utilization</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bd['by_dept'] as $dept => $d):
                      $util = $d['allocated'] > 0 ? ($d['used'] / $d['allocated']) * 100 : 0;
                      $sc   = $util > 100 ? 'danger' : ($util > 80 ? 'warning' : 'success');
                      $st   = $util > 100 ? 'Overspent' : ($util > 80 ? 'High Usage' : 'On Track');
                  ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($dept) ?></strong></td>
                    <td class="text-end"><?= peso($d['allocated']) ?></td>
                    <td class="text-end"><strong class="text-danger"><?= peso($d['used']) ?></strong></td>
                    <td class="text-end"><span class="bd-pct"><?= number_format($util, 1) ?>%</span></td>
                    <td><span class="badge bg-<?= $sc ?>"><?= $st ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- All rows -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-list-ul me-2"></i>All Spending Records</div>
          <div class="bd-section-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover bd-table mb-0">
                <thead>
                  <tr>
                    <th>Department</th>
                    <th>Cost Center</th>
                    <th>Period</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Used</th>
                    <th class="text-end">Utilization</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bd['rows'] as $row):
                      $sc = $row['utilization_pct'] > 100 ? 'danger' : ($row['utilization_pct'] > 80 ? 'warning' : 'success');
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['cost_center']) ?></td>
                    <td><span class="badge bg-info text-white"><?= htmlspecialchars($row['period']) ?></span></td>
                    <td class="text-end"><?= peso($row['allocated']) ?></td>
                    <td class="text-end"><strong class="text-danger"><?= peso($row['used']) ?></strong></td>
                    <td class="text-end"><span class="badge bg-<?= $sc ?>"><?= number_format($row['utilization_pct'], 1) ?>%</span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <th colspan="4" class="text-end">TOTAL SPENT:</th>
                    <th class="text-end"><strong class="text-danger"><?= peso($bd['total_used']) ?></strong></th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- utilization formula recap -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-calculator me-2"></i>Utilization Calculation</div>
          <div class="bd-section-body">
            <div class="bd-formula">
              <strong>Utilization %</strong> = (Amount Used ÷ Amount Allocated) × 100<br><br>
              <strong>Overall:</strong> (<?= peso($bd['total_used']) ?> ÷ <?= peso($bd['total_budget']) ?>) × 100 = <strong><?= number_format($bd['overall_utilization'], 2) ?>%</strong>
            </div>
          </div>
        </div>

      </div><!-- /modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 3 – REMAINING BUDGET
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="bdModal_remaining" tabindex="-1" aria-labelledby="bdModal_remainingLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header text-white" style="background:#198754;">
        <h5 class="modal-title" id="bdModal_remainingLabel"><i class="bi bi-wallet2 me-2"></i>Remaining Budget Breakdown</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- What is it? -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-info-circle me-2"></i>What is "Remaining Budget"?</div>
          <div class="bd-section-body">
            <p class="mb-2"><strong>Remaining Budget</strong> is how much money is still available to spend. A negative value means the budget has been exceeded.</p>
            <div class="bd-formula">
              <strong>Formula:</strong> Remaining = Amount Allocated − Amount Used<br>
              Total Remaining = SUM(amount_allocated − amount_used) from <code>budgets</code><br>
              <em>Negative values indicate overspending beyond the allocated amount.</em>
            </div>
          </div>
        </div>

        <!-- Stats row -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-bar-chart me-2"></i>Remaining Budget Statistics</div>
          <div class="bd-section-body">
            <?php
              $overspentCount = 0;
              foreach ($bd['rows'] as $row) if ($row['remaining'] < 0) $overspentCount++;
              $remPct = $bd['total_budget'] > 0 ? ($bd['total_remaining'] / $bd['total_budget']) * 100 : 0;
            ?>
            <div class="row">
              <div class="col-6 col-md-4 bd-stat mb-3">
                <div class="bd-stat-value <?= $bd['total_remaining'] < 0 ? 'text-danger' : 'text-success' ?>"><?= peso($bd['total_remaining']) ?></div>
                <div class="bd-stat-label">Total Remaining</div>
              </div>
              <div class="col-6 col-md-4 bd-stat mb-3">
                <div class="bd-stat-value text-info"><?= number_format($remPct, 1) ?>%</div>
                <div class="bd-stat-label">% Unspent</div>
              </div>
              <div class="col-6 col-md-4 bd-stat mb-3">
                <div class="bd-stat-value text-danger"><?= $overspentCount ?></div>
                <div class="bd-stat-label">Overspent Budgets</div>
              </div>
            </div>
          </div>
        </div>

        <!-- By Department -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-building me-2"></i>Remaining by Department</div>
          <div class="bd-section-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover bd-table mb-0">
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
                  <?php foreach ($bd['by_dept'] as $dept => $d):
                      $rp = $d['allocated'] > 0 ? ($d['remaining'] / $d['allocated']) * 100 : 0;
                  ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($dept) ?></strong></td>
                    <td class="text-end"><?= peso($d['allocated']) ?></td>
                    <td class="text-end"><?= peso($d['used']) ?></td>
                    <td class="text-end <?= $d['remaining'] < 0 ? 'text-danger' : 'text-success' ?>"><strong><?= peso($d['remaining']) ?></strong></td>
                    <td class="text-end"><span class="bd-pct"><?= number_format($rp, 1) ?>%</span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- All rows sorted lowest-remaining first -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-list-ul me-2"></i>All Budget Balances</div>
          <div class="bd-section-body">
            <div class="table-responsive">
              <?php
                // sort: lowest remaining first so critical budgets are on top
                usort($bd['rows'], fn($a, $b) => $a['remaining'] <=> $b['remaining']);
              ?>
              <table class="table table-sm table-bordered table-hover bd-table mb-0">
                <thead>
                  <tr>
                    <th>Department</th>
                    <th>Cost Center</th>
                    <th>Period</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Remaining</th>
                    <th class="text-end">% Left</th>
                    <th>Health</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bd['rows'] as $row):
                      if ($row['remaining'] < 0)            { $hc = 'danger';  $ht = 'Overspent'; }
                      elseif ($row['remaining_pct'] < 10)   { $hc = 'danger';  $ht = 'Critical'; }
                      elseif ($row['remaining_pct'] < 25)   { $hc = 'warning'; $ht = 'Low'; }
                      else                                  { $hc = 'success'; $ht = 'Healthy'; }
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['cost_center']) ?></td>
                    <td><span class="badge bg-info text-white"><?= htmlspecialchars($row['period']) ?></span></td>
                    <td class="text-end"><?= peso($row['allocated']) ?></td>
                    <td class="text-end <?= $row['remaining'] < 0 ? 'text-danger' : 'text-success' ?>"><strong><?= peso($row['remaining']) ?></strong></td>
                    <td class="text-end"><span class="bd-pct"><?= number_format($row['remaining_pct'], 1) ?>%</span></td>
                    <td><span class="badge bg-<?= $hc ?>"><?= $ht ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <th colspan="4" class="text-end">TOTAL REMAINING:</th>
                    <th class="text-end <?= $bd['total_remaining'] < 0 ? 'text-danger' : 'text-success' ?>"><strong><?= peso($bd['total_remaining']) ?></strong></th>
                    <th colspan="2"></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- relationship recap -->
        <div class="bd-section">
          <div class="bd-section-head"><i class="bi bi-diagram-3 me-2"></i>How the Three Numbers Connect</div>
          <div class="bd-section-body">
            <div class="bd-formula">
              <strong>Total Budget</strong> (<?= peso($bd['total_budget']) ?>) − <strong>Total Spent</strong> (<?= peso($bd['total_used']) ?>) = <strong>Remaining</strong> (<?= peso($bd['total_remaining']) ?>) ✓
            </div>
          </div>
        </div>

      </div><!-- /modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
