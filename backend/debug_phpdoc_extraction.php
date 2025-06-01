<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use phpDocumentor\Reflection\DocBlockFactory;

class MockPropertyExtraction {
    private DocBlockFactory $docBlockFactory;
    private $contextFactory;
    
    public function __construct() {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory = new \phpDocumentor\Reflection\Types\ContextFactory();
    }
    
    private function getParameterDocComment(\ReflectionClass $class, \ReflectionParameter $parameter): ?string
    {
        $constructor = $parameter->getDeclaringFunction();
        $file = $constructor->getFileName();
        $paramName = $parameter->getName();
        
        if (!$file || !is_readable($file)) {
            return null;
        }
        
        $lines = file($file);
        $startLine = $constructor->getStartLine();
        $endLine = $constructor->getEndLine();
        
        // Get the content around the constructor, with more context
        $contextStart = max(0, $startLine - 10);
        $contextEnd = min(count($lines), $endLine + 5);
        $contextLines = array_slice($lines, $contextStart, $contextEnd - $contextStart);
        $content = implode('', $contextLines);
        
        // Look for a more specific pattern: docblock followed directly by parameter declaration
        $pattern = '/\/\*\*(?:[^*]|\*(?!\/))*\*\/\s*(?:#\[[^\]]*\]\s*)*(?:public|protected|private)?\s+[^$]*\$' . preg_quote($paramName, '/') . '/s';
        
        if (preg_match($pattern, $content, $matches)) {
            // Extract just the docblock part
            if (preg_match('/(\/\*\*(?:[^*]|\*(?!\/))*\*\/)/', $matches[0], $docMatches)) {
                return $docMatches[1];
            }
        }
        
        return null;
    }
    
    private function extractPhpDocType(\ReflectionClass $class, string $propertyName, ?\ReflectionParameter $parameter = null): ?string
    {
        try {
            $context = $this->contextFactory->createFromReflector($class);
            
            if ($parameter) {
                $docComment = $this->getParameterDocComment($class, $parameter);
                echo "Doc comment found: " . ($docComment ? 'YES' : 'NO') . "\n";
                if ($docComment) {
                    echo "Doc comment content: " . trim($docComment) . "\n";
                    
                    $docBlock = $this->docBlockFactory->create($docComment, $context);
                    
                    // Look for @var tags
                    $varTags = $docBlock->getTagsByName('var');
                    echo "@var tags found: " . count($varTags) . "\n";
                    if (!empty($varTags)) {
                        $varTag = $varTags[0];
                        if ($varTag instanceof \phpDocumentor\Reflection\DocBlock\Tags\Var_) {
                            $type = $varTag->getType();
                            if ($type) {
                                $typeString = (string) $type;
                                echo "PHPDoc type from @var: {$typeString}\n";
                                return $typeString;
                            }
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            echo "Exception in extractPhpDocType: " . $e->getMessage() . "\n";
        }
        
        return null;
    }
    
    public function testPhpDocExtraction() {
        $reflection = new ReflectionClass(\App\DTO\UpdateLayerRequestDTO::class);
        $constructor = $reflection->getConstructor();
        
        foreach ($constructor->getParameters() as $param) {
            if ($param->getName() === 'properties') {
                echo "Testing PHPDoc extraction for parameter: " . $param->getName() . "\n";
                $result = $this->extractPhpDocType($reflection, $param->getName(), $param);
                echo "PHPDoc result: " . ($result ?: 'NULL') . "\n\n";
                break;
            }
        }
    }
}

(new MockPropertyExtraction())->testPhpDocExtraction();
