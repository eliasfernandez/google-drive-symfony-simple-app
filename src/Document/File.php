<?php

namespace App\Document;

class File
{
    public function __construct(
        public string $id,
        public string $name,
        public string $mimeType,
        public \DateTimeImmutable $modifiedTime,
    ) {
    }

    public function getType(): string
    {
        return match ($this->mimeType) {
            'application/vnd.google-apps.folder' => 'folder',
            'image/png' => 'png',
            default => $this->mimeType,
        };
    }
}
