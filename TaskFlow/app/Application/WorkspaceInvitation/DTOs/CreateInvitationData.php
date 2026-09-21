<?php

namespace App\Application\WorkspaceInvitation\DTOs;

final class CreateInvitationData
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly int $actingUserId,
        public readonly string $email,
        public readonly string $role,
    ) {
    }
}
