<?php

namespace App\Application\Workspace\DTOs;

final class CreateWorkspaceData
{
    public function __construct(
        public readonly string $name,
        public readonly int $ownerId,
    ) {
    }
}
