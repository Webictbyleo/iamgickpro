<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;
use DOMDocument;
use DOMElement;

final class AudioLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'audio';
    }

    public function getSupportedTypes(): array
    {
        return ['audio'];
    }

    public function getPriority(): int
    {
        return 30;
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        // Create group to contain all audio elements
        $group = $builder->createGroup();
        $document = $group->ownerDocument;
        
        // Create audio placeholder rectangle
        $rect = $builder->createElement('rect', $document);
        $rect->setAttribute('width', (string)($layer->getWidth() ?? 100));
        $rect->setAttribute('height', (string)($layer->getHeight() ?? 50));
        $rect->setAttribute('fill', '#065f46');
        $rect->setAttribute('stroke', '#047857');
        $rect->setAttribute('stroke-width', '2');
        $rect->setAttribute('rx', '12');
        
        $group->appendChild($rect);
        
        // Add waveform visualization
        $waveform = $this->createWaveform($builder, $layer, $document);
        $group->appendChild($waveform);
        
        // Add audio icon
        $audioIcon = $this->createAudioIcon($builder, $layer, $document);
        $group->appendChild($audioIcon);
        
        // Add audio label
        $label = $this->createAudioLabel($builder, $layer, $document);
        $group->appendChild($label);
        
        return $group;
    }

    private function createWaveform(SvgDocumentBuilder $builder, Layer $layer, DOMDocument $document): DOMElement
    {
        $waveformGroup = $builder->createElement('g', $document);
        
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 50;
        $centerY = $height / 2;
        $barWidth = 3;
        $spacing = 2;
        $numBars = (int)floor(($width - 40) / ($barWidth + $spacing));
        $startX = 20;
        
        for ($i = 0; $i < $numBars; $i++) {
            $x = $startX + $i * ($barWidth + $spacing);
            $barHeight = rand(5, (int)($height * 0.4));
            
            $bar = $builder->createElement('rect', $document);
            $bar->setAttribute('x', (string)$x);
            $bar->setAttribute('y', (string)($centerY - $barHeight / 2));
            $bar->setAttribute('width', (string)$barWidth);
            $bar->setAttribute('height', (string)$barHeight);
            $bar->setAttribute('fill', '#10b981');
            $bar->setAttribute('rx', '1');
            
            $waveformGroup->appendChild($bar);
        }
        
        return $waveformGroup;
    }

    private function createAudioIcon(SvgDocumentBuilder $builder, Layer $layer, DOMDocument $document): DOMElement
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 50;
        $iconSize = min($width, $height) * 0.15;
        $iconX = 10;
        $iconY = 10;
        
        $circle = $builder->createElement('circle', $document);
        $circle->setAttribute('cx', (string)($iconX + $iconSize / 2));
        $circle->setAttribute('cy', (string)($iconY + $iconSize / 2));
        $circle->setAttribute('r', (string)($iconSize / 2));
        $circle->setAttribute('fill', '#ffffff');
        $circle->setAttribute('fill-opacity', '0.9');
        
        return $circle;
    }

    private function createAudioLabel(SvgDocumentBuilder $builder, Layer $layer, DOMDocument $document): DOMElement
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 50;
        
        $text = $builder->createElement('text', $document);
        $text->setAttribute('x', (string)($width / 2));
        $text->setAttribute('y', (string)($height - 10));
        $text->setAttribute('text-anchor', 'middle');
        $text->setAttribute('font-family', 'Arial, sans-serif');
        $text->setAttribute('font-size', '12');
        $text->setAttribute('fill', '#6ee7b7');
        $text->appendChild($builder->createText('Audio Layer', $text->ownerDocument));
        
        return $text;
    }
}
