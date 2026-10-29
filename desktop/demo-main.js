const { app, BrowserWindow } = require('electron');

let mainWindow;

app.whenReady().then(() => {
    mainWindow = new BrowserWindow({
        width: 1200,
        height: 800,
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false
        }
    });

    // Create a simple HTML content
    const htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>KUKU Farm Manager Pro - Desktop Application</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                }
                .container {
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(20px);
                    border-radius: 20px;
                    padding: 3rem;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
                    max-width: 600px;
                    text-align: center;
                }
                .logo {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                    animation: pulse 2s infinite;
                }
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                h1 {
                    font-size: 2.5rem;
                    margin-bottom: 1rem;
                    color: #2c3e50;
                }
                .subtitle {
                    font-size: 1.2rem;
                    margin-bottom: 2rem;
                    color: #ecf0f1;
                }
                .features {
                    text-align: left;
                    margin: 2rem 0;
                }
                .feature {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 1rem;
                    border-radius: 10px;
                    margin-bottom: 1rem;
                    border-left: 4px solid #2ecc71;
                }
                .feature h3 {
                    color: #2ecc71;
                    margin-bottom: 0.5rem;
                }
                .status {
                    background: rgba(46, 204, 113, 0.1);
                    padding: 1rem;
                    border-radius: 10px;
                    margin-top: 2rem;
                }
                .status.success {
                    border-left-color: #27ae60;
                }
                .status h3 {
                    color: #27ae60;
                }
                .btn {
                    background: linear-gradient(135deg, #2ecc71, #27ae60);
                    color: white;
                    border: none;
                    padding: 1rem 2rem;
                    border-radius: 8px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    cursor: pointer;
                    margin: 0.5rem;
                    transition: all 0.3s ease;
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 25px rgba(46, 204, 113, 0.4);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">🐔</div>
                <h1>KUKU Farm Manager Pro</h1>
                <p class="subtitle">Professional Desktop Application</p>
                
                <div class="features">
                    <div class="feature">
                        <h3>🏢 Desktop Application</h3>
                        <p>Standalone desktop app with modern UI</p>
                    </div>
                    <div class="feature">
                        <h3>📊 Advanced Analytics</h3>
                        <p>Real-time farm insights and reporting</p>
                    </div>
                    <div class="feature">
                        <h3>🔒 Enterprise Security</h3>
                        <p>Multi-user support with role-based access</p>
                    </div>
                    <div class="feature">
                        <h3>🌐 Cloud Integration</h3>
                        <p>Automatic backup and synchronization</p>
                    </div>
                    <div class="feature">
                        <h3>📱 Mobile Companion</h3>
                        <p>Field access with offline support</p>
                    </div>
                    <div class="feature">
                        <h3>🤖 Smart Features</h3>
                        <p>IoT integration and AI-powered insights</p>
                    </div>
                </div>
                
                <div class="status success">
                    <h3>✅ Application Status</h3>
                    <p>Desktop application is running successfully!</p>
                    <p><strong>Version:</strong> 2.0.0</p>
                    <p><strong>Platform:</strong> ${process.platform}</p>
                    <p><strong>Node.js:</strong> ${process.versions.node}</p>
                    <p><strong>Electron:</strong> ${process.versions.electron}</p>
                </div>
                
                <div>
                    <button class="btn" onclick="window.close()">
                        Close Application
                    </button>
                    <button class="btn" onclick="window.location.reload()">
                        Refresh Page
                    </button>
                </div>
            </div>
        </body>
        </html>
    `;

    mainWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(htmlContent));
    mainWindow.show();
    mainWindow.center();
});

app.on('window-all-closed', () => {
    app.quit();
});