<!-- Enhanced Budget Forecast Modal - UPDATED WITH BI-WEEKLY SUPPORT & DETAILED STATISTICS -->
<!-- budget_forecast_modal.php -->
<div class="modal fade" id="budgetForecastModal" tabindex="-1" aria-labelledby="budgetForecastModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="budgetForecastModalLabel">
          <i class="bi bi-graph-up-arrow me-2"></i>AI Budget Forecast Assistant
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <!-- AI Status Indicator -->
        <div class="alert alert-info d-flex align-items-center mb-4" id="aiStatus">
          <div class="spinner-border spinner-border-sm me-2" role="status" id="aiSpinner">
            <span class="visually-hidden">Loading...</span>
          </div>
          <strong>AI Engine:</strong> <span id="aiStatusText">Initializing neural network...</span>
        </div>

        <!-- Data Analysis Section with Enhanced Statistics -->
        <div class="card mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-database-fill-check me-2"></i>Real-time Data Analysis</h6>
            <small class="text-muted">Last updated: <span id="lastUpdate">--</span></small>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-lg-8">
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><i class="bi bi-cpu me-2"></i>Analyzing budget patterns...</span>
                    <span class="badge bg-success" id="recordsAnalyzed">0 records</span>
                  </div>
                  <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-gradient progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="analysisProgress"></div>
                  </div>
                  <small class="text-muted">Processing departmental spending patterns, period-specific variations, and bi-weekly payroll cycles</small>
                </div>
              </div>
              <div class="col-lg-4">
                <div class="bg-light p-3 rounded">
                  <div class="row g-2 text-center">
                    <div class="col-3">
                      <div class="fw-bold text-primary" id="totalDepartments">0</div>
                      <small class="text-muted">Departments</small>
                    </div>
                    <div class="col-3">
                      <div class="fw-bold text-success" id="totalCostCenters">0</div>
                      <small class="text-muted">Cost Centers</small>
                    </div>
                    <div class="col-3">
                      <div class="fw-bold text-warning" id="analysisMonths">0</div>
                      <small class="text-muted">Period Types</small>
                    </div>
                    <div class="col-3">
                      <div class="fw-bold text-info" id="approvedCount">0</div>
                      <small class="text-muted">Approved</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Forecast Configuration -->
        <div class="card mb-4">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-sliders me-2"></i>AI Forecast Parameters</h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label fw-semibold">
                  <i class="bi bi-calendar-range me-1"></i>Forecast Timeline
                </label>
                <select class="form-select" id="forecastPeriod">
                  <option value="1">Next Month</option>
                  <option value="3" selected>Next 3 Months</option>
                  <option value="6">Next 6 Months</option>
                  <option value="12">Next 12 Months</option>
                </select>
                <small class="text-muted">Projection timeframe</small>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">
                  <i class="bi bi-building me-1"></i>Department Focus
                </label>
                <select class="form-select" id="departmentFilter">
                  <option value="all" selected>All Departments</option>
                  <option value="HR">HR Department</option>
                  <option value="Core">Core Department</option>
                </select>
                <small class="text-muted">Target department</small>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">
                  <i class="bi bi-clock-history me-1"></i>Budget Period Filter
                </label>
                <select class="form-select" id="budgetFrequency">
                  <option value="all" selected>All Periods</option>
                  <option value="Daily">Daily Budgets Only</option>
                  <option value="Bi-weekly">Bi-weekly (Payroll)</option>
                  <option value="Monthly">Monthly Budgets Only</option>
                  <option value="Annually">Annual Budgets Only</option>
                </select>
                <small class="text-muted">Budget allocation period</small>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">
                  <i class="bi bi-robot me-1"></i>AI Model Type
                </label>
                <div class="btn-group w-100" role="group">
                  <input type="radio" class="btn-check" name="aiModel" id="conservative" value="conservative">
                  <label class="btn btn-outline-secondary btn-sm" for="conservative" title="Lower growth predictions with higher safety buffers">
                    <i class="bi bi-shield-check"></i> Conservative
                  </label>
                  <input type="radio" class="btn-check" name="aiModel" id="balanced" value="balanced" checked>
                  <label class="btn btn-outline-primary btn-sm" for="balanced" title="Balanced predictions with moderate growth">
                    <i class="bi bi-bar-chart"></i> Balanced
                  </label>
                  <input type="radio" class="btn-check" name="aiModel" id="aggressive" value="aggressive">
                  <label class="btn btn-outline-warning btn-sm" for="aggressive" title="Higher growth predictions with lower safety buffers">
                    <i class="bi bi-graph-up"></i> Aggressive
                  </label>
                </div>
                <small class="text-muted">Prediction approach</small>
              </div>
            </div>
            
            <!-- Cost Center Filter (Dynamic based on Department) -->
            <div class="row mt-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="costCenterFilter">
                  <i class="bi bi-diagram-3 me-1"></i>Cost Center Filter (Optional)
                </label>
                <select class="form-select" id="costCenterFilter">
                  <option value="all" selected>All Cost Centers</option>
                  <!-- Populated dynamically from budget data -->
                </select>
                <small class="text-muted">
                  <i class="bi bi-info-circle me-1"></i>Shows all cost centers from your budget data
                </small>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">
                  <i class="bi bi-check-circle me-1"></i>Approval Status Filter
                </label>
                <select class="form-select" id="approvalFilter">
                  <option value="all" selected>All Statuses</option>
                  <option value="Approved">Approved Only</option>
                  <option value="Pending">Pending Only</option>
                  <option value="Rejected">Rejected Only</option>
                </select>
                <small class="text-muted">Filter by approval status</small>
              </div>
            </div>
            
            <!-- Scenario Examples with Bi-weekly -->
            <div class="row mt-3">
              <div class="col-12">
                <div class="alert alert-light mb-3">
                  <strong><i class="bi bi-lightbulb me-2"></i>Scenario Examples:</strong>
                  <ul class="mb-0 mt-2 small">
                    <li><strong>HR + Bi-weekly + 3 Months + Balanced:</strong> Payroll forecast for next quarter with moderate projections</li>
                    <li><strong>HR + Monthly + 6 Months + Conservative:</strong> Training & benefits planning with safety buffers</li>
                    <li><strong>Core + Daily + 1 Month + Conservative:</strong> Short-term operational budget with minimal changes</li>
                    <li><strong>Core + Annually + 12 Months + Aggressive:</strong> Long-term asset & fleet planning with higher growth</li>
                    <li><strong>All + All + 6 Months + Balanced:</strong> Comprehensive analysis across all departments and periods</li>
                  </ul>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-12">
                <button class="btn btn-primary btn-lg w-100" onclick="generateAIForecast()" id="generateBtn">
                  <span class="spinner-border spinner-border-sm d-none me-2" id="forecastSpinner"></span>
                  <i class="bi bi-lightning-charge-fill me-2"></i>Generate AI-Powered Forecast
                </button>
                <small class="text-muted d-block text-center mt-2">
                  AI will analyze spending patterns based on your selected scenario
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Historical Trends Analysis with Detailed Statistics -->
        <div class="card mb-4" id="historicalTrends" style="display: none;">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Historical Performance Analysis & Statistics</h6>
          </div>
          <div class="card-body">
            <!-- Primary Metrics -->
            <div class="row mb-3">
              <div class="col-md-3">
                <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                  <div class="h4 text-primary mb-1" id="avgMonthlyBudget">₱0</div>
                  <small class="text-muted">Monthly Equivalent Budget</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                  <div class="h4 mb-1" id="utilizationTrend">
                    <span class="text-success">0%</span>
                  </div>
                  <small class="text-muted">Budget Utilization Rate</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                  <div class="h4 text-warning mb-1" id="peakSpendingPeriod">--</div>
                  <small class="text-muted">Peak Spending Period</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                  <div class="h4 text-info mb-1" id="budgetEfficiency">--</div>
                  <small class="text-muted">Efficiency Score</small>
                </div>
              </div>
            </div>

            <!-- Detailed Statistics Section -->
            <div class="row">
              <div class="col-md-12">
                <h6 class="text-muted mb-3"><i class="bi bi-clipboard-data me-2"></i>Detailed Budget Statistics</h6>
              </div>
            </div>
            <div class="row g-3">
              <!-- Department Breakdown -->
              <div class="col-md-6">
                <div class="card border">
                  <div class="card-body">
                    <h6 class="card-title text-primary">
                      <i class="bi bi-building me-2"></i>Department Breakdown
                    </h6>
                    <div id="departmentStats" class="small">
                      <!-- Populated by JavaScript -->
                    </div>
                  </div>
                </div>
              </div>

              <!-- Period Distribution -->
              <div class="col-md-6">
                <div class="card border">
                  <div class="card-body">
                    <h6 class="card-title text-success">
                      <i class="bi bi-calendar3 me-2"></i>Period Distribution
                    </h6>
                    <div id="periodStats" class="small">
                      <!-- Populated by JavaScript -->
                    </div>
                  </div>
                </div>
              </div>

              <!-- Cost Center Analysis -->
              <div class="col-md-6">
                <div class="card border">
                  <div class="card-body">
                    <h6 class="card-title text-warning">
                      <i class="bi bi-diagram-3 me-2"></i>Top Cost Centers
                    </h6>
                    <div id="costCenterStats" class="small">
                      <!-- Populated by JavaScript -->
                    </div>
                  </div>
                </div>
              </div>

              <!-- Approval Status Overview -->
              <div class="col-md-6">
                <div class="card border">
                  <div class="card-body">
                    <h6 class="card-title text-info">
                      <i class="bi bi-check-circle me-2"></i>Approval Status Overview
                    </h6>
                    <div id="approvalStats" class="small">
                      <!-- Populated by JavaScript -->
                    </div>
                  </div>
                </div>
              </div>

              <!-- Spending Trends -->
              <div class="col-md-12">
                <div class="card border">
                  <div class="card-body">
                    <h6 class="card-title text-danger">
                      <i class="bi bi-graph-down me-2"></i>Spending Trends & Alerts
                    </h6>
                    <div id="spendingTrends" class="small">
                      <!-- Populated by JavaScript -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- AI Forecast Results -->
        <div class="card mb-4" id="forecastResults" style="display: none;">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-stars me-2"></i>AI-Generated Budget Forecast Results</h6>
          </div>
          <div class="card-body">
            <!-- Forecast Summary Cards -->
            <div class="row mb-4">
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-primary h-100">
                  <div class="card-body text-center">
                    <i class="bi bi-cash-stack text-primary fs-3 mb-2"></i>
                    <div class="h3 text-primary mb-2" id="projectedTotalBudget">₱0</div>
                    <h6 class="card-title">Projected Total Need</h6>
                    <small class="text-muted">Next <span id="projectedPeriodText">3 months</span></small>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-success h-100">
                  <div class="card-body text-center">
                    <i class="bi bi-piggy-bank text-success fs-3 mb-2"></i>
                    <div class="h3 text-success mb-2" id="recommendedBudget">₱0</div>
                    <h6 class="card-title">Recommended Budget</h6>
                    <small class="text-muted">With safety buffer</small>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-warning h-100">
                  <div class="card-body text-center">
                    <i class="bi bi-arrow-up-circle text-warning fs-3 mb-2"></i>
                    <div class="h3 text-warning mb-2" id="budgetVariance">+0%</div>
                    <h6 class="card-title">Budget Change</h6>
                    <small class="text-muted">From current allocation</small>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-info h-100">
                  <div class="card-body text-center">
                    <i class="bi bi-shield-check text-info fs-3 mb-2"></i>
                    <div class="h3 text-info mb-2" id="aiConfidence">0%</div>
                    <h6 class="card-title">AI Confidence</h6>
                    <small class="text-muted">Prediction accuracy</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Department-wise Forecast Table -->
            <div class="row">
              <div class="col-12">
                <h6 class="mb-3"><i class="bi bi-table me-2"></i>Department-wise Forecast Breakdown</h6>
                <div class="table-responsive">
                  <table class="table table-hover table-bordered align-middle">
                    <thead class="table-primary">
                      <tr>
                        <th style="width: 15%;">Department</th>
                        <th style="width: 12%;">Current Budget</th>
                        <th style="width: 12%;">Current Usage</th>
                        <th style="width: 10%;">Usage Rate</th>
                        <th style="width: 15%;">AI Projected Need</th>
                        <th style="width: 15%;">Recommended Budget</th>
                        <th style="width: 10%;">Change</th>
                        <th style="width: 11%;">Risk Level</th>
                      </tr>
                    </thead>
                    <tbody id="departmentForecastTable">
                      <!-- Populated by JavaScript -->
                    </tbody>
                    <tfoot class="table-secondary">
                      <tr class="fw-bold">
                        <td>TOTAL</td>
                        <td id="totalCurrentBudget">₱0</td>
                        <td id="totalCurrentUsage">₱0</td>
                        <td id="overallUsageRate">0%</td>
                        <td id="totalProjectedNeed">₱0</td>
                        <td id="totalRecommendedBudget">₱0</td>
                        <td id="totalChange">0%</td>
                        <td id="overallRisk">Low</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- AI Insights & Strategic Recommendations -->
        <div class="card mb-4" id="aiInsightsSection" style="display: none;">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-lightbulb-fill me-2"></i>AI Strategic Insights & Recommendations</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-lg-6">
                <div class="h6 text-success mb-3">
                  <i class="bi bi-check-circle-fill me-2"></i>AI-Generated Recommendations
                </div>
                <div class="list-group list-group-flush" id="aiRecommendations">
                  <!-- Populated by JavaScript -->
                </div>
              </div>
              <div class="col-lg-6">
                <div class="h6 text-warning mb-3">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>Identified Risk Factors
                </div>
                <div class="list-group list-group-flush" id="riskFactors">
                  <!-- Populated by JavaScript -->
                </div>
              </div>
            </div>
            <div class="row mt-4">
              <div class="col-12">
                <div class="alert alert-info border-0">
                  <div class="d-flex">
                    <div class="me-3">
                      <div class="bg-info bg-opacity-25 rounded-circle p-3 d-inline-flex">
                        <i class="bi bi-robot fs-4"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="alert-heading"><i class="bi bi-file-earmark-text me-2"></i>AI Executive Summary</h6>
                      <p class="mb-0" id="aiExecutiveSummary">
                        Analyzing your budget data to generate strategic insights...
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer bg-light">
        <div class="d-flex justify-content-between w-100 align-items-center flex-wrap gap-2">
          <small class="text-muted">
            <i class="bi bi-cpu-fill me-1"></i>AI Model: Multi-Scenario Budget Forecasting v3.0 | 
            <i class="bi bi-database me-1"></i>Data Points: <span id="dataPointsUsed">0</span> | 
            <i class="bi bi-calendar-check me-1"></i>Includes: Bi-weekly Payroll Analysis
          </small>
          <div class="btn-group">
            <button class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i>Close
            </button>
            <button class="btn btn-outline-primary d-none" id="exportForecastBtn" onclick="exportForecastReport()">
              <i class="bi bi-file-earmark-arrow-down me-1"></i>Export Report
            </button>
            <button class="btn btn-success d-none" id="applyForecastBtn" onclick="applyForecast()">
              <i class="bi bi-check-circle me-1"></i>Apply Forecast
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
