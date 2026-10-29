const express = require('express');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const { Pool } = require('pg');
const path = require('path');
const fs = require('fs');

// Load environment variables
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3002;

// ============================================
// DATABASE CONNECTION
// ============================================
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  max: 20,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
});

pool.on('error', (err) => {
  console.error('Unexpected database error:', err);
});

// Test connection
pool.query('SELECT 1')
  .then(() => console.log('✅ Connected to PostgreSQL'))
  .catch((err) => {
    console.warn('⚠️ PostgreSQL connection failed, continuing without DB (SQLite fallback not implemented)');
  });

// ============================================
// MIDDLEWARE
// ============================================
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));

// Session configuration
const sessionSecret = process.env.SESSION_SECRET || 'change-this-in-production';

app.use(session({
  secret: sessionSecret,
  resave: false,
  saveUninitialized: false,
  cookie: {
    secure: process.env.NODE_ENV === 'production' && process.env.NODE_ENV !== 'development',
    httpOnly: true,
    maxAge: 3600000, // 1 hour
    sameSite: process.env.NODE_ENV === 'production' ? 'strict' : 'lax'
  }
}));

// ============================================
// AUTH MIDDLEWARE
// ============================================
function requireAuth(req, res, next) {
  if (req.session && req.session.userId) {
    next();
  } else {
    res.status(401).json({ error: 'Unauthorized' });
  }
}

function requireRole(role) {
  return (req, res, next) => {
    if (!req.session || !req.session.userId) {
      return res.status(401).json({ error: 'Unauthorized' });
    }
    if (req.session.userRole !== role && req.session.userRole !== 'admin') {
      return res.status(403).json({ error: 'Forbidden' });
    }
    next();
  };
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function sanitize(str) {
  if (typeof str !== 'string') return '';
  return str.replace(/[<>]/g, '').trim();
}

function logActivity(userId, action, tableName, recordId, req) {
  pool.query(
    `INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent)
     VALUES ($1, $2, $3, $4, $5, $6)`,
    [userId, action, tableName, recordId, req.ip, req.get('User-Agent') || 'unknown']
  ).catch(() => {}); // Don't fail on audit log errors
}

// ============================================
// ROUTES
// ============================================

// Health check
app.get('/api/status', (req, res) => {
  res.json({
    status: 'success',
    message: 'KUKU Farm Management API is running',
    version: '2.0.0',
    timestamp: new Date().toISOString(),
    features: {
      farming_calculator: true,
      weather_widget: true,
      time_widget: true,
      free_weather_api: 'Open-Meteo'
    }
  });
});

// Weather endpoint (public)
app.get('/api/weather', async (req, res) => {
  try {
    const lat = process.env.WEATHER_LAT || '-1.2921';
    const lon = process.env.WEATHER_LON || '36.8219';

    // Current weather from Open-Meteo (free API)
    const weatherUrl = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,relativehumidity_2m,pressure_msl,weathercode,windspeed_10m`;
    const forecastUrl = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum`;

    const [weatherRes, forecastRes] = await Promise.all([
      fetch(weatherUrl),
      fetch(forecastUrl)
    ]);

    const weatherData = await weatherRes.json();
    const forecastData = await forecastRes.json();

    if (!weatherData.current) {
      return res.json({ success: false, error: 'Invalid weather data' });
    }

    const current = weatherData.current;
    const weatherCode = current.weathercode ?? 0;

    const weatherDescriptions = {
      0: 'Clear sky', 1: 'Mainly clear', 2: 'Partly cloudy', 3: 'Overcast',
      45: 'Fog', 48: 'Fog', 51: 'Drizzle', 53: 'Drizzle', 55: 'Drizzle',
      56: 'Freezing Drizzle', 57: 'Freezing Drizzle',
      61: 'Slight Rain', 63: 'Moderate Rain', 65: 'Heavy Rain',
      66: 'Light Freezing Rain', 67: 'Moderate Freezing Rain',
      71: 'Slight Snow', 73: 'Moderate Snow', 75: 'Heavy Snow', 77: 'Snow Grains',
      80: 'Slight Rain Showers', 81: 'Moderate Rain Showers', 82: 'Violent Rain Showers',
      85: 'Slight Snow Showers', 86: 'Moderate Snow Showers'
    };

    const currentWeather = {
      success: true,
      city: 'Current Location',
      temperature: Math.round(current.temperature_2m ?? 0),
      feels_like: Math.round(current.temperature_2m ?? 0),
      humidity: Math.round(current.relativehumidity_2m ?? 0),
      pressure: Math.round(current.pressure_msl ?? 0),
      description: weatherDescriptions[weatherCode] || 'Unknown',
      icon: weatherCode,
      wind_speed: Math.round(current.windspeed_10m ?? 0),
      wind_direction: 0,
      visibility: 24,
      sunrise: 0,
      sunset: 0,
      timestamp: Math.floor(Date.now() / 1000)
    };

    // Forecast
    const daily = forecastData.daily;
    const forecast = [];
    if (daily && daily.time) {
      for (let i = 0; i < Math.min(5, daily.time.length); i++) {
        const code = daily.weathercode[i] ?? 0;
        forecast.push({
          date: daily.time[i],
          temp_min: Math.round(daily.temperature_2m_min[i] ?? 0),
          temp_max: Math.round(daily.temperature_2m_max[i] ?? 0),
          avg_humidity: 50,
          description: weatherDescriptions[code] || 'Unknown',
          icon: code,
          precipitation: Math.round((daily.precipitation_sum[i] ?? 0) * 100) / 100
        });
      }
    }

    // Farming advice
    const advice = [];
    if (currentWeather.success) {
      const temp = currentWeather.temperature;
      const humidity = currentWeather.humidity;

      if (temp >= 25 && temp <= 30) {
        advice.push('Optimal temperature for chicken growth and egg production.');
      } else if (temp > 30) {
        advice.push('High temperature alert! Ensure adequate ventilation and cooling.');
        advice.push('Provide extra water and consider misting systems.');
      } else if (temp < 15) {
        advice.push('Low temperature warning! Ensure proper heating for young chicks.');
        advice.push('Consider additional heat sources to maintain optimal temperature.');
      }

      if (humidity > 70) {
        advice.push('High humidity detected. Ensure good ventilation to prevent respiratory issues.');
      } else if (humidity < 40) {
        advice.push('Low humidity levels. Consider humidifiers in enclosed spaces.');
      }

      advice.push('Monitor chickens closely for heat stress signs.');
      advice.push('Ensure clean, fresh water is always available.');
    }

    res.json({
      current: currentWeather,
      forecast: { success: true, forecast },
      advice,
      last_updated: new Date().toISOString(),
      api_source: 'Open-Meteo (Free API - No Key Required)'
    });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// ============================================
// AUTH ROUTES
// ============================================

// Login
app.post('/api/auth/login', async (req, res) => {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password required' });
    }

    const result = await pool.query(
      'SELECT id, username, password, email, role, farmer_id FROM users WHERE username = $1',
      [username]
    );

    if (result.rows.length === 0) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const user = result.rows[0];

    const validPassword = await bcrypt.compare(password, user.password);
    if (!validPassword) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Set session
    req.session.userId = user.id;
    req.session.username = user.username;
    req.session.email = user.email;
    req.session.userRole = user.role;
    req.session.farmerId = user.farmer_id;
    req.session.loginTime = Date.now();

    logActivity(user.id, 'login', 'users', user.id, req);

    res.json({
      status: 'success',
      message: 'Login successful',
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        role: user.role,
        farmer_id: user.farmer_id
      }
    });
  } catch (err) {
    console.error('Login error:', err);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Logout
app.post('/api/auth/logout', (req, res) => {
  if (req.session && req.session.userId) {
    logActivity(req.session.userId, 'logout', 'users', req.session.userId, req);
  }

  req.session.destroy((err) => {
    if (err) {
      return res.status(500).json({ error: 'Logout failed' });
    }
    res.json({ status: 'success', message: 'Logged out successfully' });
  });
});

// Get current user
app.get('/api/auth/me', (req, res) => {
  if (!req.session || !req.session.userId) {
    return res.status(401).json({ error: 'Not logged in' });
  }

  res.json({
    id: req.session.userId,
    username: req.session.username,
    email: req.session.email,
    role: req.session.userRole,
    farmer_id: req.session.farmerId
  });
});

// Check if logged in
app.get('/api/auth/check', (req, res) => {
  if (req.session && req.session.userId) {
    res.json({ loggedIn: true, user: {
      id: req.session.userId,
      username: req.session.username,
      email: req.session.email,
      role: req.session.userRole,
      farmer_id: req.session.farmerId
    }});
  } else {
    res.json({ loggedIn: false });
  }
});

// Create user (admin only)
app.post('/api/auth/create-user', requireRole('admin'), async (req, res) => {
  try {
    const { username, password, email, role, farmerId } = req.body;

    if (!username || !password || !email) {
      return res.status(400).json({ error: 'Username, password, and email required' });
    }

    // Check if exists
    const existing = await pool.query(
      'SELECT id FROM users WHERE username = $1 OR email = $2',
      [username, email]
    );

    if (existing.rows.length > 0) {
      return res.status(400).json({ error: 'Username or email already exists' });
    }

    const hashedPassword = await bcrypt.hash(password, 12);

    const result = await pool.query(
      `INSERT INTO users (username, password, email, role, farmer_id)
       VALUES ($1, $2, $3, $4, $5)
       RETURNING id`,
      [username, hashedPassword, email, role || 'user', farmerId || null]
    );

    const newUserId = result.rows[0].id;
    logActivity(newUserId, 'create', 'users', newUserId, req);

    res.json({ status: 'success', userId: newUserId });
  } catch (err) {
    console.error('Create user error:', err);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update password
app.post('/api/auth/update-password', requireAuth, async (req, res) => {
  try {
    const { currentPassword, newPassword } = req.body;

    if (!currentPassword || !newPassword) {
      return res.status(400).json({ error: 'Current and new password required' });
    }

    const user = await pool.query(
      'SELECT password FROM users WHERE id = $1',
      [req.session.userId]
    );

    if (user.rows.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const valid = await bcrypt.compare(currentPassword, user.rows[0].password);
    if (!valid) {
      return res.status(400).json({ error: 'Current password is incorrect' });
    }

    const hashedNewPassword = await bcrypt.hash(newPassword, 12);

    await pool.query(
      'UPDATE users SET password = $1, updated_at = NOW() WHERE id = $2',
      [hashedNewPassword, req.session.userId]
    );

    logActivity(req.session.userId, 'password_change', 'users', req.session.userId, req);

    res.json({ status: 'success', message: 'Password updated successfully' });
  } catch (err) {
    console.error('Update password error:', err);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// ============================================
// DASHBOARD STATS
// ============================================
app.get('/api/dashboard', requireAuth, async (req, res) => {
  try {
    // Farmers stats
    const farmersResult = await pool.query(`
      SELECT
        COUNT(*) as total_farmers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_farmers,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_farmers,
        COUNT(CASE WHEN join_date >= CURRENT_DATE - INTERVAL '1 month' THEN 1 END) as new_farmers_month
      FROM farmers
    `);

    // Chicken stats
    const chickensResult = await pool.query(`
      SELECT
        COUNT(*) as total_chickens,
        COUNT(CASE WHEN status = 'healthy' THEN 1 END) as healthy_chickens,
        COUNT(CASE WHEN status = 'sick' THEN 1 END) as sick_chickens,
        COUNT(CASE WHEN status = 'sold' THEN 1 END) as sold_chickens,
        COUNT(CASE WHEN status = 'deceased' THEN 1 END) as deceased_chickens,
        COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_chickens,
        COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_chickens,
        AVG(weight) as average_weight
      FROM chickens
    `);

    // Sales stats (last month)
    const salesResult = await pool.query(`
      SELECT
        COUNT(*) as total_sales,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as average_sale,
        COALESCE(SUM(quantity), 0) as total_chickens_sold,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_sales,
        COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_sales,
        COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_sales
      FROM sales
      WHERE sale_date >= CURRENT_DATE - INTERVAL '1 month'
    `);

    // Expenses stats (last month)
    const expensesResult = await pool.query(`
      SELECT
        COUNT(*) as total_expenses,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(AVG(amount), 0) as average_expense,
        COALESCE(SUM(CASE WHEN category = 'feed' THEN amount END), 0) as feed_total,
        COALESCE(SUM(CASE WHEN category = 'medicine' THEN amount END), 0) as medicine_total,
        COALESCE(SUM(CASE WHEN category = 'labor' THEN amount END), 0) as labor_total,
        COALESCE(SUM(CASE WHEN category = 'equipment' THEN amount END), 0) as equipment_total
      FROM expenses
      WHERE expense_date >= CURRENT_DATE - INTERVAL '1 month'
    `);

    // Recent sales
    const recentSalesResult = await pool.query(`
      SELECT s.*, c.tag_number, cb.batch_number, u.username as created_by_name
      FROM sales s
      LEFT JOIN chickens c ON s.chicken_id = c.id
      LEFT JOIN chicken_batches cb ON s.batch_id = cb.id
      LEFT JOIN users u ON s.created_by = u.id
      ORDER BY s.sale_date DESC
      LIMIT 5
    `);

    // Recent expenses
    const recentExpensesResult = await pool.query(`
      SELECT e.*, u.username as created_by_name
      FROM expenses e
      LEFT JOIN users u ON e.created_by = u.id
      ORDER BY e.expense_date DESC
      LIMIT 5
    `);

    res.json({
      status: 'success',
      data: {
        farmers: farmersResult.rows[0],
        chickens: chickensResult.rows[0],
        sales: salesResult.rows[0],
        expenses: expensesResult.rows[0],
        recent_sales: recentSalesResult.rows,
        recent_expenses: recentExpensesResult.rows
      }
    });
  } catch (err) {
    console.error('Dashboard error:', err);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// ============================================
// FARMERS
// ============================================
app.get('/api/farmers', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT f.*, u.username, u.email as user_email, u.role
      FROM farmers f
      LEFT JOIN users u ON f.id = u.farmer_id
      ORDER BY f.name
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/farmers/stats', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT
        COUNT(*) as total_farmers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_farmers,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_farmers,
        COUNT(CASE WHEN join_date >= CURRENT_DATE - INTERVAL '1 month' THEN 1 END) as new_farmers_month
      FROM farmers
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/farmers/search', requireAuth, async (req, res) => {
  try {
    const { q } = req.query;
    if (!q) return res.json({ status: 'success', data: [] });

    const result = await pool.query(
      `SELECT * FROM farmers
       WHERE name ILIKE $1 OR email ILIKE $1 OR phone ILIKE $1
       ORDER BY name`,
      [`%${q}%`]
    );
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/farmers/active', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(
      'SELECT * FROM farmers WHERE status = $1 ORDER BY name',
      ['active']
    );
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/farmers', requireAuth, async (req, res) => {
  try {
    const { name, email, phone, address } = req.body;

    if (!name) {
      return res.status(400).json({ error: 'Name is required' });
    }

    const result = await pool.query(
      `INSERT INTO farmers (name, email, phone, address)
       VALUES ($1, $2, $3, $4)
       RETURNING *`,
      [name, email || null, phone || null, address || null]
    );

    logActivity(req.session.userId, 'create', 'farmers', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    if (err.code === '23505') {
      return res.status(400).json({ error: 'Email already exists' });
    }
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/farmers/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { name, email, phone, address, status } = req.body;

    const existing = await pool.query('SELECT * FROM farmers WHERE id = $1', [id]);
    if (existing.rows.length === 0) {
      return res.status(404).json({ error: 'Farmer not found' });
    }

    const result = await pool.query(
      `UPDATE farmers
       SET name = $1, email = $2, phone = $3, address = $4, status = $5, updated_at = NOW()
       WHERE id = $6
       RETURNING *`,
      [name ?? existing.rows[0].name, email ?? existing.rows[0].email,
       phone ?? existing.rows[0].phone, address ?? existing.rows[0].address,
       status ?? existing.rows[0].status, id]
    );

    logActivity(req.session.userId, 'update', 'farmers', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/farmers/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;

    await pool.query('DELETE FROM farmers WHERE id = $1', [id]);

    logActivity(req.session.userId, 'delete', 'farmers', id, req);

    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// CHICKEN GROUPS
// ============================================
app.get('/api/chicken-groups', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT cg.*,
             (SELECT COUNT(*) FROM chicken_batches WHERE group_id = cg.id) as batch_count,
             (SELECT COUNT(*) FROM chickens c JOIN chicken_batches cb ON c.batch_id = cb.id WHERE cb.group_id = cg.id) as chicken_count
      FROM chicken_groups cg
      ORDER BY cg.name
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/chicken-groups', requireAuth, async (req, res) => {
  try {
    const { name, description, breed } = req.body;

    if (!name) {
      return res.status(400).json({ error: 'Name is required' });
    }

    const result = await pool.query(
      `INSERT INTO chicken_groups (name, description, breed)
       VALUES ($1, $2, $3)
       RETURNING *`,
      [name, description || null, breed || null]
    );

    logActivity(req.session.userId, 'create', 'chicken_groups', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/chicken-groups/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { name, description, breed } = req.body;

    const result = await pool.query(
      `UPDATE chicken_groups
       SET name = $1, description = $2, breed = $3
       WHERE id = $4
       RETURNING *`,
      [name, description || null, breed || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Group not found' });
    }

    logActivity(req.session.userId, 'update', 'chicken_groups', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/chicken-groups/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM chicken_groups WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'chicken_groups', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// CHICKEN BATCHES
// ============================================
app.get('/api/chicken-batches', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT cb.*, cg.name as group_name, cg.breed,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id) as chicken_count,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'healthy') as healthy_count,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'sick') as sick_count,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'sold') as sold_count,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'deceased') as deceased_count
      FROM chicken_batches cb
      LEFT JOIN chicken_groups cg ON cb.group_id = cg.id
      ORDER BY cb.purchase_date DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/chicken-batches/active', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT cb.*, cg.name as group_name, cg.breed,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status != 'sold') as chicken_count
      FROM chicken_batches cb
      JOIN chicken_groups cg ON cb.group_id = cg.id
      WHERE (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status != 'sold') > 0
      ORDER BY cb.purchase_date DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/chicken-batches/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;

    const batchResult = await pool.query(`
      SELECT cb.*, cg.name as group_name, cg.breed,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id) as total_chickens,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'healthy') as healthy_chickens,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'sick') as sick_chickens,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'sold') as sold_chickens,
             (SELECT COUNT(*) FROM chickens WHERE batch_id = cb.id AND status = 'deceased') as deceased_chickens,
             AVG(c.weight) as average_weight,
             MIN(c.birth_date) as earliest_birth_date
      FROM chicken_batches cb
      JOIN chicken_groups cg ON cb.group_id = cg.id
      LEFT JOIN chickens c ON cb.id = c.batch_id
      WHERE cb.id = $1
      GROUP BY cb.id
    `, [id]);

    if (batchResult.rows.length === 0) {
      return res.status(404).json({ error: 'Batch not found' });
    }

    const chickensResult = await pool.query(`
      SELECT c.*, cb.batch_number, cg.name as group_name
      FROM chickens c
      JOIN chicken_batches cb ON c.batch_id = cb.id
      JOIN chicken_groups cg ON cb.group_id = cg.id
      WHERE c.batch_id = $1
      ORDER BY c.tag_number
    `, [id]);

    res.json({
      status: 'success',
      batch: batchResult.rows[0],
      chickens: chickensResult.rows
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/chicken-batches', requireAuth, async (req, res) => {
  try {
    const { batch_number, group_id, quantity, purchase_date, purchase_cost, supplier, notes } = req.body;

    if (!batch_number || !group_id || !quantity) {
      return res.status(400).json({ error: 'Batch number, group, and quantity are required' });
    }

    const result = await pool.query(
      `INSERT INTO chicken_batches (batch_number, group_id, quantity, purchase_date, purchase_cost, supplier, notes)
       VALUES ($1, $2, $3, $4, $5, $6, $7)
       RETURNING *`,
      [batch_number, group_id, quantity, purchase_date || new Date().toISOString().split('T')[0],
       purchase_cost || 0, supplier || null, notes || null]
    );

    logActivity(req.session.userId, 'create', 'chicken_batches', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/chicken-batches/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { batch_number, group_id, quantity, purchase_date, purchase_cost, supplier, notes } = req.body;

    const result = await pool.query(
      `UPDATE chicken_batches
       SET batch_number = $1, group_id = $2, quantity = $3, purchase_date = $4,
           purchase_cost = $5, supplier = $6, notes = $7, updated_at = NOW()
       WHERE id = $8
       RETURNING *`,
      [batch_number, group_id, quantity, purchase_date, purchase_cost || 0,
       supplier || null, notes || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Batch not found' });
    }

    logActivity(req.session.userId, 'update', 'chicken_batches', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/chicken-batches/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM chicken_batches WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'chicken_batches', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// CHICKENS
// ============================================
app.get('/api/chickens', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT c.*, cb.batch_number, cb.purchase_date, cg.name as group_name
      FROM chickens c
      JOIN chicken_batches cb ON c.batch_id = cb.id
      JOIN chicken_groups cg ON cb.group_id = cg.id
      ORDER BY c.created_at DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/chickens/stats', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT
        COUNT(*) as total_chickens,
        COUNT(CASE WHEN status = 'healthy' THEN 1 END) as healthy_chickens,
        COUNT(CASE WHEN status = 'sick' THEN 1 END) as sick_chickens,
        COUNT(CASE WHEN status = 'sold' THEN 1 END) as sold_chickens,
        COUNT(CASE WHEN status = 'deceased' THEN 1 END) as deceased_chickens,
        COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_chickens,
        COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_chickens,
        AVG(weight) as average_weight
      FROM chickens
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/chickens/healthy-count', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(
      'SELECT COUNT(*) as count FROM chickens WHERE status = $1',
      ['healthy']
    );
    res.json({ status: 'success', count: result.rows[0].count });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/chickens/batch/:batchId', requireAuth, async (req, res) => {
  try {
    const { batchId } = req.params;
    const result = await pool.query(`
      SELECT c.*, cb.batch_number, cg.name as group_name
      FROM chickens c
      JOIN chicken_batches cb ON c.batch_id = cb.id
      JOIN chicken_groups cg ON cb.group_id = cg.id
      WHERE c.batch_id = $1
      ORDER BY c.tag_number
    `, [batchId]);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/chickens', requireAuth, async (req, res) => {
  try {
    const { batch_id, tag_number, gender, birth_date, weight, status, location, notes } = req.body;

    if (!batch_id) {
      return res.status(400).json({ error: 'Batch ID is required' });
    }

    const result = await pool.query(
      `INSERT INTO chickens (batch_id, tag_number, gender, birth_date, weight, status, location, notes)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
       RETURNING *`,
      [batch_id, tag_number || null, gender || null, birth_date || null,
       weight || null, status || 'healthy', location || null, notes || null]
    );

    logActivity(req.session.userId, 'create', 'chickens', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    if (err.code === '23505') {
      return res.status(400).json({ error: 'Tag number already exists' });
    }
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/chickens/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { tag_number, gender, birth_date, weight, status, location, notes } = req.body;

    const result = await pool.query(
      `UPDATE chickens
       SET tag_number = $1, gender = $2, birth_date = $3, weight = $4,
           status = $5, location = $6, notes = $7, updated_at = NOW()
       WHERE id = $8
       RETURNING *`,
      [tag_number || null, gender || null, birth_date || null, weight || null,
       status || 'healthy', location || null, notes || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Chicken not found' });
    }

    logActivity(req.session.userId, 'update', 'chickens', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/chickens/:id/status', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body;

    const result = await pool.query(
      `UPDATE chickens SET status = $1, updated_at = NOW() WHERE id = $2 RETURNING *`,
      [status, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Chicken not found' });
    }

    logActivity(req.session.userId, 'update_status', 'chickens', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/chickens/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM chickens WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'chickens', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// HEALTH RECORDS
// ============================================
app.get('/api/health-records', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT hr.*, c.tag_number
      FROM health_records hr
      JOIN chickens c ON hr.chicken_id = c.id
      ORDER BY hr.check_date DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/health-records/chicken/:chickenId', requireAuth, async (req, res) => {
  try {
    const { chickenId } = req.params;
    const result = await pool.query(
      'SELECT * FROM health_records WHERE chicken_id = $1 ORDER BY check_date DESC',
      [chickenId]
    );
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/health-records', requireAuth, async (req, res) => {
  try {
    const { chicken_id, check_date, weight, temperature, symptoms, diagnosis, treatment, veterinarian, cost, next_check_date } = req.body;

    if (!chicken_id || !check_date) {
      return res.status(400).json({ error: 'Chicken ID and check date are required' });
    }

    const result = await pool.query(
      `INSERT INTO health_records (chicken_id, check_date, weight, temperature, symptoms, diagnosis, treatment, veterinarian, cost, next_check_date)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
       RETURNING *`,
      [chicken_id, check_date, weight || null, temperature || null, symptoms || null,
       diagnosis || null, treatment || null, veterinarian || null, cost || null, next_check_date || null]
    );

    logActivity(req.session.userId, 'create', 'health_records', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/health-records/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { weight, temperature, symptoms, diagnosis, treatment, veterinarian, cost, next_check_date } = req.body;

    const result = await pool.query(
      `UPDATE health_records
       SET weight = $1, temperature = $2, symptoms = $3, diagnosis = $4,
           treatment = $5, veterinarian = $6, cost = $7, next_check_date = $8
       WHERE id = $9
       RETURNING *`,
      [weight || null, temperature || null, symptoms || null, diagnosis || null,
       treatment || null, veterinarian || null, cost || null, next_check_date || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Health record not found' });
    }

    logActivity(req.session.userId, 'update', 'health_records', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/health-records/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM health_records WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'health_records', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// INVENTORY ITEMS
// ============================================
app.get('/api/items', requireAuth, async (req, res) => {
  try {
    const { category } = req.query;

    let query;
    if (category) {
      query = `
        SELECT i.*,
               COALESCE((SELECT SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END)
                         FROM inventory WHERE item_id = i.id), 0) as current_stock
        FROM items i
        WHERE i.category = $1
        ORDER BY i.name ASC
      `;
    } else {
      query = `
        SELECT i.*,
               COALESCE((SELECT SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END)
                         FROM inventory WHERE item_id = i.id), 0) as current_stock
        FROM items i
        ORDER BY i.category ASC, i.name ASC
      `;
    }

    const result = await pool.query(query, category ? [category] : []);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/items/stats', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT
        COUNT(*) as total_items,
        COUNT(CASE WHEN category = 'feed' THEN 1 END) as feed_items,
        COUNT(CASE WHEN category = 'medicine' THEN 1 END) as medicine_items,
        COUNT(CASE WHEN category = 'equipment' THEN 1 END) as equipment_items,
        COUNT(CASE WHEN category = 'supplies' THEN 1 END) as supplies_items
      FROM items
      WHERE created_at >= CURRENT_DATE - INTERVAL '1 month'
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/items/low-stock', requireAuth, async (req, res) => {
  try {
    const threshold = parseInt(req.query.threshold) || 10;

    const result = await pool.query(`
      SELECT i.*,
             COALESCE((SELECT SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END)
                       FROM inventory WHERE item_id = i.id), 0) as current_stock
      FROM items i
      WHERE COALESCE((SELECT SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END)
                      FROM inventory WHERE item_id = i.id), 0) <= $1
      ORDER BY COALESCE((SELECT SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END)
                         FROM inventory WHERE item_id = i.id), 0) ASC
    `, [threshold]);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/items/inventory-value', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT COALESCE(SUM(
        COALESCE((SELECT SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END)
                  FROM inventory WHERE item_id = i.id), 0) * i.unit_cost
      ), 0) as total_value
      FROM items i
    `);
    res.json({ status: 'success', total_value: result.rows[0].total_value });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/items/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const result = await pool.query(
      `SELECT i.*,
              COALESCE((SELECT SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END)
                        FROM inventory WHERE item_id = i.id), 0) as current_stock
       FROM items i WHERE i.id = $1`,
      [id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Item not found' });
    }

    // Get recent transactions
    const transactionsResult = await pool.query(
      `SELECT inv.*, u.username as created_by_name
       FROM inventory inv
       LEFT JOIN users u ON inv.created_by = u.id
       WHERE inv.item_id = $1
       ORDER BY inv.created_at DESC
       LIMIT 10`,
      [id]
    );

    res.json({
      status: 'success',
      item: result.rows[0],
      recent_transactions: transactionsResult.rows
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/items', requireAuth, async (req, res) => {
  try {
    const { name, category, description, unit, unit_cost, supplier } = req.body;

    if (!name || !unit) {
      return res.status(400).json({ error: 'Name and unit are required' });
    }

    const result = await pool.query(
      `INSERT INTO items (name, category, description, unit, unit_cost, supplier)
       VALUES ($1, $2, $3, $4, $5, $6)
       RETURNING *`,
      [name, category || 'supplies', description || null, unit, unit_cost || 0, supplier || null]
    );

    logActivity(req.session.userId, 'create', 'items', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/items/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { name, category, description, unit, unit_cost, supplier } = req.body;

    const result = await pool.query(
      `UPDATE items
       SET name = $1, category = $2, description = $3, unit = $4,
           unit_cost = $5, supplier = $6
       WHERE id = $7
       RETURNING *`,
      [name, category, description || null, unit, unit_cost || 0, supplier || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Item not found' });
    }

    logActivity(req.session.userId, 'update', 'items', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/items/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM items WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'items', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// INVENTORY TRANSACTIONS (Stock)
// ============================================
app.get('/api/inventory/transactions', requireAuth, async (req, res) => {
  try {
    const { item_id } = req.query;

    let query = `
      SELECT inv.*, i.name as item_name, u.username as created_by_name
      FROM inventory inv
      JOIN items i ON inv.item_id = i.id
      LEFT JOIN users u ON inv.created_by = u.id
    `;

    const params = [];

    if (item_id) {
      query += ' WHERE inv.item_id = $1';
      params.push(item_id);
    }

    query += ' ORDER BY inv.created_at DESC';

    const result = await pool.query(query, params);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/inventory/transactions', requireAuth, async (req, res) => {
  try {
    const { item_id, transaction_type, quantity, notes } = req.body;

    if (!item_id || !transaction_type || !quantity) {
      return res.status(400).json({ error: 'Item ID, transaction type, and quantity are required' });
    }

    // Get item for unit cost
    const itemResult = await pool.query('SELECT * FROM items WHERE id = $1', [item_id]);
    if (itemResult.rows.length === 0) {
      return res.status(404).json({ error: 'Item not found' });
    }

    const item = itemResult.rows[0];
    const unitCost = item.unit_cost || 0;
    const totalCost = quantity * unitCost;
    const transactionDate = new Date().toISOString().split('T')[0];

    const result = await pool.query(
      `INSERT INTO inventory (item_id, transaction_type, quantity, unit_cost, total_cost, transaction_date, notes, created_by)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
       RETURNING *`,
      [item_id, transaction_type, quantity, unitCost, totalCost, transactionDate, notes || null, req.session.userId]
    );

    logActivity(req.session.userId, 'create', 'inventory', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// SALES
// ============================================
app.get('/api/sales', requireAuth, async (req, res) => {
  try {
    const { start_date, end_date } = req.query;

    let query = `
      SELECT s.*, c.tag_number, cb.batch_number, u.username as created_by_name
      FROM sales s
      LEFT JOIN chickens c ON s.chicken_id = c.id
      LEFT JOIN chicken_batches cb ON s.batch_id = cb.id
      LEFT JOIN users u ON s.created_by = u.id
      ORDER BY s.sale_date DESC
    `;

    const params = [];

    if (start_date || end_date) {
      query = query.replace('ORDER BY', 'WHERE 1=1');
      if (start_date) {
        query += ' AND s.sale_date >= $1';
        params.push(start_date);
      }
      if (end_date) {
        query += ' AND s.sale_date <= $2';
        params.push(end_date);
      }
      query += ' ORDER BY s.sale_date DESC';
    }

    const result = await pool.query(query, params);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/sales/stats', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT
        COUNT(*) as total_sales,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as average_sale,
        COALESCE(SUM(quantity), 0) as total_chickens_sold,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_sales,
        COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_sales,
        COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_sales
      FROM sales
      WHERE sale_date >= CURRENT_DATE - INTERVAL '${interval}'
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/sales/total-revenue', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT COALESCE(SUM(total_amount), 0) as total
      FROM sales
      WHERE sale_date >= CURRENT_DATE - INTERVAL '${interval}'
    `);
    res.json({ status: 'success', total: result.rows[0].total });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/sales/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const result = await pool.query(`
      SELECT s.*, c.tag_number, cb.batch_number, u.username as created_by_name
      FROM sales s
      LEFT JOIN chickens c ON s.chicken_id = c.id
      LEFT JOIN chicken_batches cb ON s.batch_id = cb.id
      LEFT JOIN users u ON s.created_by = u.id
      WHERE s.id = $1
    `, [id]);

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Sale not found' });
    }

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/sales', requireAuth, async (req, res) => {
  try {
    const { sale_date, customer_name, customer_phone, quantity, unit_price, total_amount, payment_method, payment_status, notes, chicken_id, batch_id } = req.body;

    if (!sale_date || !customer_name || !unit_price) {
      return res.status(400).json({ error: 'Sale date, customer name, and unit price are required' });
    }

    const result = await pool.query(
      `INSERT INTO sales (sale_date, customer_name, customer_phone, quantity, unit_price, total_amount, payment_method, payment_status, notes, created_by, chicken_id, batch_id)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
       RETURNING *`,
      [sale_date, customer_name, customer_phone || null, quantity || 1, unit_price,
       total_amount || (quantity || 1) * unit_price, payment_method || 'cash', payment_status || 'paid',
       notes || null, req.session.userId, chicken_id || null, batch_id || null]
    );

    logActivity(req.session.userId, 'create', 'sales', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/sales/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { sale_date, customer_name, customer_phone, quantity, unit_price, total_amount, payment_method, payment_status, notes, chicken_id, batch_id } = req.body;

    const result = await pool.query(
      `UPDATE sales
       SET sale_date = $1, customer_name = $2, customer_phone = $3, quantity = $4,
           unit_price = $5, total_amount = $6, payment_method = $7, payment_status = $8,
           notes = $9, chicken_id = $10, batch_id = $11, updated_at = NOW()
       WHERE id = $12
       RETURNING *`,
      [sale_date, customer_name, customer_phone || null, quantity || 1, unit_price,
       total_amount || quantity * unit_price, payment_method || 'cash', payment_status || 'paid',
       notes || null, chicken_id || null, batch_id || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Sale not found' });
    }

    logActivity(req.session.userId, 'update', 'sales', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/sales/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM sales WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'sales', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// EXPENSES
// ============================================
app.get('/api/expenses', requireAuth, async (req, res) => {
  try {
    const { category, start_date, end_date } = req.query;

    let query = `
      SELECT e.*, u.username as created_by_name
      FROM expenses e
      LEFT JOIN users u ON e.created_by = u.id
      ORDER BY e.expense_date DESC
    `;

    const params = [];
    let conditions = [];

    if (category) {
      conditions.push('e.category = $1');
      params.push(category);
    }
    if (start_date) {
      conditions.push('e.expense_date >= $2');
      params.push(start_date);
    }
    if (end_date) {
      conditions.push('e.expense_date <= $3');
      params.push(end_date);
    }

    if (conditions.length > 0) {
      query = query.replace('ORDER BY', 'WHERE ' + conditions.join(' AND ') + ' ORDER BY');
    }

    const result = await pool.query(query, params);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/expenses/stats', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT
        COUNT(*) as total_expenses,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(AVG(amount), 0) as average_expense,
        COUNT(CASE WHEN category = 'feed' THEN 1 END) as feed_expenses,
        COUNT(CASE WHEN category = 'medicine' THEN 1 END) as medicine_expenses,
        COUNT(CASE WHEN category = 'labor' THEN 1 END) as labor_expenses,
        COUNT(CASE WHEN category = 'equipment' THEN 1 END) as equipment_expenses,
        COALESCE(SUM(CASE WHEN category = 'feed' THEN amount END), 0) as feed_total,
        COALESCE(SUM(CASE WHEN category = 'medicine' THEN amount END), 0) as medicine_total,
        COALESCE(SUM(CASE WHEN category = 'labor' THEN amount END), 0) as labor_total,
        COALESCE(SUM(CASE WHEN category = 'equipment' THEN amount END), 0) as equipment_total
      FROM expenses
      WHERE expense_date >= CURRENT_DATE - INTERVAL '${interval}'
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/expenses/by-category', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT
        category,
        COUNT(*) as count,
        COALESCE(SUM(amount), 0) as total
      FROM expenses
      WHERE expense_date >= CURRENT_DATE - INTERVAL '${interval}'
      GROUP BY category
      ORDER BY total DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/expenses', requireAuth, async (req, res) => {
  try {
    const { category, description, amount, expense_date, supplier, payment_method, notes } = req.body;

    if (!category || !description || !amount) {
      return res.status(400).json({ error: 'Category, description, and amount are required' });
    }

    const result = await pool.query(
      `INSERT INTO expenses (category, description, amount, expense_date, supplier, payment_method, notes, created_by)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
       RETURNING *`,
      [category, description, amount, expense_date || new Date().toISOString().split('T')[0],
       supplier || null, payment_method || 'cash', notes || null, req.session.userId]
    );

    logActivity(req.session.userId, 'create', 'expenses', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/expenses/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { category, description, amount, expense_date, supplier, payment_method, notes } = req.body;

    const result = await pool.query(
      `UPDATE expenses
       SET category = $1, description = $2, amount = $3, expense_date = $4,
           supplier = $5, payment_method = $6, notes = $7, updated_at = NOW()
       WHERE id = $8
       RETURNING *`,
      [category, description, amount, expense_date, supplier || null,
       payment_method || 'cash', notes || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Expense not found' });
    }

    logActivity(req.session.userId, 'update', 'expenses', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/expenses/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM expenses WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'expenses', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// INVOICES
// ============================================
app.get('/api/invoices', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT i.*, u.username as created_by_name
      FROM invoices i
      LEFT JOIN users u ON i.created_by = u.id
      ORDER BY i.invoice_date DESC, i.created_at DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/invoices/stats', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT
        COUNT(*) as total_invoices,
        COALESCE(SUM(total_amount), 0) as total_amount,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(total_amount - paid_amount), 0) as total_outstanding,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices,
        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_invoices,
        COUNT(CASE WHEN type = 'sale' THEN 1 END) as sales_invoices,
        COUNT(CASE WHEN type = 'purchase' THEN 1 END) as purchase_invoices
      FROM invoices
      WHERE invoice_date >= CURRENT_DATE - INTERVAL '${interval}'
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/invoices/outstanding', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT * FROM invoices
      WHERE total_amount > paid_amount AND status IN ('pending', 'partial')
      ORDER BY due_date ASC, invoice_date DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/invoices/overdue', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT * FROM invoices
      WHERE due_date < CURRENT_DATE AND total_amount > paid_amount AND status IN ('pending', 'partial')
      ORDER BY due_date ASC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/invoices/total-outstanding', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT COALESCE(SUM(total_amount - paid_amount), 0) as total
      FROM invoices
      WHERE total_amount > paid_amount AND status IN ('pending', 'partial')
    `);
    res.json({ status: 'success', total: result.rows[0].total });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/invoices/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;

    const invoiceResult = await pool.query(
      `SELECT i.*, u.username as created_by_name
       FROM invoices i
       LEFT JOIN users u ON i.created_by = u.id
       WHERE i.id = $1`,
      [id]
    );

    if (invoiceResult.rows.length === 0) {
      return res.status(404).json({ error: 'Invoice not found' });
    }

    const itemsResult = await pool.query(
      'SELECT * FROM invoice_items WHERE invoice_id = $1 ORDER BY id ASC',
      [id]
    );

    res.json({
      status: 'success',
      invoice: invoiceResult.rows[0],
      items: itemsResult.rows
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/invoices', requireAuth, async (req, res) => {
  try {
    const { type, customer_supplier_name, customer_supplier_phone, customer_supplier_email,
            invoice_date, due_date, total_amount, tax_amount, discount_amount,
            payment_terms, notes, reference_sale_id, reference_expense_id } = req.body;

    if (!type || !customer_supplier_name || !total_amount) {
      return res.status(400).json({ error: 'Type, customer/supplier name, and total amount are required' });
    }

    // Generate invoice number
    const prefix = 'INV-' + new Date().toISOString().slice(0, 7);
    const countResult = await pool.query(
      `SELECT COUNT(*) as count FROM invoices WHERE invoice_number LIKE $1`,
      [prefix + '%']
    );
    const sequence = String(countResult.rows[0].count + 1).padStart(4, '0');
    const invoiceNumber = prefix + '-' + sequence;

    const result = await pool.query(
      `INSERT INTO invoices (invoice_number, type, customer_supplier_name, customer_supplier_phone,
       customer_supplier_email, invoice_date, due_date, total_amount, tax_amount, discount_amount,
       payment_terms, notes, reference_sale_id, reference_expense_id, created_by)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15)
       RETURNING *`,
      [invoiceNumber, type, customer_supplier_name, customer_supplier_phone || null,
       customer_supplier_email || null, invoice_date || new Date().toISOString().split('T')[0],
       due_date || null, total_amount, tax_amount || 0, discount_amount || 0,
       payment_terms || null, notes || null, reference_sale_id || null, reference_expense_id || null,
       req.session.userId]
    );

    logActivity(req.session.userId, 'create', 'invoices', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/invoices/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { customer_supplier_name, customer_supplier_phone, customer_supplier_email,
            invoice_date, due_date, total_amount, tax_amount, discount_amount,
            payment_terms, notes, status, reference_sale_id, reference_expense_id } = req.body;

    const result = await pool.query(
      `UPDATE invoices
       SET customer_supplier_name = $1, customer_supplier_phone = $2, customer_supplier_email = $3,
           invoice_date = $4, due_date = $5, total_amount = $6, tax_amount = $7, discount_amount = $8,
           payment_terms = $9, notes = $10, status = $11, reference_sale_id = $12,
           reference_expense_id = $13, updated_at = NOW()
       WHERE id = $14
       RETURNING *`,
      [customer_supplier_name, customer_supplier_phone || null, customer_supplier_email || null,
       invoice_date, due_date || null, total_amount, tax_amount || 0, discount_amount || 0,
       payment_terms || null, notes || null, status || 'pending',
       reference_sale_id || null, reference_expense_id || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Invoice not found' });
    }

    logActivity(req.session.userId, 'update', 'invoices', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/invoices/:id/payment', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { payment_amount, payment_method, notes } = req.body;

    if (!payment_amount || payment_amount <= 0) {
      return res.status(400).json({ error: 'Payment amount must be greater than zero' });
    }

    // Get current invoice
    const invoice = await pool.query('SELECT * FROM invoices WHERE id = $1', [id]);
    if (invoice.rows.length === 0) {
      return res.status(404).json({ error: 'Invoice not found' });
    }

    const current = invoice.rows[0];
    const newPaidAmount = Math.min(current.paid_amount + payment_amount, current.total_amount);
    const status = newPaidAmount >= current.total_amount ? 'paid' : 'partial';

    // Update invoice
    await pool.query(
      `UPDATE invoices SET paid_amount = $1, status = $2, updated_at = NOW() WHERE id = $3`,
      [newPaidAmount, status, id]
    );

    // Record payment
    await pool.query(
      `INSERT INTO invoice_payments (invoice_id, payment_amount, payment_date, payment_method, notes, created_by)
       VALUES ($1, $2, $3, $4, $5, $6)`,
      [id, payment_amount, new Date().toISOString().split('T')[0], payment_method || 'cash',
       notes || null, req.session.userId]
    );

    logActivity(req.session.userId, 'add_payment', 'invoices', id, req);

    res.json({ status: 'success', paid_amount: newPaidAmount, status });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/invoices/:id/items', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { description, quantity, unit_price, total_amount, category } = req.body;

    if (!description || !unit_price) {
      return res.status(400).json({ error: 'Description and unit price are required' });
    }

    const result = await pool.query(
      `INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total_amount, category)
       VALUES ($1, $2, $3, $4, $5, $6)
       RETURNING *`,
      [id, description, quantity || 1, unit_price, total_amount || quantity * unit_price, category || null]
    );

    logActivity(req.session.userId, 'create', 'invoice_items', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/invoices/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM invoices WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'invoices', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// DEBTS
// ============================================
app.get('/api/debts', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT d.*, u.username as created_by_name
      FROM debts d
      LEFT JOIN users u ON d.created_by = u.id
      ORDER BY d.due_date ASC, d.created_at DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/debts/stats', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT
        COUNT(*) as total_debts,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(amount - paid_amount), 0) as total_outstanding,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_debts,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_debts,
        COUNT(CASE WHEN debt_type = 'owed_to_us' THEN 1 END) as debts_owed_to_us,
        COUNT(CASE WHEN debt_type = 'we_owe' THEN 1 END) as debts_we_owe,
        COUNT(CASE WHEN due_date < CURRENT_DATE AND amount > paid_amount AND status = 'active' THEN 1 END) as overdue_debts
      FROM debts
      WHERE created_at >= CURRENT_DATE - INTERVAL '${interval}'
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/debts/outstanding', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT * FROM debts
      WHERE amount > paid_amount AND status = 'active'
      ORDER BY due_date ASC, created_at DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/debts/overdue', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT * FROM debts
      WHERE due_date < CURRENT_DATE AND amount > paid_amount AND status = 'active'
      ORDER BY due_date ASC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/debts/total-outstanding', requireAuth, async (req, res) => {
  try {
    const { type } = req.query;
    let whereClause = 'WHERE amount > paid_amount AND status = $1';
    const params = ['active'];

    if (type) {
      whereClause += ' AND debt_type = $2';
      params.push(type);
    }

    const result = await pool.query(
      `SELECT COALESCE(SUM(amount - paid_amount), 0) as total FROM debts ${whereClause}`,
      params
    );
    res.json({ status: 'success', total: result.rows[0].total });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/debts/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;

    const debtResult = await pool.query(
      `SELECT d.*, u.username as created_by_name
       FROM debts d
       LEFT JOIN users u ON d.created_by = u.id
       WHERE d.id = $1`,
      [id]
    );

    if (debtResult.rows.length === 0) {
      return res.status(404).json({ error: 'Debt not found' });
    }

    const paymentsResult = await pool.query(
      `SELECT dp.*, u.username as created_by_name
       FROM debt_payments dp
       LEFT JOIN users u ON dp.created_by = u.id
       WHERE dp.debt_id = $1
       ORDER BY dp.payment_date DESC`,
      [id]
    );

    res.json({
      status: 'success',
      debt: debtResult.rows[0],
      payments: paymentsResult.rows
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/debts', requireAuth, async (req, res) => {
  try {
    const { debtor_name, debtor_phone, debtor_email, debt_type, amount, due_date,
            description, payment_schedule, notes } = req.body;

    if (!debtor_name || !debt_type || !amount) {
      return res.status(400).json({ error: 'Debtor name, debt type, and amount are required' });
    }

    const result = await pool.query(
      `INSERT INTO debts (debtor_name, debtor_phone, debtor_email, debt_type, amount, due_date,
       description, payment_schedule, notes, created_by)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
       RETURNING *`,
      [debtor_name, debtor_phone || null, debtor_email || null, debt_type, amount,
       due_date || null, description || null, payment_schedule || null, notes || null,
       req.session.userId]
    );

    logActivity(req.session.userId, 'create', 'debts', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/debts/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { debtor_name, debtor_phone, debtor_email, debt_type, amount, due_date,
            description, payment_schedule, notes, status } = req.body;

    const result = await pool.query(
      `UPDATE debts
       SET debtor_name = $1, debtor_phone = $2, debtor_email = $3, debt_type = $4,
           amount = $5, due_date = $6, description = $7, payment_schedule = $8,
           notes = $9, status = $10, updated_at = NOW()
       WHERE id = $11
       RETURNING *`,
      [debtor_name, debtor_phone || null, debtor_email || null, debt_type, amount,
       due_date || null, description || null, payment_schedule || null, notes || null,
       status || 'active', id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Debt not found' });
    }

    logActivity(req.session.userId, 'update', 'debts', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/debts/:id/payment', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { payment_amount, payment_method, notes } = req.body;

    if (!payment_amount || payment_amount <= 0) {
      return res.status(400).json({ error: 'Payment amount must be greater than zero' });
    }

    const debt = await pool.query('SELECT * FROM debts WHERE id = $1', [id]);
    if (debt.rows.length === 0) {
      return res.status(404).json({ error: 'Debt not found' });
    }

    const current = debt.rows[0];
    const newPaidAmount = Math.min(current.paid_amount + payment_amount, current.amount);
    const status = newPaidAmount >= current.amount ? 'paid' : 'active';

    await pool.query(
      `UPDATE debts SET paid_amount = $1, status = $2, updated_at = NOW() WHERE id = $3`,
      [newPaidAmount, status, id]
    );

    await pool.query(
      `INSERT INTO debt_payments (debt_id, payment_amount, payment_date, payment_method, notes, created_by)
       VALUES ($1, $2, $3, $4, $5, $6)`,
      [id, payment_amount, new Date().toISOString().split('T')[0], payment_method || 'cash',
       notes || null, req.session.userId]
    );

    logActivity(req.session.userId, 'add_payment', 'debts', id, req);

    res.json({ status: 'success', paid_amount: newPaidAmount, status });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/debts/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM debts WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'debts', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// EARNINGS
// ============================================
app.get('/api/earnings', requireAuth, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT e.*, u.username as created_by_name
      FROM earnings e
      LEFT JOIN users u ON e.created_by = u.id
      ORDER BY e.earning_date DESC, e.created_at DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/earnings/stats', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT
        COUNT(*) as total_earnings,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(AVG(amount), 0) as average_amount,
        COUNT(CASE WHEN status = 'received' THEN 1 END) as received_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
      FROM earnings
      WHERE earning_date >= CURRENT_DATE - INTERVAL '${interval}'
    `);
    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/earnings/total-by-source', requireAuth, async (req, res) => {
  try {
    const { period } = req.query;
    let interval = '1 month';
    if (period === 'week') interval = '7 days';
    if (period === 'year') interval = '1 year';

    const result = await pool.query(`
      SELECT source, COUNT(*) as count, COALESCE(SUM(amount), 0) as total
      FROM earnings
      WHERE earning_date >= CURRENT_DATE - INTERVAL '${interval}'
      GROUP BY source
      ORDER BY total DESC
    `);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/earnings', requireAuth, async (req, res) => {
  try {
    const { source, description, amount, earning_date, reference, payment_method, status, notes } = req.body;

    if (!source || !amount) {
      return res.status(400).json({ error: 'Source and amount are required' });
    }

    const result = await pool.query(
      `INSERT INTO earnings (source, description, amount, earning_date, reference, payment_method, status, notes, created_by)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
       RETURNING *`,
      [source, description || null, amount, earning_date || new Date().toISOString().split('T')[0],
       reference || null, payment_method || 'cash', status || 'received', notes || null,
       req.session.userId]
    );

    logActivity(req.session.userId, 'create', 'earnings', result.rows[0].id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.put('/api/earnings/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const { source, description, amount, earning_date, reference, payment_method, status, notes } = req.body;

    const result = await pool.query(
      `UPDATE earnings
       SET source = $1, description = $2, amount = $3, earning_date = $4,
           reference = $5, payment_method = $6, status = $7, notes = $8, updated_at = NOW()
       WHERE id = $9
       RETURNING *`,
      [source, description || null, amount, earning_date, reference || null,
       payment_method || 'cash', status || 'received', notes || null, id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Earning not found' });
    }

    logActivity(req.session.userId, 'update', 'earnings', id, req);

    res.json({ status: 'success', data: result.rows[0] });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/earnings/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    await pool.query('DELETE FROM earnings WHERE id = $1', [id]);
    logActivity(req.session.userId, 'delete', 'earnings', id, req);
    res.json({ status: 'success' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// FARMING CALCULATOR
// ============================================
app.get('/api/calculations', (req, res) => {
  res.json({
    calculations: {
      feed_conversion: {
        name: 'Feed Conversion Ratio',
        description: 'Calculate feed efficiency (feed intake / weight gain)',
        inputs: { feed_intake: 'Feed Intake (kg)', weight_gain: 'Weight Gain (kg)' },
        formula: 'feed_intake / weight_gain',
        unit: 'ratio'
      },
      mortality_rate: {
        name: 'Mortality Rate',
        description: 'Calculate mortality percentage',
        inputs: { deaths: 'Number of Deaths', total: 'Total Population' },
        formula: '(deaths / total) * 100',
        unit: '%'
      },
      profit_margin: {
        name: 'Profit Margin',
        description: 'Calculate profit margin percentage',
        inputs: { revenue: 'Total Revenue ($)', costs: 'Total Costs ($)' },
        formula: '((revenue - costs) / revenue) * 100',
        unit: '%'
      },
      break_even: {
        name: 'Break Even Point',
        description: 'Calculate break even price per unit',
        inputs: { total_cost: 'Total Cost ($)', units: 'Number of Units' },
        formula: 'total_cost / units',
        unit: '$/unit'
      },
      roi: {
        name: 'Return on Investment',
        description: 'Calculate ROI percentage',
        inputs: { gain: 'Gain from Investment ($)', cost: 'Cost of Investment ($)' },
        formula: '((gain - cost) / cost) * 100',
        unit: '%'
      },
      egg_production: {
        name: 'Egg Production Rate',
        description: 'Calculate daily egg production per hen',
        inputs: { total_eggs: 'Total Eggs', hens: 'Number of Hens', days: 'Number of Days' },
        formula: 'total_eggs / (hens * days)',
        unit: 'eggs/hen/day'
      },
      stocking_density: {
        name: 'Stocking Density',
        description: 'Calculate birds per square meter',
        inputs: { birds: 'Number of Birds', area: 'Area (sq meters)' },
        formula: 'birds / area',
        unit: 'birds/m²'
      },
      feed_cost_per_bird: {
        name: 'Feed Cost per Bird',
        description: 'Calculate feed cost per bird per day',
        inputs: { feed_cost: 'Feed Cost ($)', feed_amount: 'Feed Amount (kg)', birds: 'Number of Birds', days: 'Number of Days' },
        formula: '(feed_cost / feed_amount) * (days / birds)',
        unit: '$/bird/day'
      }
    }
  });
});

app.post('/api/calculations', (req, res) => {
  try {
    const { type, inputs } = req.body;

    const calculations = {
      feed_conversion: { formula: (i) => i.feed_intake / i.weight_gain, unit: 'ratio' },
      mortality_rate: { formula: (i) => (i.deaths / i.total) * 100, unit: '%' },
      profit_margin: { formula: (i) => ((i.revenue - i.costs) / i.revenue) * 100, unit: '%' },
      break_even: { formula: (i) => i.total_cost / i.units, unit: '$/unit' },
      roi: { formula: (i) => ((i.gain - i.cost) / i.cost) * 100, unit: '%' },
      egg_production: { formula: (i) => i.total_eggs / (i.hens * i.days), unit: 'eggs/hen/day' },
      stocking_density: { formula: (i) => i.birds / i.area, unit: 'birds/m²' },
      feed_cost_per_bird: { formula: (i) => (i.feed_cost / i.feed_amount) * (i.days / i.birds), unit: '$/bird/day' }
    };

    if (!calculations[type]) {
      return res.status(400).json({ error: 'Unknown calculation type' });
    }

    const calc = calculations[type];

    // Validate inputs
    for (const key of Object.keys(calc.formula().toString().match(/[a-z_]+/g) || [])) {
      if (inputs[key] === undefined || isNaN(inputs[key]) || inputs[key] <= 0) {
        return res.status(400).json({ error: `Invalid or missing value for ${key}` });
      }
    }

    try {
      const fn = new Function(...Object.keys(inputs), `return ${calc.formula.toString()}`);

      // Safe validation: only allow math operations
      const expression = calc.formula.toString();
      if (!/^[a-z0-9+\-*/()._\s]+$/i.test(expression)) {
        return res.status(400).json({ error: 'Invalid formula' });
      }

      const result = fn(...Object.values(inputs));

      res.json({
        type,
        name: calculations[type].name || type,
        result: Math.round(result * 10000) / 10000,
        unit: calc.unit,
        inputs,
        formula: calc.formula.toString()
      });
    } catch (err) {
      res.status(400).json({ error: 'Calculation error: ' + err.message });
    }
  } catch (err) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// ============================================
// EXPORT / IMPORT
// ============================================
app.post('/api/export', requireAuth, async (req, res) => {
  try {
    const tables = ['users', 'farmers', 'chicken_groups', 'chicken_batches', 'chickens',
                    'items', 'inventory', 'sales', 'expenses', 'invoices', 'debts',
                    'earnings', 'health_records', 'audit_logs', 'finance_logs'];

    const exportData = {};

    for (const table of tables) {
      try {
        const result = await pool.query(`SELECT * FROM ${table}`);
        exportData[table] = result.rows;
      } catch (err) {
        exportData[table] = [];
      }
    }

    res.json({ status: 'success', data: exportData });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/import', requireAuth, async (req, res) => {
  try {
    const { data } = req.body;

    if (!data || typeof data !== 'object') {
      return res.status(400).json({ error: 'No data provided' });
    }

    // Clear and re-import each table
    for (const [table, rows] of Object.entries(data)) {
      if (!Array.isArray(rows) || rows.length === 0) continue;

      // Verify table exists
      const tableCheck = await pool.query(
        `SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = $1)`,
        [table]
      );

      if (!tableCheck.rows[0].exists) continue;

      // Delete existing data
      await pool.query(`DELETE FROM ${table}`);

      // Re-import
      for (const row of rows) {
        const columns = Object.keys(row).filter(k => k !== 'id');
        if (columns.length === 0) continue;

        const values = columns.map(k => row[k]);
        const placeholders = columns.map(() => '?').join(', ');
        const columnList = columns.join(', ');

        try {
          await pool.query(
            `INSERT INTO ${table} (${columnList}) VALUES (${placeholders})`,
            values
          );
        } catch (err) {
          console.error(`Import error for ${table}:`, err.message);
        }
      }
    }

    res.json({ status: 'success', message: 'Data imported successfully' });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// AUDIT LOGS
// ============================================
app.get('/api/audit-logs', requireRole('admin'), async (req, res) => {
  try {
    const { limit, userId } = req.query;
    const lim = parseInt(limit) || 100;

    let query = `
      SELECT al.*, u.username
      FROM audit_logs al
      LEFT JOIN users u ON al.user_id = u.id
    `;

    const params = [];

    if (userId) {
      query += ' WHERE al.user_id = $1';
      params.push(userId);
    }

    query += ` ORDER BY al.created_at DESC LIMIT $${params.length + 1}`;
    params.push(lim);

    const result = await pool.query(query, params);
    res.json({ status: 'success', data: result.rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ============================================
// CATCH-ALL FOR FRONTEND ROUTING
// ============================================
app.get('*', (req, res) => {
  // Serve index.html for any non-API route
  if (!req.path.startsWith('/api/')) {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
  }
});

// ============================================
// ERROR HANDLING
// ============================================
app.use((err, req, res, next) => {
  console.error('Unhandled error:', err);
  res.status(500).json({ error: 'Internal server error' });
});

// ============================================
// START SERVER
// ============================================
app.listen(PORT, () => {
  console.log(`
╔══════════════════════════════════════════╗
║  KUKU Farm Management System v2.0.0      ║
║  Server running on http://localhost:${PORT} ║
╚══════════════════════════════════════════╝
  `);
  console.log('Press Ctrl+C to stop the server.');
});
