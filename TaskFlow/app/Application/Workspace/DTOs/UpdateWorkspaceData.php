<?php

namespace App\Application\Workspace\DTOs;

final class UpdateWorkspaceData
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly int $actingUserId,
        public readonly string $name,
    ) {
    }
}
