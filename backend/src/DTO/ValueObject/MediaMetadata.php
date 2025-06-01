<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Media file metadata containing technical information about the media file
 */
final readonly class MediaMetadata
{
    public function __construct(
        /**
         * File size in bytes of the media file
         * @var int $fileSize
         */
        #[Assert\Type(type: 'integer', message: 'File size must be an integer')]
        #[Assert\Positive(message: 'File size must be positive')]
        public int $fileSize,

        /**
         * MIME type of the media file (e.g., image/jpeg, video/mp4, audio/mpeg)
         * @var string $mimeType
         */
        #[Assert\NotBlank(message: 'MIME type is required')]
        #[Assert\Length(max: 100, maxMessage: 'MIME type cannot exceed 100 characters')]
        public string $mimeType,

        /**
         * Width of the media in pixels (for images and videos)
         * @var int|null $width
         */
        #[Assert\Type(type: 'integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        public ?int $width = null,

        /**
         * Height of the media in pixels (for images and videos)
         * @var int|null $height
         */
        #[Assert\Type(type: 'integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        public ?int $height = null,

        /**
         * Duration of the media in seconds (for audio and video files)
         * @var float|null $duration
         */
        #[Assert\Type(type: 'float', message: 'Duration must be a number')]
        #[Assert\Positive(message: 'Duration must be positive')]
        public ?float $duration = null,

        /**
         * Bitrate of the media in bits per second (for audio and video)
         * @var int|null $bitrate
         */
        #[Assert\Type(type: 'integer', message: 'Bitrate must be an integer')]
        #[Assert\Positive(message: 'Bitrate must be positive')]
        public ?int $bitrate = null,

        /**
         * Audio sample rate in Hz (for audio files)
         * @var int|null $sampleRate
         */
        #[Assert\Type(type: 'integer', message: 'Sample rate must be an integer')]
        #[Assert\Positive(message: 'Sample rate must be positive')]
        public ?int $sampleRate = null,

        /**
         * Number of audio channels (1 = mono, 2 = stereo, etc.)
         * @var int|null $channels
         */
        #[Assert\Type(type: 'integer', message: 'Channels must be an integer')]
        #[Assert\Positive(message: 'Channels must be positive')]
        public ?int $channels = null,

        /**
         * Color space of the image (e.g., sRGB, Adobe RGB, CMYK)
         * @var string|null $colorSpace
         */
        #[Assert\Length(max: 100, maxMessage: 'Color space cannot exceed 100 characters')]
        public ?string $colorSpace = null,

        /**
         * Whether the image has transparency/alpha channel support
         * @var bool|null $hasTransparency
         */
        #[Assert\Type(type: 'boolean', message: 'Has transparency must be boolean')]
        public ?bool $hasTransparency = null,

        /**
         * Frame rate of video files in frames per second
         * @var int|null $frameRate
         */
        #[Assert\Type(type: 'integer', message: 'Frame rate must be an integer')]
        #[Assert\Positive(message: 'Frame rate must be positive')]
        public ?int $frameRate = null,

        /**
         * Codec used to encode the media file (e.g., H.264, VP9, AAC)
         * @var string|null $codec
         */
        #[Assert\Length(max: 255, maxMessage: 'Codec cannot exceed 255 characters')]
        public ?string $codec = null,

        /**
         * Aspect ratio of the media (width/height, e.g., 1.777 for 16:9)
         * @var float|null $aspectRatio
         */
        #[Assert\Type(type: 'float', message: 'Aspect ratio must be a number')]
        #[Assert\Positive(message: 'Aspect ratio must be positive')]
        public ?float $aspectRatio = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'fileSize' => $this->fileSize,
            'mimeType' => $this->mimeType,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'bitrate' => $this->bitrate,
            'sampleRate' => $this->sampleRate,
            'channels' => $this->channels,
            'colorSpace' => $this->colorSpace,
            'hasTransparency' => $this->hasTransparency,
            'frameRate' => $this->frameRate,
            'codec' => $this->codec,
            'aspectRatio' => $this->aspectRatio,
        ], fn($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fileSize: (int)$data['fileSize'],
            mimeType: $data['mimeType'],
            width: isset($data['width']) ? (int)$data['width'] : null,
            height: isset($data['height']) ? (int)$data['height'] : null,
            duration: isset($data['duration']) ? (float)$data['duration'] : null,
            bitrate: isset($data['bitrate']) ? (int)$data['bitrate'] : null,
            sampleRate: isset($data['sampleRate']) ? (int)$data['sampleRate'] : null,
            channels: isset($data['channels']) ? (int)$data['channels'] : null,
            colorSpace: $data['colorSpace'] ?? null,
            hasTransparency: isset($data['hasTransparency']) ? (bool)$data['hasTransparency'] : null,
            frameRate: isset($data['frameRate']) ? (int)$data['frameRate'] : null,
            codec: $data['codec'] ?? null,
            aspectRatio: isset($data['aspectRatio']) ? (float)$data['aspectRatio'] : null,
        );
    }
}
