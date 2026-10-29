# KUKU Farm Management System

A comprehensive farm management system available as both **Desktop Application** (recommended) and **Web Application**.

## 🚀 Desktop Application (Recommended)

The desktop version provides the best user experience with offline functionality, faster performance, and a native app feel.

### **Desktop Features**
- **Offline Operation**: Works without internet connection
- **Local Database**: SQLite database for fast, local data storage
- **Cross-Platform**: Windows, macOS, and Linux support
- **Modern UI**: Clean, professional interface with animations
- **Data Export/Import**: Backup and restore farm data
- **Weather Integration**: Local weather data for farm planning
- **Interactive Charts**: Real-time data visualization

### **Desktop Installation**

1. **Install Prerequisites**
   - Node.js (v16 or higher) from https://nodejs.org/

2. **Setup Application**
   ```bash
   # Clone or download the project
   cd kuku-farm-management

   # Run setup script
   ./setup.sh
   # OR manually:
   npm install
   php database/create_db.php
   ```

3. **Start Application**
   ```bash
   npm start
   ```

4. **Build for Distribution**
   ```bash
   npm run build          # Current platform
   npm run build-win      # Windows
   npm run build-mac      # macOS
   npm run build-linux    # Linux
   ```

### **Desktop System Requirements**
- **OS**: Windows 10+, macOS 10.13+, Ubuntu 18.04+
- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 100MB free space
- **Display**: 1024x768 resolution

## 🌐 Web Application (Alternative)

For users who prefer web-based access or development purposes.

### **Web Features**
- **Browser-based**: Access from any device with a web browser
- **Real-time Updates**: Live data synchronization
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Session Management**: Secure user authentication

### **Web Installation**

1. **Install Prerequisites**
   - PHP 8.0+ with SQLite3 extension
   - Web server (Apache/Nginx) or PHP built-in server

2. **Setup Application**
   ```bash
   php setup.php
   ```

3. **Start Server**
   ```bash
   ./start.sh
   ```

4. **Access Application**
   - Open browser: http://localhost:4567
   - Default login: `admin` / `admin123`

## 🎯 Core Features (Both Versions)

- **🐔 Chicken Management**: Track batches, health records, and individual chickens
- **📦 Inventory Management**: Monitor feeds, medications, equipment, and supplies
- **💰 Financial Tracking**: Sales, expenses, invoices, debts, and financial reports
- **📊 Advanced Analytics**: Interactive charts and comprehensive reporting
- **🌤️ Weather Integration**: Local weather data for farming decisions
- **🔒 Secure Authentication**: Role-based access control
- **💾 Data Export/Import**: Backup and restore capabilities
- **📱 Responsive Design**: Works on all devices and screen sizes

## 📁 Project Structure

### **Desktop Application**
```
kuku-farm-management/
├── electron-main.js          # Main Electron process
├── public/                   # Frontend application files
│   ├── index.html           # Main app entry point
│   ├── dashboard.html       # Dashboard with sidebar
│   ├── chickens.html        # Chicken management interface
│   ├── inventory.html       # Inventory management
│   ├── sales.html           # Sales tracking
│   ├── expenses.html        # Expense management
│   ├── invoices.html        # Invoice management
│   ├── debts.html           # Debt tracking
│   ├── financial.html       # Financial overview
│   ├── reports.html         # Reports & analytics
│   └── assets/             # Styles, scripts, icons
├── database/               # SQLite database and migrations
│   ├── migrations/         # Database schema files
│   └── create_db.php       # Database setup script
├── assets/                 # Application assets
├── dist/                   # Built application packages
├── package.json            # Node.js dependencies and scripts
└── README.md              # This file
```

### **Web Application**
```
kuku/
├── app/
│   ├── auth/Auth.php
│   ├── config/config.php
│   ├── helpers/
│   │   ├── FarmingCalculator.php
│   │   └── WeatherService.php
│   └── models/
│       ├── Chicken.php
│       ├── ChickenBatch.php
│       ├── ChickenGroup.php
│       ├── Earning.php
│       ├── Expense.php
│       ├── Farmer.php
│       ├── Invoice.php
│       ├── Item.php
│       ├── Debt.php
│       ├── Model.php
│       └── Sale.php
├── database/
│   ├── migrations/
│   └── create_db.php
├── public/
│   ├── assets/
│   ├── *.php               # Web pages
│   └── api.php
├── sessions/
├── start.sh
└── setup.php
```

## 🎮 Usage Guide

### **Desktop Application**

#### **Starting the Application**
```bash
# Development mode
npm start

# Production build
npm run build
npm run build-win    # Windows
npm run build-mac    # macOS
npm run build-linux  # Linux
```

#### **Navigation**
- **Sidebar**: Click section icons on the left to navigate
- **Dashboard**: Central hub with overview and quick actions
- **Back Buttons**: Return to previous sections easily

#### **Data Management**
- **Add Records**: Use "Add" buttons in each section
- **Edit Records**: Click "Edit" on existing records
- **Stock Management**: Update inventory levels
- **Export/Import**: Backup and restore data

### **Web Application**

#### **Starting the Server**
```bash
# Default port 4567
./start.sh

# Custom configuration
PORT=8080 ./start.sh
HOST=0.0.0.0 PORT=3000 ./start.sh
```

#### **Access**
- Open browser: http://localhost:4567
- Default login: `admin` / `admin123`

### **API Endpoints (Web Version)**
- `GET /api.php?endpoint=status` - System status
- `GET /api.php?endpoint=weather` - Weather data
- `POST /api.php?endpoint=login` - User authentication
- `GET /api.php?endpoint=dashboard` - Dashboard statistics
- `GET /api.php?endpoint=calculations` - Farming calculations

## 🗄️ Database Schema

The system uses SQLite with the following main tables:

### **Core Tables**
- `users` - Authentication and user management
- `chicken_groups` - Chicken breed/type categories
- `chicken_batches` - Batch management and tracking
- `chickens` - Individual chicken records
- `items` - Inventory items (feeds, medicine, equipment)
- `inventory` - Stock transactions and levels

### **Financial Tables**
- `sales` - Sales transactions and revenue
- `expenses` - Expense tracking by category
- `invoices` - Sales/purchase invoices
- `debts` - Money owed and money owing
- `earnings` - Additional income sources

### **Tracking Tables**
- `health_records` - Chicken health monitoring
- `audit_logs` - Activity logging and audit trails
- `finance_logs` - Financial transaction history

## 🔒 Security Features

### **Authentication & Access Control**
- Secure password hashing with bcrypt
- Session management with automatic regeneration
- Role-based access control (Admin, Manager, User)
- Automatic logout for inactive sessions

### **Data Protection**
- SQL injection prevention with prepared statements
- XSS protection with comprehensive input sanitization
- CSRF protection on sensitive operations
- Encrypted data storage for sensitive information

### **Audit & Monitoring**
- Comprehensive activity logging
- Failed login attempt monitoring
- Data access audit trails
- System health monitoring

## 🆕 New Features & Improvements

### **Enhanced Chicken Management**
- **Individual Chicken Tracking**: Age, size, weight, health status
- **Batch Management**: Group chickens by breed and purchase batch
- **Health Monitoring**: Track medical treatments and health records
- **Growth Analytics**: Monitor weight gain and development

### **Advanced Inventory System**
- **Multi-Category Support**: Feeds, medicines, equipment, supplies
- **Stock Level Monitoring**: Automatic low-stock alerts
- **Transaction History**: Complete audit trail of stock movements
- **Cost Tracking**: Unit costs and total inventory valuation

### **Comprehensive Financial Management**
- **Real-time Calculations**: Automatic profit/loss computations
- **Outstanding Tracking**: Monitor receivables and payables
- **Invoice Management**: Professional billing system
- **Debt Management**: Track money owed and owing

### **Data Visualization**
- **Interactive Charts**: Revenue trends, expense breakdowns
- **Financial Dashboards**: Visual representation of key metrics
- **Weather Integration**: Local weather data for farming decisions
- **Export Capabilities**: PDF, CSV, and Excel export options

### **User Experience Enhancements**
- **Responsive Design**: Optimized for desktop, tablet, and mobile
- **Intuitive Navigation**: Clear section organization
- **Quick Actions**: One-click access to common tasks
- **Search Functionality**: Global search across all records

## 🛠️ Development

### **Architecture**
- **Desktop**: Electron.js with Node.js backend and HTML/CSS/JS frontend
- **Web**: PHP MVC architecture with SQLite database
- **Database**: SQLite for cross-platform compatibility
- **API**: RESTful endpoints for data operations

### **Technology Stack**
- **Frontend**: HTML5, CSS3, JavaScript, Chart.js
- **Backend**: Node.js (Desktop) / PHP 8.0+ (Web)
- **Database**: SQLite 3
- **Desktop Framework**: Electron.js
- **Styling**: Custom responsive CSS with animations

### **Development Commands**
```bash
# Desktop Development
npm install          # Install dependencies
npm start           # Run in development mode
npm run build      # Build for current platform

# Web Development
php setup.php       # Initial setup
./start.sh          # Start web server
```

## 📋 System Requirements

### **Desktop Application**
- **OS**: Windows 10+, macOS 10.13+, Ubuntu 18.04+
- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 100MB free space
- **Node.js**: v16.0 or higher

### **Web Application**
- **OS**: Any OS supporting PHP
- **PHP**: 8.0 or higher with SQLite3 extension
- **RAM**: 1GB minimum, 2GB recommended
- **Storage**: 50MB free space

## 🐛 Troubleshooting

### **Desktop Application Issues**
```bash
# Clear cache and reinstall
rm -rf node_modules package-lock.json
npm install

# Reset database
rm database/kuku_farm.db
php database/create_db.php
```

### **Web Application Issues**
```bash
# Check PHP version
php --version

# Test SQLite extension
php -m | grep sqlite

# Reset database
php database/create_db.php
```

### **Common Issues**
- **Port conflicts**: Change port in start.sh
- **Permission errors**: Check file/folder permissions
- **Database locked**: Close other connections to database

## 📞 Support & Contributing

### **Getting Help**
1. Check the troubleshooting section above
2. Review the application logs
3. Ensure system requirements are met
4. Test with the default admin account

### **Contributing**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit changes (`git commit -am 'Add new feature'`)
4. Push to branch (`git push origin feature/new-feature`)
5. Create a Pull Request

### **Reporting Issues**
- Use the GitHub issue tracker
- Include system information and error messages
- Provide steps to reproduce the issue
- Attach relevant log files if available

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- **Chart.js** for data visualization
- **Electron.js** for desktop application framework
- **OpenWeatherMap** for weather API
- **SQLite** for reliable data storage

---

**Built with ❤️ for farmers, by developers who care about agriculture and rural communities.** 🐔🌾📊