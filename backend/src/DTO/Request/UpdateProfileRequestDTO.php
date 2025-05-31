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
    public readonly ?string $firstName;

    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Last name cannot be empty',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
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

    #[Assert\Email(message: 'Please provide a valid email address')]
    public readonly ?string $email;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Job title cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $jobTitle;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Company name cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $company;

    #[Assert\Url(message: 'Website must be a valid URL')]
    public readonly ?string $website;

    #[Assert\Url(message: 'Portfolio must be a valid URL')]
    public readonly ?string $portfolio;

    #[Assert\Length(
        max: 1000,
        maxMessage: 'Bio cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $bio;

    #[Assert\Type(type: 'array', message: 'Social links must be an array')]
    public readonly ?array $socialLinks;

    #[Assert\Length(
        max: 50,
        maxMessage: 'Timezone cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $timezone;

    #[Assert\Length(
        max: 10,
        maxMessage: 'Language code cannot be longer than {{ limit }} characters'
    )]
    public readonly ?string $language;

    public function __construct(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $username = null,
        ?string $email = null,
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
        $this->email = $email;
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
            || $this->email !== null
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
        if ($this->email !== null) {
            $data['email'] = $this->email;
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
}
