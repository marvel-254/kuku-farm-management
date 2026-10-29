<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animation Demo - KUKU</title>
    <link rel="stylesheet" href="assets/css/professional.css">
    <script src="assets/js/animations.js" defer></script>
    <style>
        .demo-section {
            margin: 2rem 0;
            padding: 2rem;
            border: 1px solid #e9ecef;
            border-radius: var(--border-radius);
        }

        .demo-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 1rem 0;
        }

        .demo-card {
            animation: fadeInScale 0.6s ease-out both;
        }

        .demo-card:nth-child(1) { animation-delay: 0.1s; }
        .demo-card:nth-child(2) { animation-delay: 0.2s; }
        .demo-card:nth-child(3) { animation-delay: 0.3s; }
        .demo-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="dashboard.php" class="logo">KUKU</a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="animation-demo.php" class="nav-link active">Animation Demo</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1>Animation Showcase</h1>
                <p>Experience the smooth animations and interactions</p>
            </div>

            <div class="demo-section">
                <h3>Interactive Cards</h3>
                <p>Hover over the cards to see the lift and glow effects</p>
                <div class="stats-grid">
                    <div class="stat-card demo-card">
                        <div class="stat-icon">$</div>
                        <div class="stat-content">
                            <h3>$12,345</h3>
                            <p>Revenue</p>
                            <small>This month</small>
                        </div>
                    </div>

                    <div class="stat-card demo-card">
                        <div class="stat-icon">C</div>
                        <div class="stat-content">
                            <h3>1,234</h3>
                            <p>Chickens</p>
                            <small>Active</small>
                        </div>
                    </div>

                    <div class="stat-card demo-card">
                        <div class="stat-icon">P</div>
                    <div class="stat-content">
                            <h3>15.7%</h3>
                            <p>Profit Margin</p>
                            <small>Growth</small>
                        </div>
                    </div>

                    <div class="stat-card demo-card">
                        <div class="stat-icon">T</div>
                        <div class="stat-content">
                            <h3>89</h3>
                            <p>Transactions</p>
                            <small>This week</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="demo-section">
                <h3>Button Interactions</h3>
                <p>Click buttons to see ripple effects and loading states</p>
                <div class="demo-buttons">
                    <button class="btn btn-primary">Primary Button</button>
                    <button class="btn btn-success">Success Button</button>
                    <button class="btn btn-warning">Warning Button</button>
                    <button class="btn btn-danger">Danger Button</button>
                </div>

                <div class="demo-buttons">
                    <button class="btn btn-secondary" onclick="this.classList.add('loading'); setTimeout(() => this.classList.remove('loading'), 3000)">
                        Loading Demo
                    </button>
                    <button class="btn btn-info pulse-animation">Pulsing Button</button>
                    <button class="btn btn-dark glow">Glowing Button</button>
                </div>
            </div>

            <div class="demo-section">
                <h3>Alert Animations</h3>
                <p>Different alert types with slide-in animations</p>
                <div class="alert alert-success">
                    <strong>Success!</strong> Operation completed successfully.
                </div>
                <div class="alert alert-info">
                    <strong>Info:</strong> This is an informational message.
                </div>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> Please review this information.
                </div>
                <div class="alert alert-danger">
                    <strong>Error:</strong> Something went wrong.
                </div>
            </div>

            <div class="demo-section">
                <h3>Form Interactions</h3>
                <p>Focus on inputs to see the lift and glow effects</p>
                <div class="card">
                    <div class="card-body">
                        <form>
                            <div class="dashboard-grid">
                                <div class="form-group">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" placeholder="Enter your name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" placeholder="Enter your email">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Amount</label>
                                    <input type="number" class="form-control" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="demo-section">
                <h3>Table Interactions</h3>
                <p>Hover over table rows to see highlight effects</p>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Chicken Feed</td>
                                <td>50 kg</td>
                                <td>$2.50</td>
                                <td>$125.00</td>
                                <td><span class="badge badge-success">Paid</span></td>
                            </tr>
                            <tr>
                                <td>Vitamins</td>
                                <td>2 bottles</td>
                                <td>$15.00</td>
                                <td>$30.00</td>
                                <td><span class="badge badge-warning">Pending</span></td>
                            </tr>
                            <tr>
                                <td>Equipment</td>
                                <td>1 unit</td>
                                <td>$200.00</td>
                                <td>$200.00</td>
                                <td><span class="badge badge-info">Processing</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 KUKU Farm Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>