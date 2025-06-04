<?php

declare(strict_types=1);

namespace App\Service\Svg;

use App\Entity\Layer;

/**
 * Builder for SVG transform attributes from layer transform data
 */
class SvgTransformBuilder
{
    public function buildTransformAttribute(Layer $layer): string
    {
        $transformParts = [];
        
        // Get transform properties
        $x = $layer->getX() ?? 0;
        $y = $layer->getY() ?? 0;
        $rotation = $layer->getRotation() ?? 0;
        $scaleX = $layer->getScaleX() ?? 1;
        $scaleY = $layer->getScaleY() ?? 1;
        $width = $layer->getWidth() ?? 0;
        $height = $layer->getHeight() ?? 0;
        
        // Apply translation
        if ($x !== 0 || $y !== 0) {
            $transformParts[] = "translate({$x}, {$y})";
        }
        
        // Apply rotation around center of element
        if ($rotation !== 0) {
            $centerX = $width / 2;
            $centerY = $height / 2;
            $transformParts[] = "rotate({$rotation}, {$centerX}, {$centerY})";
        }
        
        // Apply scale
        if ($scaleX !== 1 || $scaleY !== 1) {
            $transformParts[] = "scale({$scaleX}, {$scaleY})";
        }
        
        return implode(' ', $transformParts);
    }

    public function buildMatrix(Layer $layer): array
    {
        $x = $layer->getX() ?? 0;
        $y = $layer->getY() ?? 0;
        $rotation = $layer->getRotation() ?? 0;
        $scaleX = $layer->getScaleX() ?? 1;
        $scaleY = $layer->getScaleY() ?? 1;
        $width = $layer->getWidth() ?? 0;
        $height = $layer->getHeight() ?? 0;
        
        // Convert rotation to radians
        $radians = deg2rad($rotation);
        $cos = cos($radians);
        $sin = sin($radians);
        
        // Calculate center point for rotation
        $centerX = $width / 2;
        $centerY = $height / 2;
        
        // Build transformation matrix
        // Order: scale, rotate around center, translate
        $a = $scaleX * $cos;
        $b = $scaleX * $sin;
        $c = -$scaleY * $sin;
        $d = $scaleY * $cos;
        $e = $x + $centerX * (1 - $cos * $scaleX) + $centerY * $sin * $scaleY;
        $f = $y + $centerY * (1 - $cos * $scaleY) - $centerX * $sin * $scaleX;
        
        return [$a, $b, $c, $d, $e, $f];
    }

    public function buildMatrixString(Layer $layer): string
    {
        $matrix = $this->buildMatrix($layer);
        return sprintf('matrix(%g %g %g %g %g %g)', ...$matrix);
    }

    public function buildViewBox(int $x, int $y, int $width, int $height): string
    {
        return "{$x} {$y} {$width} {$height}";
    }

    public function normalizeAngle(float $angle): float
    {
        // Normalize angle to 0-360 range
        while ($angle < 0) {
            $angle += 360;
        }
        while ($angle >= 360) {
            $angle -= 360;
        }
        return $angle;
    }

    public function calculateBoundingBox(Layer $layer): array
    {
        $x = $layer->getX() ?? 0;
        $y = $layer->getY() ?? 0;
        $width = $layer->getWidth() ?? 0;
        $height = $layer->getHeight() ?? 0;
        $rotation = $layer->getRotation() ?? 0;
        $scaleX = $layer->getScaleX() ?? 1;
        $scaleY = $layer->getScaleY() ?? 1;
        
        // Apply scaling
        $scaledWidth = $width * abs($scaleX);
        $scaledHeight = $height * abs($scaleY);
        
        if ($rotation === 0) {
            return [
                'x' => $x,
                'y' => $y,
                'width' => $scaledWidth,
                'height' => $scaledHeight
            ];
        }
        
        // Calculate rotated bounding box
        $radians = deg2rad($rotation);
        $cos = abs(cos($radians));
        $sin = abs(sin($radians));
        
        $rotatedWidth = $scaledWidth * $cos + $scaledHeight * $sin;
        $rotatedHeight = $scaledWidth * $sin + $scaledHeight * $cos;
        
        return [
            'x' => $x - ($rotatedWidth - $scaledWidth) / 2,
            'y' => $y - ($rotatedHeight - $scaledHeight) / 2,
            'width' => $rotatedWidth,
            'height' => $rotatedHeight
        ];
    }

    public function applyOpacity(Layer $layer): ?string
    {
        $opacity = $layer->getOpacity();
        if ($opacity !== null && $opacity < 1.0) {
            return (string)$opacity;
        }
        return null;
    }

    public function buildClipPath(int $width, int $height): string
    {
        return "M 0,0 L {$width},0 L {$width},{$height} L 0,{$height} Z";
    }

    public function convertPointsToPath(array $points): string
    {
        if (empty($points)) {
            return '';
        }
        
        $pathData = [];
        foreach ($points as $index => $point) {
            $command = $index === 0 ? 'M' : 'L';
            $pathData[] = "{$command} {$point['x']},{$point['y']}";
        }
        
        return implode(' ', $pathData);
    }

    public function createArcPath(float $cx, float $cy, float $rx, float $ry, float $startAngle, float $endAngle): string
    {
        $startAngleRad = deg2rad($startAngle);
        $endAngleRad = deg2rad($endAngle);
        
        $x1 = $cx + $rx * cos($startAngleRad);
        $y1 = $cy + $ry * sin($startAngleRad);
        $x2 = $cx + $rx * cos($endAngleRad);
        $y2 = $cy + $ry * sin($endAngleRad);
        
        $largeArcFlag = abs($endAngle - $startAngle) > 180 ? 1 : 0;
        $sweepFlag = $endAngle > $startAngle ? 1 : 0;
        
        return "M {$cx},{$cy} L {$x1},{$y1} A {$rx},{$ry} 0 {$largeArcFlag},{$sweepFlag} {$x2},{$y2} Z";
    }
}
