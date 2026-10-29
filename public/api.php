<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include required files
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();

// API endpoints that don't require authentication
$publicEndpoints = [
    'status' => true,
    'weather' => true
];

$endpoint = $_GET['endpoint'] ?? '';

if (!isset($publicEndpoints[$endpoint])) {
    // For protected endpoints, check for API key or session
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $sessionId = $_SERVER['HTTP_X_SESSION_ID'] ?? '';
    
    if (!$auth->isLoggedIn() && !$apiKey) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

try {
    switch ($endpoint) {
        case 'status':
            echo json_encode([
                'status' => 'success',
                'message' => 'KUKU Farm Management API is running',
                'version' => APP_VERSION,
                'timestamp' => date('Y-m-d H:i:s'),
                'features' => [
                    'farming_calculator' => true,
                    'weather_widget' => true,
                    'time_widget' => true,
                    'free_weather_api' => 'Open-Meteo'
                ]
            ]);
            break;
            
        case 'weather':
            require_once __DIR__ . '/../app/helpers/WeatherService.php';
            
            $weatherService = new WeatherService();
            
            // Get current weather using Open-Meteo (completely free!)
            $currentWeather = $weatherService->getCurrentWeather();
            
            // Get forecast
            $forecast = $weatherService->getForecast();
            
            // Get farming advice
            $advice = $currentWeather['success'] ? $weatherService->getFarmingAdvice($currentWeather) : [];
            
            echo json_encode([
                'current' => $currentWeather,
                'forecast' => $forecast,
                'advice' => $advice,
                'last_updated' => date('Y-m-d H:i:s'),
                'api_source' => 'Open-Meteo (Free API - No Key Required)'
            ]);
            break;
            
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $username = $input['username'] ?? '';
                $password = $input['password'] ?? '';
                
                if ($auth->login($username, $password)) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'user' => $auth->getCurrentUser(),
                        'session_id' => session_id()
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Invalid credentials']);
                }
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case 'dashboard':
            require_once __DIR__ . '/../app/models/Farmer.php';
            require_once __DIR__ . '/../app/models/Chicken.php';
            require_once __DIR__ . '/../app/models/Sale.php';
            require_once __DIR__ . '/../app/models/Expense.php';
            
            $farmerModel = new Farmer();
            $chickenModel = new Chicken();
            $saleModel = new Sale();
            $expenseModel = new Expense();
            
            $data = [
                'farmers' => $farmerModel->getStats(),
                'chickens' => $chickenModel->getStats(),
                'sales' => $saleModel->getStats('month'),
                'expenses' => $expenseModel->getStats('month'),
                'recent_sales' => $saleModel->getWithDetails(5),
                'recent_expenses' => $expenseModel->getWithDetails(5)
            ];
            
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        case 'calculations':
            require_once __DIR__ . '/../app/helpers/FarmingCalculator.php';
            
            $calculator = new FarmingCalculator();
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo json_encode($calculator->getCalculations());
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (isset($input['action']) && $input['action'] === 'save_history') {
                    // Save history to session
                    $_SESSION['calc_history'] = $input['history'] ?? [];
                    echo json_encode(['success' => true]);
                } else {
                    // Perform calculation
                    $type = $input['type'] ?? '';
                    $inputs = $input['inputs'] ?? [];
                    
                    $result = $calculator->calculate($type, $inputs);
                    
                    if (!isset($result['error'])) {
                        $calculator->saveToHistory($type, $inputs, $result);
                    }
                    
                    echo json_encode($result);
                }
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>