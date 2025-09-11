<!-- Enhanced Budget Forecast Modal with All Periods Support -->
<!-- budget_forecast_modal.php -->
<div class="modal fade" id="budgetForecastModal" tabindex="-1" aria-labelledby="budgetForecastModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="budgetForecastModalLabel">AI Budget Forecast Assistant</h5>
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

        <!-- Data Analysis Section -->
        <div class="card mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Real-time Data Analysis</h6>
            <small class="text-muted">Last updated: <span id="lastUpdate">--</span></small>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-lg-8">
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Analyzing budget patterns...</span>
                    <span class="badge bg-success" id="recordsAnalyzed">0 records</span>
                  </div>
                  <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-gradient progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="analysisProgress"></div>
                  </div>
                  <small class="text-muted">Processing departmental spending patterns and period-specific variations</small>
                </div>
              </div>
              <div class="col-lg-4">
                <div class="bg-light p-3 rounded">
                  <div class="row g-2 text-center">
                    <div class="col-4">
                      <div class="fw-bold text-primary" id="totalDepartments">0</div>
                      <small class="text-muted">Departments</small>
                    </div>
                    <div class="col-4">
                      <div class="fw-bold text-success" id="totalCostCenters">0</div>
                      <small class="text-muted">Cost Centers</small>
                    </div>
                    <div class="col-4">
                      <div class="fw-bold text-warning" id="analysisMonths">0</div>
                      <small class="text-muted">Period Types</small>
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
            <h6 class="mb-0">AI Forecast Parameters</h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label fw-semibold">Forecast Timeline</label>
                <select class="form-select" id="forecastPeriod">
                  <option value="1">Next Month</option>
                  <option value="3" selected>Next 3 Months</option>
                  <option value="6">Next 6 Months</option>
                  <option value="12">Next 12 Months</option>
                </select>
                <small class="text-muted">Projection timeframe</small>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Department Focus</label>
                <select class="form-select" id="departmentFilter">
                  <option value="all" selected>All Departments</option>
                  <option value="HR2">HR2 Department</option>
                  <option value="HR4">HR4 Department</option>
                  <option value="Core 2">Core 2 Department</option>
                  <option value="Core 4">Core 4 Department</option>
                </select>
                <small class="text-muted">Target department</small>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Budget Period Filter</label>
                <select class="form-select" id="budgetFrequency">
                  <option value="all" selected>All Periods</option>
                  <option value="Daily">Daily Budgets Only</option>
                  <option value="Monthly">Monthly Budgets Only</option>
                  <option value="Annually">Annual Budgets Only</option>
                </select>
                <small class="text-muted">Budget allocation period</small>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">AI Model Type</label>
                <div class="btn-group w-100" role="group">
                  <input type="radio" class="btn-check" name="aiModel" id="conservative" value="conservative">
                  <label class="btn btn-outline-secondary btn-sm" for="conservative" title="Lower growth predictions">Conservative</label>
                  <input type="radio" class="btn-check" name="aiModel" id="balanced" value="balanced" checked>
                  <label class="btn btn-outline-primary btn-sm" for="balanced" title="Balanced predictions">Balanced</label>
                  <input type="radio" class="btn-check" name="aiModel" id="aggressive" value="aggressive">
                  <label class="btn btn-outline-warning btn-sm" for="aggressive" title="Higher growth predictions">Aggressive</label>
                </div>
                <small class="text-muted">Prediction approach</small>
              </div>
            </div>
            
            <!-- Scenario Examples -->
            <div class="row mt-3">
              <div class="col-12">
                <div class="alert alert-light mb-3">
                  <strong>Scenario Examples:</strong>
                  <ul class="mb-0 mt-2 small">
                    <li><strong>HR2 + Monthly + 3 Months + Balanced:</strong> Focus on HR2 monthly budgets with moderate projections</li>
                    <li><strong>All + Daily + 1 Month + Conservative:</strong> Short-term daily budget analysis with minimal changes</li>
                    <li><strong>Core 2 + Annually + 12 Months + Aggressive:</strong> Long-term annual planning with higher growth</li>
                    <li><strong>All + All + 6 Months + Balanced:</strong> Comprehensive analysis across all departments and periods</li>
                  </ul>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-12">
                <button class="btn btn-primary btn-lg" onclick="generateAIForecast()" id="generateBtn">
                  <span class="spinner-border spinner-border-sm d-none me-2" id="forecastSpinner"></span>
                  Generate AI-Powered Forecast
                </button>
                <small class="text-muted ms-3">AI will analyze spending patterns based on your selected scenario</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Historical Trends Analysis -->
        <div class="card mb-4" id="historicalTrends" style="display: none;">
          <div class="card-header bg-light">
            <h6 class="mb-0">Historical Performance Analysis</h6>
          </div>
          <div class="card-body">
            <div class="row">
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
          </div>
        </div>

        <!-- AI Forecast Results -->
        <div class="card mb-4" id="forecastResults" style="display: none;">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0">AI-Generated Budget Forecast Results</h6>
          </div>
          <div class="card-body">
            <!-- Forecast Summary Cards -->
            <div class="row mb-4">
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-primary h-100">
                  <div class="card-body text-center">
                    <div class="h3 text-primary mb-2" id="projectedTotalBudget">₱0</div>
                    <h6 class="card-title">Projected Total Need</h6>
                    <small class="text-muted">Next <span id="projectedPeriodText">3</span> months</small>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-success h-100">
                  <div class="card-body text-center">
                    <div class="h3 text-success mb-2" id="recommendedBudget">₱0</div>
                    <h6 class="card-title">Recommended Budget</h6>
                    <small class="text-muted">With safety buffer</small>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-warning h-100">
                  <div class="card-body text-center">
                    <div class="h3 text-warning mb-2" id="budgetVariance">+0%</div>
                    <h6 class="card-title">Budget Change</h6>
                    <small class="text-muted">From current allocation</small>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-info h-100">
                  <div class="card-body text-center">
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
                <h6 class="mb-3">Department-wise Forecast Breakdown</h6>
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
            <h6 class="mb-0">AI Strategic Insights & Recommendations</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-lg-6">
                <div class="h6 text-success mb-3">AI-Generated Recommendations</div>
                <div class="list-group list-group-flush" id="aiRecommendations">
                  <!-- Populated by JavaScript -->
                </div>
              </div>
              <div class="col-lg-6">
                <div class="h6 text-warning mb-3">Identified Risk Factors</div>
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
                      <div class="bg-info bg-opacity-25 rounded-circle p-2 d-inline-flex">
                        🤖
                      </div>
                    </div>
                    <div>
                      <h6 class="alert-heading">AI Executive Summary</h6>
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
        <div class="d-flex justify-content-between w-100 align-items-center">
          <small class="text-muted">
            AI Model: Multi-Scenario Budget Forecasting v2.3 | 
            Data Points: <span id="dataPointsUsed">0</span> | 
            All Scenarios: Department, Period, Timeline, AI Model
          </small>
          <div class="btn-group">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-outline-primary d-none" id="exportForecastBtn" onclick="exportForecastReport()">
              📄 Export Report
            </button>
            <button class="btn btn-success d-none" id="applyForecastBtn" onclick="applyForecast()">
              💾 Apply Forecast
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>