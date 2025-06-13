<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\DTO\ValueObject\Tag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Custom normalizer for Tag value objects to handle serialization/deserialization
 */
class TagNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize($object, string $format = null, array $context = []): string
    {
        if (!$object instanceof Tag) {
            throw new \InvalidArgumentException('Expected Tag object');
        }

        return $object->name;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Tag;
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): Tag
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException('Expected string for Tag denormalization');
        }

        return new Tag($data);
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === Tag::class && is_string($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Tag::class => true,
        ];
    }
}
