<?php
require_once __DIR__ . '/vendor/autoload.php';

$fqn = 'App\DTO\Response\UserResponseDTO';
if (class_exists($fqn)) {
    echo "✅ UserResponseDTO class exists\n";
    
    $reflection = new \ReflectionClass($fqn);
    $constructor = $reflection->getConstructor();
    
    if ($constructor) {
        echo "✅ Constructor found\n";
        foreach ($constructor->getParameters() as $param) {
            echo "  - Parameter: {$param->getName()}\n";
            echo "    Type: " . ($param->getType() ? $param->getType()->getName() : 'none') . "\n";
            
            try {
                $property = $reflection->getProperty($param->getName());
                echo "    Promoted: " . ($property->isPromoted() ? 'yes' : 'no') . "\n";
            } catch (Exception $e) {
                echo "    Promoted: no (not a property)\n";
            }
        }
    }
} else {
    echo "❌ UserResponseDTO class not found\n";
}
