<?php

class WeatherService {
    private $apiUrl = 'https://api.open-meteo.com/v1/forecast';
    private $defaultLat = -1.2921; // Nairobi
    private $defaultLon = 36.8219;
    
    public function getCurrentWeather() {
        try {
            $url = $this->apiUrl . '?latitude=' . $this->defaultLat . '&longitude=' . $this->defaultLon . '&current=temperature_2m,relativehumidity_2m,pressure_msl,weathercode,windspeed_10m';
            
            $response = file_get_contents($url);
            if ($response === false) {
                return ['success' => false, 'error' => 'Failed to fetch weather data'];
            }
            
            $data = json_decode($response, true);
            if (!$data || !isset($data['current'])) {
                return ['success' => false, 'error' => 'Invalid weather data format'];
            }
            
            $current = $data['current'];
            $weatherCode = $current['weathercode'] ?? 0;
            
            return [
                'success' => true,
                'city' => 'Current Location',
                'country' => '',
                'temperature' => round($current['temperature_2m'] ?? 0),
                'feels_like' => round($current['temperature_2m'] ?? 0),
                'humidity' => round($current['relativehumidity_2m'] ?? 0),
                'pressure' => round($current['pressure_msl'] ?? 0),
                'description' => $this->getWeatherDescription($weatherCode),
                'icon' => $weatherCode,
                'wind_speed' => round($current['windspeed_10m'] ?? 0),
                'wind_direction' => 0,
                'visibility' => 24,
                'sunrise' => 0,
                'sunset' => 0,
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getForecast() {
        try {
            $url = $this->apiUrl . '?latitude=' . $this->defaultLat . '&longitude=' . $this->defaultLon . '&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum';
            
            $response = file_get_contents($url);
            if ($response === false) {
                return ['success' => false, 'error' => 'Failed to fetch forecast data'];
            }
            
            $data = json_decode($response, true);
            if (!$data || !isset($data['daily'])) {
                return ['success' => false, 'error' => 'Invalid forecast data format'];
            }
            
            $forecast = [];
            $daily = $data['daily'];
            
            for ($i = 0; $i < min(5, count($daily['time'])); $i++) {
                $weatherCode = $daily['weathercode'][$i] ?? 0;
                
                $forecast[] = [
                    'date' => date('Y-m-d', strtotime($daily['time'][$i])),
                    'temp_min' => round($daily['temperature_2m_min'][$i] ?? 0),
                    'temp_max' => round($daily['temperature_2m_max'][$i] ?? 0),
                    'avg_humidity' => 50, // Open-Meteo doesn't provide humidity in daily forecast
                    'description' => $this->getWeatherDescription($weatherCode),
                    'icon' => $weatherCode,
                    'precipitation' => round($daily['precipitation_sum'][$i] ?? 0, 2)
                ];
            }
            
            return [
                'success' => true,
                'forecast' => $forecast
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getFarmingAdvice($weatherData) {
        if (!$weatherData['success']) {
            return ['Unable to provide farming advice due to weather data error.'];
        }
        
        $temp = $weatherData['temperature'];
        $humidity = $weatherData['humidity'];
        $advice = [];
        
        // Temperature-based advice
        if ($temp >= 25 && $temp <= 30) {
            $advice[] = 'Optimal temperature for chicken growth and egg production.';
        } elseif ($temp > 30) {
            $advice[] = 'High temperature alert! Ensure adequate ventilation and cooling.';
            $advice[] = 'Provide extra water and consider misting systems.';
        } elseif ($temp < 15) {
            $advice[] = 'Low temperature warning! Ensure proper heating for young chicks.';
            $advice[] = 'Consider additional heat sources to maintain optimal temperature.';
        }
        
        // Humidity-based advice
        if ($humidity > 70) {
            $advice[] = 'High humidity detected. Ensure good ventilation to prevent respiratory issues.';
        } elseif ($humidity < 40) {
            $advice[] = 'Low humidity levels. Consider humidifiers in enclosed spaces.';
        }
        
        // General advice
        $advice[] = 'Monitor chickens closely for heat stress signs.';
        $advice[] = 'Ensure clean, fresh water is always available.';
        
        return $advice;
    }
    
    private function getWeatherDescription($code) {
        $descriptions = [
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45 => 'Fog',
            48 => 'Fog',
            51 => 'Drizzle',
            53 => 'Drizzle',
            55 => 'Drizzle',
            56 => 'Freezing Drizzle',
            57 => 'Freezing Drizzle',
            61 => 'Slight Rain',
            63 => 'Moderate Rain',
            65 => 'Heavy Rain',
            66 => 'Light Freezing Rain',
            67 => 'Moderate Freezing Rain',
            71 => 'Slight Snow',
            73 => 'Moderate Snow',
            75 => 'Heavy Snow',
            77 => 'Snow Grains',
            80 => 'Slight Rain Showers',
            81 => 'Moderate Rain Showers',
            82 => 'Violent Rain Showers',
            85 => 'Slight Snow Showers',
            86 => 'Moderate Snow Showers'
        ];
        
        return $descriptions[$code] ?? 'Unknown';
    }
}
?>