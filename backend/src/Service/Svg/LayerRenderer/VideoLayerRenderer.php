<?php

declare(strict_types=1);

namespace App\Service\Svg\LayerRenderer;

use App\Entity\Layer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;
use DOMDocument;
use DOMElement;

final class VideoLayerRenderer extends AbstractLayerRenderer
{
    public function canRender(Layer $layer): bool
    {
        return $layer->getType() === 'video';
    }

    public function getSupportedTypes(): array
    {
        return ['video'];
    }

    public function getPriority(): int
    {
        return 40;
    }

    protected function renderLayerContent(Layer $layer, SvgDocumentBuilder $builder): DOMElement
    {
        // Create group to contain all video elements
        $group = $builder->createGroup();
        $document = $group->ownerDocument;
        
        // Create video placeholder rectangle
        $rect = $builder->createElement('rect', $document);
        $rect->setAttribute('width', (string)($layer->getWidth() ?? 100));
        $rect->setAttribute('height', (string)($layer->getHeight() ?? 100));
        $rect->setAttribute('fill', '#1f2937');
        $rect->setAttribute('stroke', '#374151');
        $rect->setAttribute('stroke-width', '2');
        $rect->setAttribute('rx', '8');
        
        $group->appendChild($rect);
        
        // Add play button icon
        $playButton = $this->createPlayButton($builder, $layer, $document);
        $group->appendChild($playButton);
        
        // Add video label
        $label = $this->createVideoLabel($builder, $layer, $document);
        $group->appendChild($label);
        
        return $group;
    }

    private function createPlayButton(SvgDocumentBuilder $builder, Layer $layer, DOMDocument $document): DOMElement
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        $centerX = $width / 2;
        $centerY = $height / 2;
        $buttonSize = min($width, $height) * 0.2;
        
        $circle = $builder->createElement('circle', $document);
        $circle->setAttribute('cx', (string)$centerX);
        $circle->setAttribute('cy', (string)$centerY);
        $circle->setAttribute('r', (string)($buttonSize / 2));
        $circle->setAttribute('fill', '#ffffff');
        $circle->setAttribute('fill-opacity', '0.9');
        
        return $circle;
    }

    private function createVideoLabel(SvgDocumentBuilder $builder, Layer $layer, DOMDocument $document): DOMElement
    {
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        $text = $builder->createElement('text', $document);
        $text->setAttribute('x', (string)($width / 2));
        $text->setAttribute('y', (string)($height - 10));
        $text->setAttribute('text-anchor', 'middle');
        $text->setAttribute('font-family', 'Arial, sans-serif');
        $text->setAttribute('font-size', '12');
        $text->setAttribute('fill', '#9ca3af');
        $text->appendChild($builder->createText('Video Layer', $text->ownerDocument));
        
        return $text;
    }
}
