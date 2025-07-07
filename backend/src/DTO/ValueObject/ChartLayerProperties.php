<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Properties specific to chart layers
 */
final readonly class ChartLayerProperties extends LayerProperties
{
    public function __construct(
        /**
         * Type of chart to render (bar, line, pie, doughnut, area, scatter, bubble)
         * @var string $chartType
         */
        #[Assert\Choice(
            choices: ['bar', 'line', 'pie', 'doughnut', 'area', 'scatter', 'bubble'],
            message: 'Invalid chart type'
        )]
        public string $chartType = 'bar',

        /**
         * Chart data structure containing labels and datasets
         * Expected structure:
         * [
         *   'labels' => ['Jan', 'Feb', 'Mar'],
         *   'datasets' => [
         *     [
         *       'label' => 'Dataset 1',
         *       'data' => [10, 20, 30],
         *       'backgroundColor' => '#3B82F6',
         *       'borderColor' => '#1E40AF',
         *       'borderWidth' => 2
         *     ]
         *   ]
         * ]
         * @var array $data
         */
        #[Assert\Type(type: 'array', message: 'Chart data must be an array')]
        #[Assert\NotBlank(message: 'Chart data is required')]
        public array $data = [
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
        ],

        /**
         * Chart options for layout, plugins, scales, and animations
         * Expected structure:
         * [
         *   'responsive' => true,
         *   'maintainAspectRatio' => false,
         *   'plugins' => [
         *     'legend' => ['display' => true, 'position' => 'top'],
         *     'title' => ['display' => false, 'text' => 'Chart Title'],
         *     'tooltip' => ['enabled' => true]
         *   ],
         *   'scales' => [
         *     'x' => ['display' => true, 'grid' => ['display' => true, 'color' => '#E5E7EB']],
         *     'y' => ['display' => true, 'grid' => ['display' => true, 'color' => '#E5E7EB']]
         *   ],
         *   'animation' => ['duration' => 1000, 'easing' => 'easeInOutQuad']
         * ]
         * @var array $options
         */
        #[Assert\Type(type: 'array', message: 'Chart options must be an array')]
        public array $options = [
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
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeInOutQuad'
            ]
        ],

        /**
         * Chart theme configuration for colors and styling
         * Expected structure:
         * [
         *   'primary' => '#3B82F6',
         *   'secondary' => '#8B5CF6',
         *   'background' => '#FFFFFF',
         *   'text' => '#1F2937',
         *   'grid' => '#E5E7EB',
         *   'accent' => ['#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899']
         * ]
         * @var array $theme
         */
        #[Assert\Type(type: 'array', message: 'Chart theme must be an array')]
        public array $theme = [
            'primary' => '#3B82F6',
            'secondary' => '#8B5CF6',
            'background' => '#FFFFFF',
            'text' => '#1F2937',
            'grid' => '#E5E7EB',
            'accent' => ['#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899']
        ]
    ) {
    }

    /**
     * Convert properties to array for storage
     */
    public function toArray(): array
    {
        return [
            'chartType' => $this->chartType,
            'data' => $this->data,
            'options' => $this->options,
            'theme' => $this->theme
        ];
    }

    /**
     * Create properties from array data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            chartType: $data['chartType'] ?? 'bar',
            data: $data['data'] ?? [
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
            ],
            options: $data['options'] ?? [
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
                ],
                'animation' => [
                    'duration' => 1000,
                    'easing' => 'easeInOutQuad'
                ]
            ],
            theme: $data['theme'] ?? [
                'primary' => '#3B82F6',
                'secondary' => '#8B5CF6',
                'background' => '#FFFFFF',
                'text' => '#1F2937',
                'grid' => '#E5E7EB',
                'accent' => ['#EF4444', '#F59E0B', '#10B981', '#F97316', '#8B5CF6', '#EC4899']
            ]
        );
    }

    /**
     * Validate chart data structure
     */
    public function validateChartData(): bool
    {
        // Check if data has required structure
        if (!isset($this->data['labels']) || !is_array($this->data['labels'])) {
            return false;
        }

        if (!isset($this->data['datasets']) || !is_array($this->data['datasets'])) {
            return false;
        }

        // Validate each dataset
        foreach ($this->data['datasets'] as $dataset) {
            if (!is_array($dataset) || !isset($dataset['data']) || !is_array($dataset['data'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get supported chart types
     */
    public static function getSupportedChartTypes(): array
    {
        return ['bar', 'line', 'pie', 'doughnut', 'area', 'scatter', 'bubble'];
    }
}
