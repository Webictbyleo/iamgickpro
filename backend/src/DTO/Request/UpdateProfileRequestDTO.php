<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for comprehensive profile update requests
 */
class UpdateProfileRequestDTO
{
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'First name cannot be empty',
        maxMessage: 'First name cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\'\.]+$/u',
        message: 'First name can only contain letters, spaces, hyphens, apostrophes, and periods'
    )]
    public readonly ?string $firstName;

    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Last name cannot be empty',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\'\.]+$/u',
        message: 'Last name can only contain letters, spaces, hyphens, apostrophes, and periods'
    )]
    public readonly ?string $lastName;

    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Username must be at least {{ limit }} characters long',
        maxMessage: 'Username cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_]+$/',
        message: 'Username can only contain letters, numbers, and underscores'
    )]
    public readonly ?string $username;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Job title cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\-\.\,\(\)\/&]+$/u',
        message: 'Job title contains invalid characters'
    )]
    public readonly ?string $jobTitle;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Company name cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\-\.\,\(\)\/&]+$/u',
        message: 'Company name contains invalid characters'
    )]
    public readonly ?string $company;

    #[Assert\Url(message: 'Website must be a valid URL')]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Website URL cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $website;

    #[Assert\Url(message: 'Portfolio must be a valid URL')]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Portfolio URL cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $portfolio;

    #[Assert\Length(
        max: 1000,
        maxMessage: 'Bio cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\-\.\,\(\)\/&\'\"\!\?\:\;\n\r]+$/u',
        message: 'Bio contains invalid characters'
    )]
    public readonly ?string $bio;

    #[Assert\Type(type: 'array', message: 'Social links must be an array')]
    #[Assert\Count(
        max: 10,
        maxMessage: 'Cannot have more than {{ limit }} social links'
    )]
    #[Assert\Callback(
        callback: [self::class, 'validateSocialLinks']
    )]
    public readonly ?array $socialLinks;

    #[Assert\Length(
        max: 50,
        maxMessage: 'Timezone cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Timezone(message: 'Invalid timezone')]
    public readonly ?string $timezone;

    #[Assert\Length(
        max: 10,
        maxMessage: 'Language code cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Locale(message: 'Invalid language code')]
    public readonly ?string $language;

    public function __construct(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $username = null,
        ?string $jobTitle = null,
        ?string $company = null,
        ?string $website = null,
        ?string $portfolio = null,
        ?string $bio = null,
        ?array $socialLinks = null,
        ?string $timezone = null,
        ?string $language = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;
        $this->jobTitle = $jobTitle;
        $this->company = $company;
        $this->website = $website;
        $this->portfolio = $portfolio;
        $this->bio = $bio;
        $this->socialLinks = $socialLinks;
        $this->timezone = $timezone;
        $this->language = $language;
    }

    /**
     * Check if any profile data is provided for update
     */
    public function hasAnyData(): bool
    {
        return $this->firstName !== null 
            || $this->lastName !== null 
            || $this->username !== null 
            || $this->jobTitle !== null
            || $this->company !== null
            || $this->website !== null
            || $this->portfolio !== null
            || $this->bio !== null
            || $this->socialLinks !== null
            || $this->timezone !== null
            || $this->language !== null;
    }

    /**
     * Convert DTO to array for service layer
     */
    public function toArray(): array
    {
        $data = [];
        
        if ($this->firstName !== null) {
            $data['firstName'] = $this->firstName;
        }
        if ($this->lastName !== null) {
            $data['lastName'] = $this->lastName;
        }
        if ($this->username !== null) {
            $data['username'] = $this->username;
        }
        if ($this->jobTitle !== null) {
            $data['jobTitle'] = $this->jobTitle;
        }
        if ($this->company !== null) {
            $data['company'] = $this->company;
        }
        if ($this->website !== null) {
            $data['website'] = $this->website;
        }
        if ($this->portfolio !== null) {
            $data['portfolio'] = $this->portfolio;
        }
        if ($this->bio !== null) {
            $data['bio'] = $this->bio;
        }
        if ($this->socialLinks !== null) {
            $data['socialLinks'] = $this->socialLinks;
        }
        if ($this->timezone !== null) {
            $data['timezone'] = $this->timezone;
        }
        if ($this->language !== null) {
            $data['language'] = $this->language;
        }
        
        return $data;
    }

    /**
     * Custom validation for social links format (platform=url)
     */
    public static function validateSocialLinks(?array $socialLinks, \Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($socialLinks === null || empty($socialLinks)) {
            return;
        }

        foreach ($socialLinks as $platform => $url) {
            // Skip empty URLs - they are optional
            if (empty($url) || $url === null) {
                continue;
            }

            // Validate platform name (key) - only if URL is provided
            if (!is_string($platform) || empty($platform)) {
                $context->buildViolation('Platform name must be a non-empty string')
                    ->atPath('[' . $platform . ']')
                    ->addViolation();
                continue;
            }

            if (strlen($platform) > 50) {
                $context->buildViolation('Platform name cannot be longer than 50 characters')
                    ->atPath('[' . $platform . ']')
                    ->addViolation();
                continue;
            }

            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $platform)) {
                $context->buildViolation('Platform name can only contain letters, numbers, underscores, and hyphens')
                    ->atPath('[' . $platform . ']')
                    ->addViolation();
                continue;
            }

            // Validate URL (value) - only validate non-empty URLs
            if (!is_string($url)) {
                $context->buildViolation('URL must be a string')
                    ->atPath('[' . $platform . ']')
                    ->addViolation();
                continue;
            }

            if (strlen($url) > 500) {
                $context->buildViolation('URL cannot be longer than 500 characters')
                    ->atPath('[' . $platform . ']')
                    ->addViolation();
                continue;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $context->buildViolation('Must be a valid URL')
                    ->atPath('[' . $platform . ']')
                    ->addViolation();
            }
        }
    }
}
