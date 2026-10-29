<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Chicken.php';
require_once __DIR__ . '/../app/models/ChickenBatch.php';
require_once __DIR__ . '/../app/models/Sale.php';
require_once __DIR__ . '/../app/models/Expense.php';
require_once __DIR__ . '/../app/models/Invoice.php';
require_once __DIR__ . '/../app/models/Debt.php';

$chickenModel = new Chicken();
$batchModel = new ChickenBatch();
$saleModel = new Sale();
$expenseModel = new Expense();
$invoiceModel = new Invoice();
$debtModel = new Debt();

// Get statistics
$chickenStats = $chickenModel->getStats();
$saleStats = $saleModel->getStats('month');
$expenseStats = $expenseModel->getStats('month');
$invoiceStats = $invoiceModel->getStats('month');
$debtStats = $debtModel->getStats('month');

$recentBatches = $batchModel->getWithDetails();
$recentSales = $saleModel->getWithDetails(5);
$recentExpenses = $expenseModel->getWithDetails(5);

// Calculate comprehensive financial summary
$totalRevenue = $saleStats['total_revenue'] ?? 0;
$totalExpenses = $expenseStats['total_amount'] ?? 0;

// Outstanding receivables (money owed TO the farmer)
$outstandingReceivables = $invoiceModel->getTotalOutstanding();

// Outstanding payables (money the farmer OWES)
$outstandingPayables = $debtModel->getTotalOutstandingByType('we_owe');

// True net position: Revenue - Expenses - Payables + Receivables
$netProfit = $totalRevenue - $totalExpenses;
$netFinancialPosition = $netProfit - $outstandingPayables + $outstandingReceivables;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
    <link rel="stylesheet" href="assets/css/calculator-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/animations.js" defer></script>

    <script>
        // Weather API Integration
        async function loadWeather() {
            try {
                // Default coordinates for Nairobi, Kenya (can be made configurable)
                const lat = -1.2864;
                const lon = 36.8172;

                // Using OpenWeatherMap free API
                const apiKey = 'bd5e378503939ddaee76f12ad7a97608'; // Free tier API key
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${apiKey}&units=metric`);

                if (!response.ok) {
                    throw new Error('Weather API request failed');
                }

                const data = await response.json();

                const weatherHtml = `
                    <div class="text-center">
                        <h4>${data.name}, ${data.sys.country}</h4>
                        <img src="https://openweathermap.org/img/wn/${data.weather[0].icon}@2x.png"
                             alt="${data.weather[0].description}"
                             style="width: 80px; height: 80px;">
                        <h3>${Math.round(data.main.temp)}°C</h3>
                        <p class="text-muted text-capitalize">${data.weather[0].description}</p>
                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <small>Humidity</small>
                                <br><strong>${data.main.humidity}%</strong>
                            </div>
                            <div class="col-4">
                                <small>Wind</small>
                                <br><strong>${data.wind.speed} m/s</strong>
                            </div>
                            <div class="col-4">
                                <small>Pressure</small>
                                <br><strong>${data.main.pressure} hPa</strong>
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('weather-widget').innerHTML = weatherHtml;
            } catch (error) {
                console.error('Weather API error:', error);
                document.getElementById('weather-widget').innerHTML = `
                    <div class="text-center">
                        <p class="text-muted">Weather data unavailable</p>
                        <small>Please check your internet connection</small>
                    </div>
                `;
            }
        }

        // Quick Chart for Dashboard
        function createQuickChart() {
            const ctx = document.getElementById('quickChart').getContext('2d');

            // Sample data - in real implementation, this would come from PHP
            const chartData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Monthly Revenue ($)',
                    data: [1200, 1900, 3000, 5000, 2000, 3000],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }]
            };

            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Load weather and charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadWeather();
            createQuickChart();
        });

        // Refresh weather every 30 minutes
        setInterval(loadWeather, 30 * 60 * 1000);
    </script>
</body>
</html>