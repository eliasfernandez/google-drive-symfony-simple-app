<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Security\Core\User\UserInterface;

#[MongoDB\Document]
class User implements UserInterface
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\Field(type: "string")]
    private string $email;

    #[MongoDB\Field(type: "string", nullable: true)]
    private ?string $name = null;

    #[MongoDB\Field(type: "string", nullable: true)]
    private ?string $googleId = null;

    #[MongoDB\Field(type: "string", nullable: true)]
    private ?string $lastToken = null;


    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): User
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): User
    {
        $this->name = $name;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): User
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        $this->googleId = null;
        $this->lastToken = null;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getLastToken(): ?string
    {
        return $this->lastToken;
    }

    public function setLastToken(?string $lastToken): User
    {
        $this->lastToken = $lastToken;
        return $this;
    }
}
