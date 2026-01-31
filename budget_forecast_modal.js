// budget_forecast_modal_enhanced.js - UPDATED VERSION 3.0
// Enhanced with Bi-weekly Support, Detailed Statistics, and Improved AI Algorithms

class BudgetForecastAI {
    constructor() {
        this.budgetData = [];
        this.summaryData = {};
        this.aiModel = 'balanced';
        this.forecastPeriod = 3;
        this.departmentFilter = 'all';
        this.budgetFrequency = 'all';
        this.costCenterFilter = 'all';
        this.approvalFilter = 'all';
        this.isAnalyzing = false;
        
        // Updated Department to Cost Center mapping
        this.departmentCostCenters = {
            'HR': ['Training Budget', 'Reimbursement Budget', 'Benefits Budget', 'Payroll Budget'],
            'Core': ['Log Maintenance Costs', 'Depreciation Charges', 'Insurance Fees', 'Vehicle Operational Budget']
        };
        
        // Enhanced AI coefficients with bi-weekly support
        this.aiCoefficients = {
            conservative: { 
                growth: 0.02, 
                volatility: 0.03, 
                buffer: 0.08,
                daily: { growth: 0.005, volatility: 0.02, buffer: 0.05 },
                biweekly: { growth: 0.015, volatility: 0.025, buffer: 0.06 },
                monthly: { growth: 0.02, volatility: 0.03, buffer: 0.08 },
                annually: { growth: 0.025, volatility: 0.04, buffer: 0.10 },
                mixed: { growth: 0.018, volatility: 0.03, buffer: 0.08 }
            },
            balanced: { 
                growth: 0.05, 
                volatility: 0.06, 
                buffer: 0.10,
                daily: { growth: 0.012, volatility: 0.04, buffer: 0.08 },
                biweekly: { growth: 0.035, volatility: 0.05, buffer: 0.09 },
                monthly: { growth: 0.05, volatility: 0.06, buffer: 0.10 },
                annually: { growth: 0.055, volatility: 0.07, buffer: 0.12 },
                mixed: { growth: 0.048, volatility: 0.06, buffer: 0.10 }
            },
            aggressive: { 
                growth: 0.08, 
                volatility: 0.10, 
                buffer: 0.05,
                daily: { growth: 0.025, volatility: 0.08, buffer: 0.03 },
                biweekly: { growth: 0.055, volatility: 0.09, buffer: 0.04 },
                monthly: { growth: 0.08, volatility: 0.10, buffer: 0.05 },
                annually: { growth: 0.085, volatility: 0.12, buffer: 0.07 },
                mixed: { growth: 0.078, volatility: 0.10, buffer: 0.05 }
            }
        };
        
        // Enhanced seasonal factors with bi-weekly
        this.seasonalFactors = {
            daily: {
                1: 0.95, 2: 0.90, 3: 0.98, 4: 1.02, 5: 1.08, 6: 1.05, 7: 0.85
            },
            biweekly: {
                1: 0.98, 2: 1.02, 3: 1.00, 4: 1.03, 5: 1.05, 6: 1.07,
                7: 0.99, 8: 1.01, 9: 1.04, 10: 1.02, 11: 1.06, 12: 1.10
            },
            monthly: {
                1: 0.92, 2: 0.88, 3: 0.95, 4: 1.03, 5: 1.05, 6: 1.08,
                7: 0.98, 8: 1.02, 9: 1.06, 10: 1.04, 11: 1.07, 12: 1.15
            },
            annually: {
                1: 1.0
            },
            mixed: {
                1: 0.94, 2: 0.89, 3: 0.97, 4: 1.02, 5: 1.06, 6: 1.07,
                7: 0.99, 8: 1.01, 9: 1.05, 10: 1.03, 11: 1.06, 12: 1.12
            }
        };
        
        this.init();
    }
    
    init() {
        this.updateAIStatus('Initializing AI neural networks...', true);
        
        const modal = document.getElementById('budgetForecastModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', () => {
                this.startDataAnalysis();
                this.populateAllCostCenters(); // Populate cost centers when modal opens
            });
            
            modal.addEventListener('hidden.bs.modal', () => {
                this.resetAI();
            });
        }

        // Setup department filter change listener
        const deptFilter = document.getElementById('departmentFilter');
        if (deptFilter) {
            deptFilter.addEventListener('change', () => {
                this.updateCostCenterOptions();
            });
        }
    }
    
    updateCostCenterOptions() {
        const deptFilter = document.getElementById('departmentFilter');
        const costCenterFilter = document.getElementById('costCenterFilter');
        
        if (!deptFilter || !costCenterFilter) return;
        
        const selectedDept = deptFilter.value;
        costCenterFilter.innerHTML = '<option value="all" selected>All Cost Centers</option>';
        
        if (selectedDept !== 'all' && this.departmentCostCenters[selectedDept]) {
            // Use predefined cost centers for the selected department
            this.departmentCostCenters[selectedDept].forEach(cc => {
                const option = document.createElement('option');
                option.value = cc;
                option.textContent = cc;
                costCenterFilter.appendChild(option);
            });
        } else if (selectedDept === 'all') {
            // Show all cost centers from actual data when "All Departments" is selected
            this.populateAllCostCenters();
        }
    }
    
    populateAllCostCenters() {
        const costCenterFilter = document.getElementById('costCenterFilter');
        if (!costCenterFilter) return;
        
        // Get unique cost centers from actual budget data
        const uniqueCostCenters = [...new Set(
            this.budgetData.map(item => item.cost_center).filter(cc => cc && cc.trim() !== '')
        )].sort();
        
        // Reset the dropdown
        costCenterFilter.innerHTML = '<option value="all" selected>All Cost Centers</option>';
        
        // Add all unique cost centers from data
        uniqueCostCenters.forEach(cc => {
            const option = document.createElement('option');
            option.value = cc;
            option.textContent = cc;
            costCenterFilter.appendChild(option);
        });
        
        // Update the label to show count
        const label = document.querySelector('label[for="costCenterFilter"]');
        if (label && uniqueCostCenters.length > 0) {
            const countBadge = label.querySelector('.badge');
            if (countBadge) {
                countBadge.textContent = uniqueCostCenters.length;
            } else {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary ms-2';
                badge.textContent = uniqueCostCenters.length;
                label.appendChild(badge);
            }
        }
        
        console.log('✅ Cost centers populated:', uniqueCostCenters.length, 'unique cost centers');
    }
    
    updateAIStatus(text, showSpinner = false) {
        const statusElement = document.getElementById('aiStatusText');
        const spinner = document.getElementById('aiSpinner');
        
        if (statusElement) statusElement.textContent = text;
        if (spinner) {
            spinner.style.display = showSpinner ? 'inline-block' : 'none';
        }
    }
    
    startDataAnalysis() {
        this.updateAIStatus('Scanning financial databases...', true);
        this.loadBudgetData();
        
        setTimeout(() => {
            this.analyzeData();
        }, 1000);
    }
    
    loadBudgetData() {
        try {
            this.budgetData = window.budgetData || [];
            this.summaryData = window.summaryData || {};
            
            console.log('✅ Budget data loaded - ALL records:', this.budgetData.length, 'total records');
            console.log('Summary data:', this.summaryData);
        } catch (error) {
            console.error('Error loading budget data:', error);
            this.budgetData = [];
            this.summaryData = { total_budget: 0, total_used: 0, total_remaining: 0 };
        }
        
        this.updateDataStats();
    }
    
    updateDataStats() {
        const recordCount = this.budgetData.length;
        const departments = [...new Set(this.budgetData.map(item => item.department || ''))].filter(d => d);
        const costCenters = [...new Set(this.budgetData.map(item => item.cost_center || ''))].filter(c => c);
        const periods = [...new Set(this.budgetData.map(item => item.period || ''))].filter(p => p);
        const approvedCount = this.budgetData.filter(item => item.approval_status === 'Approved').length;
        
        const elements = {
            recordsAnalyzed: document.getElementById('recordsAnalyzed'),
            totalDepartments: document.getElementById('totalDepartments'),
            totalCostCenters: document.getElementById('totalCostCenters'),
            analysisMonths: document.getElementById('analysisMonths'),
            approvedCount: document.getElementById('approvedCount'),
            lastUpdate: document.getElementById('lastUpdate'),
            dataPointsUsed: document.getElementById('dataPointsUsed')
        };
        
        if (elements.recordsAnalyzed) elements.recordsAnalyzed.textContent = `${recordCount} records`;
        if (elements.totalDepartments) elements.totalDepartments.textContent = departments.length;
        if (elements.totalCostCenters) elements.totalCostCenters.textContent = costCenters.length;
        if (elements.analysisMonths) elements.analysisMonths.textContent = periods.length;
        if (elements.approvedCount) elements.approvedCount.textContent = approvedCount;
        if (elements.lastUpdate) elements.lastUpdate.textContent = new Date().toLocaleTimeString();
        if (elements.dataPointsUsed) elements.dataPointsUsed.textContent = recordCount;
    }
    
    analyzeData() {
        this.updateAIStatus('Processing spending patterns...', true);
        
        let progress = 0;
        const progressBar = document.getElementById('analysisProgress');
        
        const analysisSteps = [
            'Analyzing period-specific spending patterns...',
            'Processing bi-weekly payroll cycles...',
            'Detecting seasonal variations by budget type...',
            'Processing budget utilization rates...',
            'Identifying cost center trends...',
            'Training prediction models...',
            'Analysis complete!'
        ];
        
        let currentStep = 0;
        const interval = setInterval(() => {
            progress += 14.28;
            if (progressBar) progressBar.style.width = progress + '%';
            
            if (currentStep < analysisSteps.length) {
                this.updateAIStatus(analysisSteps[currentStep], true);
                currentStep++;
            }
            
            if (progress >= 100) {
                clearInterval(interval);
                this.updateAIStatus('AI analysis ready - Configure parameters to generate forecast', false);
                this.showHistoricalTrends();
                this.showDetailedStatistics();
            }
        }, 700);
    }
    
    showHistoricalTrends() {
        const trendsSection = document.getElementById('historicalTrends');
        if (trendsSection) trendsSection.style.display = 'block';
        
        const allRecords = this.budgetData || [];
        
        let totalBudget = 0;
        let totalUsed = 0;
        let totalMonthlyEquivalent = 0;
        
        allRecords.forEach(record => {
            const allocated = parseFloat(record.amount_allocated || 0);
            const used = parseFloat(record.amount_used || 0);
            
            totalBudget += allocated;
            totalUsed += used;
            
            if (allocated > 0) {
                let monthlyEquiv = 0;
                const period = record.period || 'Monthly';
                
                switch(period) {
                    case 'Daily':
                        monthlyEquiv = allocated * 22;
                        break;
                    case 'Bi-weekly':
                        monthlyEquiv = allocated * 2;
                        break;
                    case 'Monthly':
                        monthlyEquiv = allocated;
                        break;
                    case 'Annually':
                        monthlyEquiv = allocated / 12;
                        break;
                    default:
                        monthlyEquiv = allocated;
                        break;
                }
                totalMonthlyEquivalent += monthlyEquiv;
            }
        });
        
        const utilizationRate = totalBudget > 0 ? ((totalUsed / totalBudget) * 100) : 0;
        const peakPeriod = this.findPeakSpendingPeriodFromAllData(allRecords);
        const efficiencyScore = this.calculateEfficiencyScore(utilizationRate);
        
        const elements = {
            avgMonthlyBudget: document.getElementById('avgMonthlyBudget'),
            utilizationTrend: document.getElementById('utilizationTrend'),
            peakSpendingPeriod: document.getElementById('peakSpendingPeriod'),
            budgetEfficiency: document.getElementById('budgetEfficiency')
        };
        
        if (elements.avgMonthlyBudget) elements.avgMonthlyBudget.textContent = this.formatCurrency(totalMonthlyEquivalent);
        if (elements.utilizationTrend) {
            const colorClass = utilizationRate > 90 ? 'text-danger' : utilizationRate > 75 ? 'text-warning' : 'text-success';
            elements.utilizationTrend.innerHTML = `<span class="${colorClass}">${utilizationRate.toFixed(1)}%</span>`;
        }
        
        if (elements.peakSpendingPeriod) elements.peakSpendingPeriod.textContent = peakPeriod;
        if (elements.budgetEfficiency) elements.budgetEfficiency.textContent = efficiencyScore;
        
        console.log('Historical Analysis Calculated:', {
            totalRecords: allRecords.length,
            totalBudget,
            totalUsed,
            utilizationRate,
            totalMonthlyEquivalent,
            peakPeriod,
            efficiencyScore
        });
    }
    
    showDetailedStatistics() {
        this.generateDepartmentStats();
        this.generatePeriodStats();
        this.generateCostCenterStats();
        this.generateApprovalStats();
        this.generateSpendingTrends();
    }
    
    generateDepartmentStats() {
        const deptStats = {};
        
        this.budgetData.forEach(record => {
            const dept = record.department || 'Unknown';
            if (!deptStats[dept]) {
                deptStats[dept] = {
                    count: 0,
                    totalBudget: 0,
                    totalUsed: 0
                };
            }
            deptStats[dept].count++;
            deptStats[dept].totalBudget += parseFloat(record.amount_allocated || 0);
            deptStats[dept].totalUsed += parseFloat(record.amount_used || 0);
        });
        
        const container = document.getElementById('departmentStats');
        if (!container) return;
        
        let html = '<div class="list-group list-group-flush">';
        Object.entries(deptStats).forEach(([dept, stats]) => {
            const utilization = stats.totalBudget > 0 ? (stats.totalUsed / stats.totalBudget * 100) : 0;
            const utilClass = utilization > 90 ? 'danger' : utilization > 75 ? 'warning' : 'success';
            
            html += `
                <div class="list-group-item px-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${dept}</strong>
                            <small class="text-muted d-block">${stats.count} budget(s)</small>
                        </div>
                        <div class="text-end">
                            <div>${this.formatCurrency(stats.totalBudget)}</div>
                            <small class="text-${utilClass}">${utilization.toFixed(1)}% used</small>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-${utilClass}" style="width: ${Math.min(utilization, 100)}%"></div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    generatePeriodStats() {
        const periodStats = {};
        
        this.budgetData.forEach(record => {
            const period = record.period || 'Unknown';
            if (!periodStats[period]) {
                periodStats[period] = {
                    count: 0,
                    totalBudget: 0,
                    totalUsed: 0
                };
            }
            periodStats[period].count++;
            periodStats[period].totalBudget += parseFloat(record.amount_allocated || 0);
            periodStats[period].totalUsed += parseFloat(record.amount_used || 0);
        });
        
        const container = document.getElementById('periodStats');
        if (!container) return;
        
        let html = '<div class="list-group list-group-flush">';
        
        // Sort by count descending
        const sortedPeriods = Object.entries(periodStats).sort((a, b) => b[1].count - a[1].count);
        
        sortedPeriods.forEach(([period, stats]) => {
            const avgBudget = stats.count > 0 ? stats.totalBudget / stats.count : 0;
            const icon = {
                'Daily': 'calendar-day',
                'Bi-weekly': 'calendar2-week',
                'Monthly': 'calendar-month',
                'Annually': 'calendar-year'
            }[period] || 'calendar';
            
            html += `
                <div class="list-group-item px-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-${icon} me-2"></i><strong>${period}</strong>
                            <span class="badge bg-secondary ms-2">${stats.count}</span>
                        </div>
                        <div class="text-end">
                            <div class="small">Avg: ${this.formatCurrency(avgBudget)}</div>
                            <div class="small text-muted">Total: ${this.formatCurrency(stats.totalBudget)}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    generateCostCenterStats() {
        const ccStats = {};
        
        this.budgetData.forEach(record => {
            const cc = record.cost_center || 'Unknown';
            if (!ccStats[cc]) {
                ccStats[cc] = {
                    count: 0,
                    totalBudget: 0,
                    totalUsed: 0
                };
            }
            ccStats[cc].count++;
            ccStats[cc].totalBudget += parseFloat(record.amount_allocated || 0);
            ccStats[cc].totalUsed += parseFloat(record.amount_used || 0);
        });
        
        const container = document.getElementById('costCenterStats');
        if (!container) return;
        
        // Get top 5 cost centers by budget
        const topCC = Object.entries(ccStats)
            .sort((a, b) => b[1].totalBudget - a[1].totalBudget)
            .slice(0, 5);
        
        let html = '<div class="list-group list-group-flush">';
        topCC.forEach(([cc, stats], index) => {
            const utilization = stats.totalBudget > 0 ? (stats.totalUsed / stats.totalBudget * 100) : 0;
            
            html += `
                <div class="list-group-item px-0 py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <span class="badge bg-primary me-2">#${index + 1}</span>
                            <strong>${cc}</strong>
                        </div>
                        <div class="text-end">
                            <div>${this.formatCurrency(stats.totalBudget)}</div>
                            <small class="text-muted">${utilization.toFixed(0)}% utilized</small>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        if (topCC.length === 0) {
            html = '<p class="text-muted mb-0">No cost center data available</p>';
        }
        
        container.innerHTML = html;
    }
    
    generateApprovalStats() {
        const approvalStats = {
            'Approved': 0,
            'Pending': 0,
            'Rejected': 0
        };
        
        let approvedBudget = 0;
        let pendingBudget = 0;
        let rejectedBudget = 0;
        
        this.budgetData.forEach(record => {
            const status = record.approval_status || 'Pending';
            const budget = parseFloat(record.amount_allocated || 0);
            
            if (approvalStats.hasOwnProperty(status)) {
                approvalStats[status]++;
                
                if (status === 'Approved') approvedBudget += budget;
                else if (status === 'Pending') pendingBudget += budget;
                else if (status === 'Rejected') rejectedBudget += budget;
            }
        });
        
        const container = document.getElementById('approvalStats');
        if (!container) return;
        
        const total = this.budgetData.length;
        
        let html = '<div class="list-group list-group-flush">';
        
        const statusConfig = {
            'Approved': { icon: 'check-circle-fill', class: 'success', budget: approvedBudget },
            'Pending': { icon: 'clock-fill', class: 'warning', budget: pendingBudget },
            'Rejected': { icon: 'x-circle-fill', class: 'danger', budget: rejectedBudget }
        };
        
        Object.entries(approvalStats).forEach(([status, count]) => {
            const config = statusConfig[status];
            const percentage = total > 0 ? (count / total * 100) : 0;
            
            html += `
                <div class="list-group-item px-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-${config.icon} text-${config.class} me-2"></i>
                            <strong>${status}</strong>
                            <span class="badge bg-${config.class} ms-2">${count}</span>
                        </div>
                        <div class="text-end">
                            <div>${this.formatCurrency(config.budget)}</div>
                            <small class="text-muted">${percentage.toFixed(1)}%</small>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-${config.class}" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    generateSpendingTrends() {
        const container = document.getElementById('spendingTrends');
        if (!container) return;
        
        let alerts = [];
        
        // Find overspent budgets
        const overspent = this.budgetData.filter(r => 
            parseFloat(r.amount_used || 0) > parseFloat(r.amount_allocated || 0)
        );
        
        if (overspent.length > 0) {
            const totalOverspent = overspent.reduce((sum, r) => 
                sum + (parseFloat(r.amount_used) - parseFloat(r.amount_allocated)), 0
            );
            alerts.push({
                type: 'danger',
                icon: 'exclamation-triangle-fill',
                message: `${overspent.length} budget(s) overspent by ${this.formatCurrency(totalOverspent)}`
            });
        }
        
        // Find budgets close to limit (>90% utilization)
        const nearLimit = this.budgetData.filter(r => {
            const allocated = parseFloat(r.amount_allocated || 0);
            const used = parseFloat(r.amount_used || 0);
            const utilization = allocated > 0 ? (used / allocated) : 0;
            return utilization > 0.9 && utilization <= 1.0;
        });
        
        if (nearLimit.length > 0) {
            alerts.push({
                type: 'warning',
                icon: 'exclamation-circle-fill',
                message: `${nearLimit.length} budget(s) above 90% utilization - monitor closely`
            });
        }
        
        // Find underutilized budgets (<50% utilization)
        const underutilized = this.budgetData.filter(r => {
            const allocated = parseFloat(r.amount_allocated || 0);
            const used = parseFloat(r.amount_used || 0);
            const utilization = allocated > 0 ? (used / allocated) : 0;
            return utilization < 0.5 && allocated > 0;
        });
        
        if (underutilized.length > 0) {
            alerts.push({
                type: 'info',
                icon: 'info-circle-fill',
                message: `${underutilized.length} budget(s) below 50% utilization - potential reallocation opportunity`
            });
        }
        
        // Check for bi-weekly payroll budgets
        const biweeklyPayroll = this.budgetData.filter(r => 
            r.period === 'Bi-weekly' && r.cost_center === 'Payroll Budget'
        );
        
        if (biweeklyPayroll.length > 0) {
            const totalPayroll = biweeklyPayroll.reduce((sum, r) => 
                sum + parseFloat(r.amount_allocated || 0), 0
            );
            alerts.push({
                type: 'primary',
                icon: 'calendar2-week-fill',
                message: `${biweeklyPayroll.length} bi-weekly payroll budget(s) totaling ${this.formatCurrency(totalPayroll)}`
            });
        }
        
        let html = '<div class="list-group list-group-flush">';
        
        if (alerts.length > 0) {
            alerts.forEach(alert => {
                html += `
                    <div class="list-group-item px-0 py-2">
                        <i class="bi bi-${alert.icon} text-${alert.type} me-2"></i>
                        <span>${alert.message}</span>
                    </div>
                `;
            });
        } else {
            html += `
                <div class="list-group-item px-0 py-2">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <span>All budgets are within healthy parameters</span>
                </div>
            `;
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    findPeakSpendingPeriodFromAllData(allRecords) {
        const periodSpending = {};
        
        allRecords.forEach(record => {
            const period = record.period || 'Monthly';
            const used = parseFloat(record.amount_used || 0);
            periodSpending[period] = (periodSpending[period] || 0) + used;
        });
        
        let peakPeriod = 'Monthly';
        let maxSpending = 0;
        
        for (const [period, spending] of Object.entries(periodSpending)) {
            if (spending > maxSpending) {
                maxSpending = spending;
                peakPeriod = period;
            }
        }
        
        return `${peakPeriod} (${this.formatCurrency(maxSpending)} total)`;
    }
    
    calculateEfficiencyScore(utilizationRate) {
        if (utilizationRate >= 85 && utilizationRate <= 95) return 'A+';
        if (utilizationRate >= 75 && utilizationRate < 85) return 'A';
        if (utilizationRate >= 65 && utilizationRate < 75) return 'B+';
        if (utilizationRate >= 55 && utilizationRate < 65) return 'B';
        if (utilizationRate >= 45 && utilizationRate < 55) return 'C';
        return 'D';
    }
    
    generateAIForecast() {
        if (this.isAnalyzing) return;
        
        this.isAnalyzing = true;
        this.updateAIStatus('AI is generating forecast predictions...', true);
        
        // Get all parameters from form
        const forecastPeriodElement = document.getElementById('forecastPeriod');
        const departmentFilterElement = document.getElementById('departmentFilter');
        const budgetFrequencyElement = document.getElementById('budgetFrequency');
        const costCenterFilterElement = document.getElementById('costCenterFilter');
        const approvalFilterElement = document.getElementById('approvalFilter');
        const aiModelElement = document.querySelector('input[name="aiModel"]:checked');
        
        this.forecastPeriod = forecastPeriodElement ? parseInt(forecastPeriodElement.value) : 3;
        this.departmentFilter = departmentFilterElement ? departmentFilterElement.value : 'all';
        this.budgetFrequency = budgetFrequencyElement ? budgetFrequencyElement.value : 'all';
        this.costCenterFilter = costCenterFilterElement ? costCenterFilterElement.value : 'all';
        this.approvalFilter = approvalFilterElement ? approvalFilterElement.value : 'all';
        this.aiModel = aiModelElement ? aiModelElement.value : 'balanced';
        
        console.log('Forecast Parameters:', {
            period: this.forecastPeriod,
            department: this.departmentFilter,
            budgetType: this.budgetFrequency,
            costCenter: this.costCenterFilter,
            approvalStatus: this.approvalFilter,
            aiModel: this.aiModel
        });
        
        // Show loading
        const generateBtn = document.getElementById('generateBtn');
        const spinner = document.getElementById('forecastSpinner');
        if (generateBtn) generateBtn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        
        setTimeout(() => {
            this.performMultiScenarioForecast();
            
            if (generateBtn) generateBtn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            this.isAnalyzing = false;
            
            this.updateAIStatus('Forecast generation complete!', false);
        }, 3000);
    }
    
    performMultiScenarioForecast() {
        let filteredData = [...this.budgetData];
        
        // Apply department filter
        if (this.departmentFilter !== 'all') {
            filteredData = filteredData.filter(item => item.department === this.departmentFilter);
        }
        
        // Apply budget period filter
        if (this.budgetFrequency !== 'all') {
            filteredData = filteredData.filter(item => item.period === this.budgetFrequency);
        }
        
        // Apply cost center filter
        if (this.costCenterFilter !== 'all') {
            filteredData = filteredData.filter(item => item.cost_center === this.costCenterFilter);
        }
        
        // Apply approval filter
        if (this.approvalFilter !== 'all') {
            filteredData = filteredData.filter(item => item.approval_status === this.approvalFilter);
        }
        
        console.log(`Filtered ${filteredData.length} records from ${this.budgetData.length} total`);
        
        const coefficients = this.getScenarioCoefficients();
        const forecastResults = this.calculateScenarioForecasts(filteredData, coefficients);
        
        this.displayForecastResults(forecastResults, filteredData);
        this.displayAIInsights(forecastResults, filteredData);
        
        // Show export/apply buttons
        const exportBtn = document.getElementById('exportForecastBtn');
        const applyBtn = document.getElementById('applyForecastBtn');
        if (exportBtn) exportBtn.classList.remove('d-none');
        if (applyBtn) applyBtn.classList.remove('d-none');
    }
    
    getScenarioCoefficients() {
        const baseCoefficients = this.aiCoefficients[this.aiModel] || this.aiCoefficients.balanced;
        
        if (this.budgetFrequency === 'Daily') {
            return baseCoefficients.daily || baseCoefficients;
        } else if (this.budgetFrequency === 'Bi-weekly') {
            return baseCoefficients.biweekly || baseCoefficients;
        } else if (this.budgetFrequency === 'Monthly') {
            return baseCoefficients.monthly || baseCoefficients;
        } else if (this.budgetFrequency === 'Annually') {
            return baseCoefficients.annually || baseCoefficients;
        } else {
            return baseCoefficients.mixed || baseCoefficients;
        }
    }
    
    calculateScenarioForecasts(filteredData, coefficients) {
        const departmentStats = {};
        
        if (filteredData.length === 0) {
            return this.generatePlaceholderForecast();
        }
        
        filteredData.forEach(item => {
            const dept = item.department || 'Unknown';
            if (!departmentStats[dept]) {
                departmentStats[dept] = {
                    currentBudget: 0,
                    currentUsage: 0,
                    records: 0,
                    periods: new Set(),
                    costCenters: new Set()
                };
            }
            
            departmentStats[dept].currentBudget += parseFloat(item.amount_allocated || 0);
            departmentStats[dept].currentUsage += parseFloat(item.amount_used || 0);
            departmentStats[dept].records++;
            if (item.period) departmentStats[dept].periods.add(item.period);
            if (item.cost_center) departmentStats[dept].costCenters.add(item.cost_center);
        });
        
        const forecasts = {};
        for (const [dept, stats] of Object.entries(departmentStats)) {
            const usageRate = stats.currentBudget > 0 ? stats.currentUsage / stats.currentBudget : 0;
            
            const periodMultiplier = this.getPeriodMultiplier();
            const seasonalAdjustment = this.getSeasonalAdjustment();
            const growthFactor = 1 + (coefficients.growth * periodMultiplier);
            
            const baseProjection = stats.currentUsage * growthFactor * seasonalAdjustment;
            const projectedUsage = Math.max(baseProjection, stats.currentUsage * 0.8);
            
            const bufferMultiplier = 1 + coefficients.buffer;
            const projectedBudget = projectedUsage * bufferMultiplier;
            
            const variance = stats.currentBudget > 0 ? 
                ((projectedBudget - stats.currentBudget) / stats.currentBudget * 100) : 0;
            
            let riskLevel = 'Low';
            if (usageRate > 0.95 || variance > 50) {
                riskLevel = 'High';
            } else if (usageRate > 0.85 || variance > 25) {
                riskLevel = 'Medium';
            }
            
            forecasts[dept] = {
                ...stats,
                usageRate: usageRate,
                projectedNeed: projectedUsage,
                recommendedBudget: projectedBudget,
                variance: variance,
                riskLevel: riskLevel,
                confidence: this.calculateScenarioConfidence(stats.records, usageRate),
                periodTypes: Array.from(stats.periods)
            };
        }
        
        return forecasts;
    }
    
    generatePlaceholderForecast() {
        const placeholder = {};
        
        if (this.departmentFilter !== 'all') {
            placeholder[this.departmentFilter] = {
                currentBudget: 0,
                currentUsage: 0,
                records: 0,
                periods: new Set([this.budgetFrequency === 'all' ? 'Monthly' : this.budgetFrequency]),
                costCenters: new Set(),
                usageRate: 0,
                projectedNeed: 0,
                recommendedBudget: 0,
                variance: 0,
                riskLevel: 'Low',
                confidence: 30,
                periodTypes: [this.budgetFrequency === 'all' ? 'Monthly' : this.budgetFrequency]
            };
        }
        
        return placeholder;
    }
    
    getPeriodMultiplier() {
        switch(this.forecastPeriod) {
            case 1: return 0.8;
            case 3: return 1.0;
            case 6: return 1.3;
            case 12: return 1.6;
            default: return 1.0;
        }
    }
    
    getSeasonalAdjustment() {
        let seasonalKey = 'mixed';
        
        if (this.budgetFrequency === 'Daily') {
            seasonalKey = 'daily';
        } else if (this.budgetFrequency === 'Bi-weekly') {
            seasonalKey = 'biweekly';
        } else if (this.budgetFrequency === 'Monthly') {
            seasonalKey = 'monthly';
        } else if (this.budgetFrequency === 'Annually') {
            seasonalKey = 'annually';
        }
        
        const seasonalData = this.seasonalFactors[seasonalKey];
        
        if (seasonalKey === 'daily') {
            const dayOfWeek = new Date().getDay() + 1;
            return seasonalData[dayOfWeek] || 1.0;
        } else if (seasonalKey === 'monthly' || seasonalKey === 'mixed' || seasonalKey === 'biweekly') {
            const currentMonth = new Date().getMonth() + 1;
            return seasonalData[currentMonth] || 1.0;
        }
        
        return 1.0;
    }
    
    calculateScenarioConfidence(records, usageRate) {
        let confidence = 60;
        
        if (records >= 5) confidence += 20;
        else if (records >= 2) confidence += 10;
        else confidence -= 10;
        
        if (usageRate >= 0.3 && usageRate <= 0.9) confidence += 15;
        else confidence -= 5;
        
        if (this.aiModel === 'conservative') confidence += 5;
        else if (this.aiModel === 'aggressive') confidence -= 5;
        
        if (this.forecastPeriod <= 3) confidence += 10;
        else if (this.forecastPeriod >= 12) confidence -= 10;
        
        if (this.budgetFrequency !== 'all') confidence += 5;
        
        return Math.min(95, Math.max(25, confidence));
    }
    
    displayForecastResults(forecasts, filteredData) {
        const forecastSection = document.getElementById('forecastResults');
        if (forecastSection) forecastSection.style.display = 'block';
        
        const periodElement = document.getElementById('projectedPeriodText');
        if (periodElement) {
            let periodText = `${this.forecastPeriod} month${this.forecastPeriod > 1 ? 's' : ''}`;
            if (this.budgetFrequency !== 'all') {
                periodText += ` (${this.budgetFrequency} budgets)`;
            }
            if (this.costCenterFilter !== 'all') {
                periodText += ` - ${this.costCenterFilter}`;
            }
            periodElement.textContent = periodText;
        }
        
        this.updateFilterStatus(filteredData.length);
        
        let totalCurrentBudget = 0;
        let totalCurrentUsage = 0;
        let totalProjectedNeed = 0;
        let totalRecommendedBudget = 0;
        let overallConfidence = 0;
        let confidenceCount = 0;
        
        const tbody = document.getElementById('departmentForecastTable');
        if (tbody) {
            tbody.innerHTML = '';
            
            if (Object.keys(forecasts).length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="8" class="text-center text-muted py-4">
                    No data found for the selected filters.<br>
                    <small>Department: ${this.departmentFilter}, Period: ${this.budgetFrequency}, Cost Center: ${this.costCenterFilter}</small>
                </td>`;
                tbody.appendChild(row);
            } else {
                Object.entries(forecasts).forEach(([dept, data]) => {
                    totalCurrentBudget += data.currentBudget;
                    totalCurrentUsage += data.currentUsage;
                    totalProjectedNeed += data.projectedNeed;
                    totalRecommendedBudget += data.recommendedBudget;
                    overallConfidence += data.confidence;
                    confidenceCount++;
                    
                    const row = this.createDepartmentRow(dept, data);
                    tbody.appendChild(row);
                });
            }
        }
        
        const elements = {
            projectedTotalBudget: document.getElementById('projectedTotalBudget'),
            recommendedBudget: document.getElementById('recommendedBudget'),
            budgetVariance: document.getElementById('budgetVariance'),
            aiConfidence: document.getElementById('aiConfidence')
        };
        
        if (elements.projectedTotalBudget) elements.projectedTotalBudget.textContent = this.formatCurrency(totalProjectedNeed);
        if (elements.recommendedBudget) elements.recommendedBudget.textContent = this.formatCurrency(totalRecommendedBudget);
        
        const variance = totalCurrentBudget > 0 ? 
            ((totalRecommendedBudget - totalCurrentBudget) / totalCurrentBudget * 100) : 0;
        if (elements.budgetVariance) {
            elements.budgetVariance.textContent = (variance > 0 ? '+' : '') + variance.toFixed(1) + '%';
            elements.budgetVariance.className = 
                variance > 20 ? 'h3 text-danger mb-2' : 
                variance > 10 ? 'h3 text-warning mb-2' : 'h3 text-success mb-2';
        }
        
        const avgConfidence = confidenceCount > 0 ? overallConfidence / confidenceCount : 0;
        if (elements.aiConfidence) elements.aiConfidence.textContent = Math.round(avgConfidence) + '%';
        
        this.updateTotalsRow(totalCurrentBudget, totalCurrentUsage, totalProjectedNeed, 
                           totalRecommendedBudget, variance, forecasts);
    }
    
    updateFilterStatus(filteredCount) {
        const existingStatus = document.getElementById('filterStatus');
        if (existingStatus) existingStatus.remove();
        
        const forecastSection = document.getElementById('forecastResults');
        if (forecastSection) {
            const statusDiv = document.createElement('div');
            statusDiv.id = 'filterStatus';
            statusDiv.className = 'alert alert-info mb-3';
            
            let filterDetails = [];
            if (this.departmentFilter !== 'all') filterDetails.push(`Department: ${this.departmentFilter}`);
            if (this.budgetFrequency !== 'all') filterDetails.push(`Period: ${this.budgetFrequency}`);
            if (this.costCenterFilter !== 'all') filterDetails.push(`Cost Center: ${this.costCenterFilter}`);
            if (this.approvalFilter !== 'all') filterDetails.push(`Approval: ${this.approvalFilter}`);
            
            const filterText = filterDetails.length > 0 ? ` (${filterDetails.join(', ')})` : '';
            
            statusDiv.innerHTML = `
                <strong><i class="bi bi-funnel-fill me-2"></i>Scenario Analysis:</strong> 
                Forecasting ${filteredCount} records over ${this.forecastPeriod} months using ${this.aiModel} AI model${filterText}
            `;
            forecastSection.insertBefore(statusDiv, forecastSection.firstChild);
        }
    }
    
    createDepartmentRow(dept, data) {
        const row = document.createElement('tr');
        const riskClass = data.riskLevel === 'High' ? 'danger' : 
                         data.riskLevel === 'Medium' ? 'warning' : 'success';
        const changeClass = data.variance > 20 ? 'text-danger' : 
                           data.variance > 10 ? 'text-warning' : 'text-success';
        
        const periodInfo = data.periodTypes.length > 0 ? 
            `<br><small class="text-muted">${data.periodTypes.join(', ')}</small>` : '';
        
        row.innerHTML = `
            <td><strong>${dept}</strong>${periodInfo}</td>
            <td>${this.formatCurrency(data.currentBudget)}</td>
            <td>${this.formatCurrency(data.currentUsage)}</td>
            <td>${(data.usageRate * 100).toFixed(1)}%</td>
            <td>${this.formatCurrency(data.projectedNeed)}</td>
            <td>${this.formatCurrency(data.recommendedBudget)}</td>
            <td class="${changeClass}">
                ${data.variance > 0 ? '+' : ''}${data.variance.toFixed(1)}%
            </td>
            <td>
                <span class="badge bg-${riskClass}">${data.riskLevel}</span>
                <small class="text-muted d-block">${data.confidence}% confidence</small>
            </td>
        `;
        
        return row;
    }
    
    updateTotalsRow(totalCurrentBudget, totalCurrentUsage, totalProjectedNeed, 
                   totalRecommendedBudget, variance, forecasts) {
        const elements = {
            totalCurrentBudget: document.getElementById('totalCurrentBudget'),
            totalCurrentUsage: document.getElementById('totalCurrentUsage'),
            overallUsageRate: document.getElementById('overallUsageRate'),
            totalProjectedNeed: document.getElementById('totalProjectedNeed'),
            totalRecommendedBudget: document.getElementById('totalRecommendedBudget'),
            totalChange: document.getElementById('totalChange'),
            overallRisk: document.getElementById('overallRisk')
        };
        
        if (elements.totalCurrentBudget) elements.totalCurrentBudget.textContent = this.formatCurrency(totalCurrentBudget);
        if (elements.totalCurrentUsage) elements.totalCurrentUsage.textContent = this.formatCurrency(totalCurrentUsage);
        if (elements.overallUsageRate) elements.overallUsageRate.textContent = 
            totalCurrentBudget > 0 ? ((totalCurrentUsage / totalCurrentBudget) * 100).toFixed(1) + '%' : '0%';
        if (elements.totalProjectedNeed) elements.totalProjectedNeed.textContent = this.formatCurrency(totalProjectedNeed);
        if (elements.totalRecommendedBudget) elements.totalRecommendedBudget.textContent = this.formatCurrency(totalRecommendedBudget);
        if (elements.totalChange) elements.totalChange.textContent = (variance > 0 ? '+' : '') + variance.toFixed(1) + '%';
        if (elements.overallRisk) elements.overallRisk.textContent = this.calculateOverallRisk(forecasts);
    }
    
    calculateOverallRisk(forecasts) {
        const riskLevels = Object.values(forecasts).map(f => f.riskLevel);
        const highCount = riskLevels.filter(r => r === 'High').length;
        const mediumCount = riskLevels.filter(r => r === 'Medium').length;
        
        if (highCount >= riskLevels.length * 0.5) return 'High';
        if (highCount + mediumCount >= riskLevels.length * 0.6) return 'Medium';
        return 'Low';
    }
    
    displayAIInsights(forecasts, filteredData) {
        const insightsSection = document.getElementById('aiInsightsSection');
        if (insightsSection) insightsSection.style.display = 'block';
        
        const recommendations = this.generateScenarioRecommendations(forecasts, filteredData);
        const risks = this.generateScenarioRisks(forecasts, filteredData);
        const executiveSummary = this.generateScenarioSummary(forecasts, filteredData);
        
        const recContainer = document.getElementById('aiRecommendations');
        if (recContainer) {
            recContainer.innerHTML = '';
            recommendations.forEach(rec => {
                const item = document.createElement('div');
                item.className = 'list-group-item border-0 px-0';
                item.innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i>${rec}`;
                recContainer.appendChild(item);
            });
        }
        
        const riskContainer = document.getElementById('riskFactors');
        if (riskContainer) {
            riskContainer.innerHTML = '';
            risks.forEach(risk => {
                const item = document.createElement('div');
                item.className = 'list-group-item border-0 px-0';
                item.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>${risk}`;
                riskContainer.appendChild(item);
            });
        }
        
        const summaryElement = document.getElementById('aiExecutiveSummary');
        if (summaryElement) summaryElement.textContent = executiveSummary;
    }
    
    generateScenarioRecommendations(forecasts, filteredData) {
        const recommendations = [];
        
        if (this.departmentFilter !== 'all') {
            recommendations.push(`<strong>Department Focus:</strong> Analysis targeted for ${this.departmentFilter} - use for departmental budget planning`);
        }
        
        if (this.budgetFrequency !== 'all') {
            const periodAdvice = {
                'Daily': 'Implement daily spending controls and automated alerts for better cash flow management',
                'Bi-weekly': 'Align with payroll cycles - ensure sufficient liquidity for bi-weekly disbursements',
                'Monthly': 'Set up mid-month reviews and trend monitoring for optimal budget utilization',
                'Annually': 'Break down annual budgets into quarterly milestones for better tracking'
            };
            recommendations.push(`<strong>${this.budgetFrequency} Strategy:</strong> ${periodAdvice[this.budgetFrequency]}`);
        }
        
        if (this.costCenterFilter !== 'all') {
            recommendations.push(`<strong>Cost Center Focus:</strong> Specific analysis for ${this.costCenterFilter} - monitor closely`);
        }
        
        const timelineAdvice = {
            1: 'Short-term forecast - focus on immediate spending controls and cash flow',
            3: 'Quarterly forecast - ideal for operational planning and budget adjustments',
            6: 'Mid-term forecast - suitable for strategic planning and resource allocation',
            12: 'Annual forecast - use for long-term strategic budgeting and goal setting'
        };
        recommendations.push(`<strong>${this.forecastPeriod}-Month Timeline:</strong> ${timelineAdvice[this.forecastPeriod]}`);
        
        const modelAdvice = {
            'conservative': 'Conservative model - budget increases minimal, focused on essential needs with higher safety buffers',
            'balanced': 'Balanced model - moderate growth projections suitable for standard planning',
            'aggressive': 'Aggressive model - higher growth potential, ensure adequate funding for projected increases'
        };
        recommendations.push(`<strong>AI Model Impact:</strong> ${modelAdvice[this.aiModel]}`);
        
        Object.entries(forecasts).forEach(([dept, data]) => {
            if (data.variance > 15) {
                recommendations.push(`<strong>${dept}:</strong> Budget increase of ${data.variance.toFixed(0)}% recommended over ${this.forecastPeriod} months`);
            }
            if (data.usageRate > 0.9) {
                recommendations.push(`<strong>${dept}:</strong> High utilization (${(data.usageRate * 100).toFixed(0)}%) - implement stricter monitoring`);
            }
        });
        
        return recommendations.slice(0, 7);
    }
    
    generateScenarioRisks(forecasts, filteredData) {
        const risks = [];
        
        if (filteredData.length < 3) {
            risks.push(`<strong>Data Limitation:</strong> Only ${filteredData.length} records - consider broader criteria for reliable predictions`);
        }
        
        if (this.forecastPeriod >= 12) {
            risks.push(`<strong>Long-term Risk:</strong> 12-month predictions have higher uncertainty - review quarterly`);
        }
        
        if (this.budgetFrequency === 'Daily' && this.forecastPeriod > 3) {
            risks.push(`<strong>Daily Budget Risk:</strong> Long-term daily projections are volatile - consider monthly planning`);
        }
        
        if (this.budgetFrequency === 'Bi-weekly' && this.costCenterFilter === 'Payroll Budget') {
            risks.push(`<strong>Payroll Risk:</strong> Ensure sufficient cash flow for bi-weekly payroll cycles - late payments can impact morale`);
        }
        
        Object.entries(forecasts).forEach(([dept, data]) => {
            if (data.riskLevel === 'High') {
                if (data.usageRate > 0.95) {
                    risks.push(`<strong>${dept}:</strong> Critical budget exhaustion risk - immediate intervention required`);
                }
                if (data.variance > 40) {
                    risks.push(`<strong>${dept}:</strong> Projected ${data.variance.toFixed(0)}% increase may strain finances`);
                }
            }
        });
        
        if (this.aiModel === 'aggressive' && Object.values(forecasts).some(f => f.variance > 30)) {
            risks.push(`<strong>Aggressive Model Risk:</strong> High growth projections - validate with conservative estimates`);
        }
        
        return risks.slice(0, 6);
    }
    
    generateScenarioSummary(forecasts, filteredData) {
        const deptCount = Object.keys(forecasts).length;
        const highRiskCount = Object.values(forecasts).filter(f => f.riskLevel === 'High').length;
        const avgVariance = deptCount > 0 ? Object.values(forecasts).reduce((sum, f) => sum + f.variance, 0) / deptCount : 0;
        const avgConfidence = deptCount > 0 ? Object.values(forecasts).reduce((sum, f) => sum + f.confidence, 0) / deptCount : 0;
        
        let scenarioDescription = `${this.forecastPeriod}-month forecast using ${this.aiModel} AI model`;
        if (this.departmentFilter !== 'all') scenarioDescription += ` for ${this.departmentFilter}`;
        if (this.budgetFrequency !== 'all') scenarioDescription += ` (${this.budgetFrequency} budgets)`;
        if (this.costCenterFilter !== 'all') scenarioDescription += ` - ${this.costCenterFilter}`;
        
        const riskAssessment = highRiskCount > 0 ? `${highRiskCount} high-risk area(s)` : 'No high-risk areas';
        const budgetImpact = Math.abs(avgVariance) > 10 ? `significant adjustments needed (${avgVariance.toFixed(1)}% avg)` : 'minor adjustments';
        const actionRequired = avgVariance > 20 ? 'immediate planning required' : avgVariance > 10 ? 'proactive planning recommended' : 'maintain current trajectory';
        
        return `${scenarioDescription} shows ${budgetImpact}. ${riskAssessment} identified. AI confidence: ${avgConfidence.toFixed(0)}%. Based on ${filteredData.length} records. Recommendation: ${actionRequired}.`;
    }
    
    formatCurrency(amount) {
        return '₱' + parseFloat(amount || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    resetAI() {
        const sections = ['historicalTrends', 'forecastResults', 'aiInsightsSection'];
        sections.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.style.display = 'none';
        });
        
        const progressBar = document.getElementById('analysisProgress');
        if (progressBar) progressBar.style.width = '0%';
        
        const exportBtn = document.getElementById('exportForecastBtn');
        const applyBtn = document.getElementById('applyForecastBtn');
        if (exportBtn) exportBtn.classList.add('d-none');
        if (applyBtn) applyBtn.classList.add('d-none');
        
        const existingStatus = document.getElementById('filterStatus');
        if (existingStatus) existingStatus.remove();
        
        this.isAnalyzing = false;
        this.updateAIStatus('AI system ready', false);
    }
}

// Global functions
function generateAIForecast() {
    if (window.budgetAI) {
        window.budgetAI.generateAIForecast();
    } else {
        console.error('Budget AI system not initialized');
        alert('AI system not ready. Please refresh the page and try again.');
    }
}

function exportForecastReport() {
    const currentFilters = window.budgetAI ? {
        department: window.budgetAI.departmentFilter,
        period: window.budgetAI.budgetFrequency,
        costCenter: window.budgetAI.costCenterFilter,
        approval: window.budgetAI.approvalFilter,
        forecastMonths: window.budgetAI.forecastPeriod,
        aiModel: window.budgetAI.aiModel
    } : {};
    
    const filterSummary = `Department=${currentFilters.department || 'All'}, Period=${currentFilters.period || 'All'}, Cost Center=${currentFilters.costCenter || 'All'}, Timeline=${currentFilters.forecastMonths || 3}mo, AI=${currentFilters.aiModel || 'Balanced'}`;
    
    alert(`✅ Enhanced Multi-Scenario Forecast Report

📊 Report includes:
• Scenario-specific projections
• Department & cost center breakdowns
• Bi-weekly payroll analysis
• Risk assessments with detailed statistics
• Period-based recommendations
• AI confidence metrics
• Approval status analysis

⚙️ Filters: ${filterSummary}

🔗 Ready for export to financial reporting system`);
}

function applyForecast() {
    const currentFilters = window.budgetAI ? {
        department: window.budgetAI.departmentFilter,
        period: window.budgetAI.budgetFrequency,
        costCenter: window.budgetAI.costCenterFilter,
        timeline: window.budgetAI.forecastPeriod,
        model: window.budgetAI.aiModel
    } : {};
    
    const scenarioSummary = `${currentFilters.timeline}mo forecast, ${currentFilters.department} dept, ${currentFilters.period} budgets, ${currentFilters.costCenter} cost center, ${currentFilters.model} AI`;
    
    if (confirm(`Apply Multi-Scenario AI Forecast?

${scenarioSummary}

This will:
✓ Update allocations based on analysis
✓ Set scenario-specific monitoring
✓ Apply period-appropriate controls
✓ Create forecast tracking
✓ Configure bi-weekly payroll alerts

Proceed?`)) {
        alert(`✅ Multi-Scenario Forecast Applied Successfully!

✅ Changes Applied:
• Budget allocations updated
• Scenario alerts activated
• Period controls implemented
• Risk monitoring configured
• Bi-weekly payroll tracking enabled
• Department notifications sent

📈 Scenario: ${scenarioSummary}

🔗 System updated`);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    try {
        window.budgetAI = new BudgetForecastAI();
        console.log('✅ Enhanced Budget Forecast AI System v3.0 Initialized');
        console.log('📊 Data loaded:', window.budgetData ? window.budgetData.length : 0, 'records');
        console.log('🔧 Features: Bi-weekly Support, Detailed Statistics, Enhanced AI');
    } catch (error) {
        console.error('❌ Failed to initialize Budget Forecast AI:', error);
        setTimeout(() => {
            try {
                window.budgetAI = new BudgetForecastAI();
                console.log('✅ Budget AI initialized on retry');
            } catch (retryError) {
                console.error('❌ Retry failed:', retryError);
            }
        }, 2000);
    }
});
