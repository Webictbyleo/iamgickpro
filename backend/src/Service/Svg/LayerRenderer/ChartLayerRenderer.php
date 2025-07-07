<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMElement;
use DOMXPath;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Renderer for chart layers - renders charts as SVG elements
 * Supports various chart types: bar, line, pie, doughnut, area, scatter, bubble
 */
#[AutoconfigureTag('app.svg.layer_renderer')]
class ChartLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'chart';
    }

    public function getSupportedTypes(): array
    {
        return ['chart'];
    }

    public function getPriority(): int
    {
        return 10;
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        $properties = $layer->getProperties() ?? [];
        $transform = $layer->getTransform() ?? [];
        
        $width = $transform['width'] ?? 400;
        $height = $transform['height'] ?? 300;
        
        // Chart properties with defaults
        $chartType = $properties['chartType'] ?? 'bar';
        $data = $properties['data'] ?? $this->getDefaultData();
        $options = $properties['options'] ?? $this->getDefaultOptions();
        $theme = $properties['theme'] ?? $this->getDefaultTheme();
        
        // Create chart group
        $chartGroup = $builder->createElement('g');
        $chartGroup->setAttribute('class', 'chart-layer');
        
        // Add background if specified
        if ($theme['background'] !== 'transparent') {
            $background = $builder->createElement('rect');
            $background->setAttribute('x', '0');
            $background->setAttribute('y', '0');
            $background->setAttribute('width', (string)$width);
            $background->setAttribute('height', (string)$height);
            $background->setAttribute('fill', $theme['background']);
            $chartGroup->appendChild($background);
        }
        
        // Calculate chart area (excluding margins for legend, title, etc.)
        $margins = $this->calculateMargins($options, $width, $height);
        $chartArea = [
            'x' => $margins['left'],
            'y' => $margins['top'],
            'width' => $width - $margins['left'] - $margins['right'],
            'height' => $height - $margins['top'] - $margins['bottom']
        ];
        
        // Render title if enabled
        if ($options['plugins']['title']['display'] ?? false) {
            $this->renderTitle($builder, $chartGroup, $options['plugins']['title'], $theme, $width);
        }
        
        // Render chart based on type
        switch ($chartType) {
            case 'bar':
                $this->renderBarChart($builder, $chartGroup, $data, $chartArea, $theme, $options);
                break;
            case 'line':
                $this->renderLineChart($builder, $chartGroup, $data, $chartArea, $theme, $options);
                break;
            case 'pie':
            case 'doughnut':
                $this->renderPieChart($builder, $chartGroup, $data, $chartArea, $theme, $options, $chartType === 'doughnut');
                break;
            case 'area':
                $this->renderAreaChart($builder, $chartGroup, $data, $chartArea, $theme, $options);
                break;
            case 'scatter':
            case 'bubble':
                $this->renderScatterChart($builder, $chartGroup, $data, $chartArea, $theme, $options);
                break;
        }
        
        // Render axes for charts that need them
        if (in_array($chartType, ['bar', 'line', 'area', 'scatter', 'bubble'])) {
            $this->renderAxes($builder, $chartGroup, $data, $chartArea, $theme, $options);
        }
        
        // Render legend if enabled
        if ($options['plugins']['legend']['display'] ?? false) {
            $this->renderLegend($builder, $chartGroup, $data, $options['plugins']['legend'], $theme, $width, $height);
        }
        
        return $chartGroup;
    }
    
    private function getDefaultData(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            'datasets' => [
                [
                    'label' => 'Sample Data',
                    'data' => [10, 20, 15, 25, 30],
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#1E40AF',
                    'borderWidth' => 2
                ]
            ]
        ];
    }
    
    private function getDefaultOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top'
                ],
                'title' => [
                    'display' => false,
                    'text' => 'Chart'
                ],
                'tooltip' => [
                    'enabled' => true
                ]
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'grid' => [
                        'display' => true,
                        'color' => '#E5E7EB'
                    ]
                ],
                'y' => [
                    'display' => true,
                    'grid' => [
                        'display' => true,
                        'color' => '#E5E7EB'
                    ]
                ]
            ]
        ];
    }
    
    private function getDefaultTheme(): array
    {
        return [
            'primary' => '#3B82F6',
            'secondary' => '#8B5CF6',
            'background' => '#FFFFFF',
            'text' => '#1F2937',
            'grid' => '#E5E7EB',
            'accent' => ['#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899']
        ];
    }
    
    private function calculateMargins(array $options, float $width, float $height): array
    {
        $margins = [
            'top' => 20,
            'right' => 20,
            'bottom' => 40,
            'left' => 60
        ];
        
        if ($options['plugins']['title']['display'] ?? false) {
            $margins['top'] += 40;
        }
        
        if ($options['plugins']['legend']['display'] ?? false) {
            $position = $options['plugins']['legend']['position'] ?? 'top';
            switch ($position) {
                case 'top':
                    $margins['top'] += 40;
                    break;
                case 'bottom':
                    $margins['bottom'] += 40;
                    break;
                case 'left':
                    $margins['left'] += 80;
                    break;
                case 'right':
                    $margins['right'] += 80;
                    break;
            }
        }
        
        return $margins;
    }
    
    private function renderTitle(SvgDocumentBuilder $builder, DOMElement $parent, array $title, array $theme, float $width): void
    {
        $text = $builder->createElement('text');
        $text->setAttribute('x', (string)($width / 2));
        $text->setAttribute('y', '20');
        $text->setAttribute('text-anchor', 'middle');
        $text->setAttribute('font-size', (string)($title['font']['size'] ?? 16));
        $text->setAttribute('font-weight', $title['font']['weight'] ?? 'bold');
        $text->setAttribute('fill', $theme['text']);
        $text->textContent = $title['text'] ?? 'Chart';
        
        $parent->appendChild($text);
    }
    
    private function renderBarChart(SvgDocumentBuilder $builder, DOMElement $parent, array $data, array $chartArea, array $theme, array $options): void
    {
        $labels = $data['labels'];
        $datasets = $data['datasets'];
        
        if (empty($labels) || empty($datasets)) {
            return;
        }
        
        $barWidth = $chartArea['width'] / (count($labels) * count($datasets) + count($labels) - 1);
        $maxValue = max(array_merge(...array_column($datasets, 'data')));
        $scale = $chartArea['height'] / max($maxValue, 1);
        
        foreach ($datasets as $datasetIndex => $dataset) {
            foreach ($dataset['data'] as $index => $value) {
                $x = $chartArea['x'] + $index * ($barWidth * count($datasets) + $barWidth * 0.2) + $datasetIndex * $barWidth;
                $barHeight = $value * $scale;
                $y = $chartArea['y'] + $chartArea['height'] - $barHeight;
                
                $rect = $builder->createElement('rect');
                $rect->setAttribute('x', (string)$x);
                $rect->setAttribute('y', (string)$y);
                $rect->setAttribute('width', (string)($barWidth * 0.8));
                $rect->setAttribute('height', (string)$barHeight);
                $rect->setAttribute('fill', $this->getColor($dataset['backgroundColor'] ?? null, $datasetIndex, $theme));
                
                if (isset($dataset['borderColor']) && ($dataset['borderWidth'] ?? 0) > 0) {
                    $rect->setAttribute('stroke', $this->getColor($dataset['borderColor'], $datasetIndex, $theme));
                    $rect->setAttribute('stroke-width', (string)($dataset['borderWidth'] ?? 1));
                }
                
                $parent->appendChild($rect);
            }
        }
    }
    
    private function renderLineChart(SvgDocumentBuilder $builder, DOMElement $parent, array $data, array $chartArea, array $theme, array $options): void
    {
        $labels = $data['labels'];
        $datasets = $data['datasets'];
        
        if (empty($labels) || empty($datasets)) {
            return;
        }
        
        $pointSpacing = $chartArea['width'] / (count($labels) - 1);
        $maxValue = max(array_merge(...array_column($datasets, 'data')));
        $scale = $chartArea['height'] / max($maxValue, 1);
        
        foreach ($datasets as $datasetIndex => $dataset) {
            $points = [];
            
            foreach ($dataset['data'] as $index => $value) {
                $x = $chartArea['x'] + $index * $pointSpacing;
                $y = $chartArea['y'] + $chartArea['height'] - ($value * $scale);
                $points[] = $x . ',' . $y;
            }
            
            // Draw line
            $polyline = $builder->createElement('polyline');
            $polyline->setAttribute('points', implode(' ', $points));
            $polyline->setAttribute('fill', 'none');
            $polyline->setAttribute('stroke', $this->getColor($dataset['borderColor'] ?? null, $datasetIndex, $theme));
            $polyline->setAttribute('stroke-width', (string)($dataset['borderWidth'] ?? 2));
            
            $parent->appendChild($polyline);
            
            // Draw points if specified
            if (($dataset['pointRadius'] ?? 0) > 0) {
                foreach ($dataset['data'] as $index => $value) {
                    $x = $chartArea['x'] + $index * $pointSpacing;
                    $y = $chartArea['y'] + $chartArea['height'] - ($value * $scale);
                    
                    $circle = $builder->createElement('circle');
                    $circle->setAttribute('cx', (string)$x);
                    $circle->setAttribute('cy', (string)$y);
                    $circle->setAttribute('r', (string)$dataset['pointRadius']);
                    $circle->setAttribute('fill', $this->getColor($dataset['pointBackgroundColor'] ?? $dataset['backgroundColor'] ?? null, $datasetIndex, $theme));
                    
                    if (isset($dataset['pointBorderColor'])) {
                        $circle->setAttribute('stroke', $this->getColor($dataset['pointBorderColor'], $datasetIndex, $theme));
                        $circle->setAttribute('stroke-width', '1');
                    }
                    
                    $parent->appendChild($circle);
                }
            }
        }
    }
    
    private function renderPieChart(SvgDocumentBuilder $builder, DOMElement $parent, array $data, array $chartArea, array $theme, array $options, bool $isDoughnut = false): void
    {
        $dataset = $data['datasets'][0] ?? [];
        if (empty($dataset['data'])) {
            return;
        }
        
        $centerX = $chartArea['x'] + $chartArea['width'] / 2;
        $centerY = $chartArea['y'] + $chartArea['height'] / 2;
        $radius = min($chartArea['width'], $chartArea['height']) / 2 - 10;
        $innerRadius = $isDoughnut ? $radius * 0.4 : 0;
        
        $total = array_sum($dataset['data']);
        $currentAngle = -90; // Start at top
        
        foreach ($dataset['data'] as $index => $value) {
            $sliceAngle = ($value / $total) * 360;
            $endAngle = $currentAngle + $sliceAngle;
            
            $path = $this->createPieSlicePath($centerX, $centerY, $radius, $innerRadius, $currentAngle, $endAngle);
            
            $pathElement = $builder->createElement('path');
            $pathElement->setAttribute('d', $path);
            $pathElement->setAttribute('fill', $this->getColor($dataset['backgroundColor'] ?? null, $index, $theme));
            
            if (isset($dataset['borderColor']) && ($dataset['borderWidth'] ?? 0) > 0) {
                $pathElement->setAttribute('stroke', $this->getColor($dataset['borderColor'], $index, $theme));
                $pathElement->setAttribute('stroke-width', (string)($dataset['borderWidth'] ?? 1));
            }
            
            $parent->appendChild($pathElement);
            $currentAngle = $endAngle;
        }
    }
    
    private function renderAreaChart(SvgDocumentBuilder $builder, DOMElement $parent, array $data, array $chartArea, array $theme, array $options): void
    {
        $labels = $data['labels'];
        $datasets = $data['datasets'];
        
        if (empty($labels) || empty($datasets)) {
            return;
        }
        
        $pointSpacing = $chartArea['width'] / (count($labels) - 1);
        $maxValue = max(array_merge(...array_column($datasets, 'data')));
        $scale = $chartArea['height'] / max($maxValue, 1);
        
        foreach ($datasets as $datasetIndex => $dataset) {
            $points = [];
            
            // Start from bottom left
            $points[] = $chartArea['x'] . ',' . ($chartArea['y'] + $chartArea['height']);
            
            // Add data points
            foreach ($dataset['data'] as $index => $value) {
                $x = $chartArea['x'] + $index * $pointSpacing;
                $y = $chartArea['y'] + $chartArea['height'] - ($value * $scale);
                $points[] = $x . ',' . $y;
            }
            
            // End at bottom right
            $points[] = ($chartArea['x'] + $chartArea['width']) . ',' . ($chartArea['y'] + $chartArea['height']);
            
            $polygon = $builder->createElement('polygon');
            $polygon->setAttribute('points', implode(' ', $points));
            $polygon->setAttribute('fill', $this->getColor($dataset['backgroundColor'] ?? null, $datasetIndex, $theme, 0.3));
            $polygon->setAttribute('stroke', $this->getColor($dataset['borderColor'] ?? null, $datasetIndex, $theme));
            $polygon->setAttribute('stroke-width', (string)($dataset['borderWidth'] ?? 2));
            
            $parent->appendChild($polygon);
        }
    }
    
    private function renderScatterChart(SvgDocumentBuilder $builder, DOMElement $parent, array $data, array $chartArea, array $theme, array $options): void
    {
        // For scatter charts, data should be array of {x, y} objects
        // For simplicity, treating as line chart without connecting lines
        $this->renderLineChart($builder, $parent, $data, $chartArea, $theme, $options);
    }
    
    private function renderAxes(SvgDocumentBuilder $builder, DOMElement $parent, array $data, array $chartArea, array $theme, array $options): void
    {
        // X-axis
        if ($options['scales']['x']['display'] ?? true) {
            $xAxis = $builder->createElement('line');
            $xAxis->setAttribute('x1', (string)$chartArea['x']);
            $xAxis->setAttribute('y1', (string)($chartArea['y'] + $chartArea['height']));
            $xAxis->setAttribute('x2', (string)($chartArea['x'] + $chartArea['width']));
            $xAxis->setAttribute('y2', (string)($chartArea['y'] + $chartArea['height']));
            $xAxis->setAttribute('stroke', $theme['grid']);
            $xAxis->setAttribute('stroke-width', '1');
            $parent->appendChild($xAxis);
            
            // X-axis labels
            foreach ($data['labels'] as $index => $label) {
                $x = $chartArea['x'] + ($index * $chartArea['width']) / (count($data['labels']) - 1);
                
                $text = $builder->createElement('text');
                $text->setAttribute('x', (string)$x);
                $text->setAttribute('y', (string)($chartArea['y'] + $chartArea['height'] + 15));
                $text->setAttribute('text-anchor', 'middle');
                $text->setAttribute('font-size', '12');
                $text->setAttribute('fill', $theme['text']);
                $text->textContent = (string)$label;
                
                $parent->appendChild($text);
            }
        }
        
        // Y-axis
        if ($options['scales']['y']['display'] ?? true) {
            $yAxis = $builder->createElement('line');
            $yAxis->setAttribute('x1', (string)$chartArea['x']);
            $yAxis->setAttribute('y1', (string)$chartArea['y']);
            $yAxis->setAttribute('x2', (string)$chartArea['x']);
            $yAxis->setAttribute('y2', (string)($chartArea['y'] + $chartArea['height']));
            $yAxis->setAttribute('stroke', $theme['grid']);
            $yAxis->setAttribute('stroke-width', '1');
            $parent->appendChild($yAxis);
            
            // Y-axis grid lines and labels
            $maxValue = max(array_merge(...array_column($data['datasets'], 'data')));
            $steps = 5;
            
            for ($i = 0; $i <= $steps; $i++) {
                $value = ($maxValue / $steps) * $i;
                $y = $chartArea['y'] + $chartArea['height'] - ($value / $maxValue) * $chartArea['height'];
                
                // Grid line
                if ($options['scales']['y']['grid']['display'] ?? true) {
                    $gridLine = $builder->createElement('line');
                    $gridLine->setAttribute('x1', (string)$chartArea['x']);
                    $gridLine->setAttribute('y1', (string)$y);
                    $gridLine->setAttribute('x2', (string)($chartArea['x'] + $chartArea['width']));
                    $gridLine->setAttribute('y2', (string)$y);
                    $gridLine->setAttribute('stroke', $theme['grid']);
                    $gridLine->setAttribute('stroke-width', '0.5');
                    $gridLine->setAttribute('opacity', '0.3');
                    $parent->appendChild($gridLine);
                }
                
                // Label
                $text = $builder->createElement('text');
                $text->setAttribute('x', (string)($chartArea['x'] - 10));
                $text->setAttribute('y', (string)($y + 4));
                $text->setAttribute('text-anchor', 'end');
                $text->setAttribute('font-size', '12');
                $text->setAttribute('fill', $theme['text']);
                $text->textContent = (string)round($value);
                
                $parent->appendChild($text);
            }
        }
    }
    
    private function renderLegend(SvgDocumentBuilder $builder, DOMElement $parent, array $data, array $legend, array $theme, float $width, float $height): void
    {
        $position = $legend['position'] ?? 'top';
        $itemHeight = 20;
        $itemSpacing = 10;
        
        $startX = 10;
        $startY = 10;
        
        switch ($position) {
            case 'bottom':
                $startY = $height - (count($data['datasets']) * $itemHeight + (count($data['datasets']) - 1) * $itemSpacing) - 10;
                break;
            case 'left':
                $startX = 10;
                $startY = $height / 2 - (count($data['datasets']) * $itemHeight) / 2;
                break;
            case 'right':
                $startX = $width - 100;
                $startY = $height / 2 - (count($data['datasets']) * $itemHeight) / 2;
                break;
        }
        
        foreach ($data['datasets'] as $index => $dataset) {
            $y = $startY + $index * ($itemHeight + $itemSpacing);
            
            // Legend color box
            $rect = $builder->createElement('rect');
            $rect->setAttribute('x', (string)$startX);
            $rect->setAttribute('y', (string)$y);
            $rect->setAttribute('width', '12');
            $rect->setAttribute('height', '12');
            $rect->setAttribute('fill', $this->getColor($dataset['backgroundColor'] ?? null, $index, $theme));
            $parent->appendChild($rect);
            
            // Legend text
            $text = $builder->createElement('text');
            $text->setAttribute('x', (string)($startX + 20));
            $text->setAttribute('y', (string)($y + 9));
            $text->setAttribute('font-size', '12');
            $text->setAttribute('fill', $theme['text']);
            $text->textContent = $dataset['label'] ?? 'Dataset ' . ($index + 1);
            $parent->appendChild($text);
        }
    }
    
    private function getColor(?string $color, int $index, array $theme, float $alpha = 1.0): string
    {
        if ($color) {
            if ($alpha < 1.0 && str_starts_with($color, '#')) {
                // Convert hex to rgba
                $hex = ltrim($color, '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                return "rgba({$r}, {$g}, {$b}, {$alpha})";
            }
            return $color;
        }
        
        return $theme['accent'][$index % count($theme['accent'])];
    }
    
    private function createPieSlicePath(float $centerX, float $centerY, float $radius, float $innerRadius, float $startAngle, float $endAngle): string
    {
        $startAngleRad = deg2rad($startAngle);
        $endAngleRad = deg2rad($endAngle);
        
        $x1 = $centerX + cos($startAngleRad) * $radius;
        $y1 = $centerY + sin($startAngleRad) * $radius;
        $x2 = $centerX + cos($endAngleRad) * $radius;
        $y2 = $centerY + sin($endAngleRad) * $radius;
        
        $largeArcFlag = ($endAngle - $startAngle) > 180 ? 1 : 0;
        
        if ($innerRadius > 0) {
            // Doughnut chart
            $x3 = $centerX + cos($startAngleRad) * $innerRadius;
            $y3 = $centerY + sin($startAngleRad) * $innerRadius;
            $x4 = $centerX + cos($endAngleRad) * $innerRadius;
            $y4 = $centerY + sin($endAngleRad) * $innerRadius;
            
            return "M {$x1} {$y1} A {$radius} {$radius} 0 {$largeArcFlag} 1 {$x2} {$y2} L {$x4} {$y4} A {$innerRadius} {$innerRadius} 0 {$largeArcFlag} 0 {$x3} {$y3} Z";
        } else {
            // Pie chart
            return "M {$centerX} {$centerY} L {$x1} {$y1} A {$radius} {$radius} 0 {$largeArcFlag} 1 {$x2} {$y2} Z";
        }
    }
}
