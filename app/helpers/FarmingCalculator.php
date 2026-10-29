<?php
class FarmingCalculator {
    private $calculations = [];
    
    public function __construct() {
        $this->calculations = [
            'feed_conversion' => [
                'name' => 'Feed Conversion Ratio',
                'description' => 'Calculate feed efficiency (feed intake / weight gain)',
                'inputs' => ['feed_intake' => 'Feed Intake (kg)', 'weight_gain' => 'Weight Gain (kg)'],
                'formula' => 'feed_intake / weight_gain',
                'unit' => 'ratio'
            ],
            'mortality_rate' => [
                'name' => 'Mortality Rate',
                'description' => 'Calculate mortality percentage',
                'inputs' => ['deaths' => 'Number of Deaths', 'total' => 'Total Population'],
                'formula' => '(deaths / total) * 100',
                'unit' => '%'
            ],
            'profit_margin' => [
                'name' => 'Profit Margin',
                'description' => 'Calculate profit margin percentage',
                'inputs' => ['revenue' => 'Total Revenue ($)', 'costs' => 'Total Costs ($)'],
                'formula' => '((revenue - costs) / revenue) * 100',
                'unit' => '%'
            ],
            'break_even' => [
                'name' => 'Break Even Point',
                'description' => 'Calculate break even price per unit',
                'inputs' => ['total_cost' => 'Total Cost ($)', 'units' => 'Number of Units'],
                'formula' => 'total_cost / units',
                'unit' => '$/unit'
            ],
            'roi' => [
                'name' => 'Return on Investment',
                'description' => 'Calculate ROI percentage',
                'inputs' => ['gain' => 'Gain from Investment ($)', 'cost' => 'Cost of Investment ($)'],
                'formula' => '((gain - cost) / cost) * 100',
                'unit' => '%'
            ],
            'egg_production' => [
                'name' => 'Egg Production Rate',
                'description' => 'Calculate daily egg production per hen',
                'inputs' => ['total_eggs' => 'Total Eggs', 'hens' => 'Number of Hens', 'days' => 'Number of Days'],
                'formula' => 'total_eggs / (hens * days)',
                'unit' => 'eggs/hen/day'
            ],
            'stocking_density' => [
                'name' => 'Stocking Density',
                'description' => 'Calculate birds per square meter',
                'inputs' => ['birds' => 'Number of Birds', 'area' => 'Area (sq meters)'],
                'formula' => 'birds / area',
                'unit' => 'birds/m²'
            ],
            'feed_cost_per_bird' => [
                'name' => 'Feed Cost per Bird',
                'description' => 'Calculate feed cost per bird per day',
                'inputs' => ['feed_cost' => 'Feed Cost ($)', 'feed_amount' => 'Feed Amount (kg)', 'birds' => 'Number of Birds', 'days' => 'Number of Days'],
                'formula' => '(feed_cost / feed_amount) / (birds / days)',
                'unit' => '$/bird/day'
            ]
        ];
        
        // Initialize history if not exists
        if (!isset($_SESSION['calc_history'])) {
            $_SESSION['calc_history'] = [];
        }
    }
    
    public function getCalculations() {
        return $this->calculations;
    }
    
    public function calculate($type, $inputs) {
        if (!isset($this->calculations[$type])) {
            return ['error' => 'Unknown calculation type'];
        }
        
        $calc = $this->calculations[$type];
        
        // Validate inputs
        foreach ($calc['inputs'] as $key => $label) {
            if (!isset($inputs[$key]) || !is_numeric($inputs[$key]) || $inputs[$key] <= 0) {
                return ['error' => "Invalid or missing value for $label"];
            }
        }
        
        try {
            // Safe evaluation of formula
            $result = $this->evaluateFormula($calc['formula'], $inputs);
            
            return [
                'type' => $type,
                'name' => $calc['name'],
                'result' => round($result, 4),
                'unit' => $calc['unit'],
                'inputs' => $inputs,
                'formula' => $calc['formula']
            ];
        } catch (Exception $e) {
            return ['error' => 'Calculation error: ' . $e->getMessage()];
        }
    }
    
    private function evaluateFormula($formula, $variables) {
        // Replace variables with values
        $expression = $formula;
        foreach ($variables as $key => $value) {
            $expression = str_replace($key, $value, $expression);
        }
        
        // Only allow mathematical operations and numbers
        if (!preg_match('/^[0-9+\-*\/().\s]+$/', $expression)) {
            throw new Exception('Invalid formula expression');
        }
        
        // Evaluate the expression safely
        return eval("return $expression;");
    }
    
    public function saveToHistory($type, $inputs, $result) {
        $historyEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'name' => $this->calculations[$type]['name'],
            'inputs' => $inputs,
            'result' => $result
        ];
        
        $_SESSION['calc_history'][] = $historyEntry;
        
        // Keep only last 50 calculations
        if (count($_SESSION['calc_history']) > 50) {
            $_SESSION['calc_history'] = array_slice($_SESSION['calc_history'], -50);
        }
    }
    
    public function getHistory() {
        return $_SESSION['calc_history'] ?? [];
    }
    
    public function clearHistory() {
        $_SESSION['calc_history'] = [];
    }
}
?>