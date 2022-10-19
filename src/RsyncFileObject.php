<?php

declare(strict_types=1);

namespace Sunaoka\RsyncUtils;

use JsonSerializable;

class RsyncFileObject implements JsonSerializable
{
    private ?string $path = null;

    private string $type;

    private ?string $updateType = null;

    private ?string $fileType = null;

    private bool $checksum = false;

    private bool $size = false;

    private bool $timestamp = false;

    private bool $permissions = false;

    private bool $owner = false;

    private bool $group = false;

    private bool $acl = false;

    private bool $xattr = false;

    private ?string $message = null;

    public function __construct(string $line)
    {
        $this->setAttributes($line);
    }

    private function setAttributes(string $line): void
    {
        $skipping = $this->isError($line);
        if ($skipping) {
            $this->setSkipped($line);
            return;
        }

        [$itemized, $this->path] = preg_split('/ +/', $line, 2);

        $items = str_split($itemized);

        $this->updateType = match ($items[0]) {
            '<'     => 'sent',
            '>'     => 'received',
            'c'     => 'changed',
            'h'     => 'hardlink',
            '.'     => 'unchanged',
            '*'     => 'message',
            default => 'unknown',
        };

        if ($this->updateType === 'message') {
            $this->updateType = substr($itemized, 1);
        }

        $this->fileType = match ($itemized[1]) {
            'f'     => 'file',
            'd'     => 'directory',
            'L'     => 'symlink',
            'D'     => 'device',
            'S'     => 'special',
            default => 'unknown',
        };

        if ($this->updateType === 'unknown' || $this->fileType === 'unknown') {
            $this->setSkipped($line);
            return;
        }

        $this->type = match ($this->updateType) {
            'deleting' => 'deleted',
            default    => match ($itemized[2]) {
                ' '     => 'unchanged',
                '+'     => 'created',
                default => 'updated',
            },
        };

        if ($this->updateType === 'hardlink') {
            $this->path = explode(' => ', $this->path)[0];
        }

        if ($this->fileType === 'symlink') {
            $this->path = explode(' -> ', $this->path)[0];
        }

        $this->checksum = $items[2] === 'c';
        $this->size = $items[3] === 's';
        $this->timestamp = $items[4] === 't' || $items[4] === 'T';
        $this->permissions = $items[5] === 'p';
        $this->owner = $items[6] === 'o';
        $this->group = $items[7] === 'g';
        $this->acl = ($items[9] ?? null) === 'a';
        $this->xattr = ($items[10] ?? null) === 'x';

    }

    public function toArray(): array
    {
        return [
            'path'        => $this->path,
            'type'        => $this->type,
            'updateType'  => $this->updateType,
            'fileType'    => $this->fileType,
            'checksum'    => $this->checksum,
            'size'        => $this->size,
            'timestamp'   => $this->timestamp,
            'permissions' => $this->permissions,
            'owner'       => $this->owner,
            'group'       => $this->group,
            'acl'         => $this->acl,
            'xattr'       => $this->xattr,
            'message'     => $this->message,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUpdateType(): string
    {
        return $this->updateType;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function isChecksum(): bool
    {
        return $this->checksum;
    }

    public function isSize(): bool
    {
        return $this->size;
    }

    public function isPermissions(): bool
    {
        return $this->permissions;
    }

    public function isOwner(): bool
    {
        return $this->owner;
    }

    public function isGroup(): bool
    {
        return $this->group;
    }

    public function isAcl(): bool
    {
        return $this->acl;
    }

    public function isXattr(): bool
    {
        return $this->xattr;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isSkipped(): bool
    {
        return $this->type === 'skipped';
    }

    public function isDeleted(): bool
    {
        return $this->type === 'deleted';
    }

    public function isUnchanged(): bool
    {
        return $this->type === 'unchanged';
    }

    public function isCreated(): bool
    {
        return $this->type === 'created';
    }

    public function isUpdated(): bool
    {
        return $this->type === 'updated';
    }

    private function setSkipped(string $line): void
    {
        $this->path = null;
        $this->type = 'skipped';
        $this->message = $line;
    }

    private function isError(string $line): bool
    {
        $messages = [
            'cannot',
            'symlink',
            'skipping',
            'not',
            'IO error',
        ];

        foreach ($messages as $message) {
            if (str_starts_with($line, $message)) {
                return true;
            }
        }

        return false;
    }
}
