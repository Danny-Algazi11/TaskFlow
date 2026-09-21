<?php

namespace App\Application\WorkspaceInvitation\DTOs;

final class AcceptInvitationData
{
    public function __construct(
        public readonly string $token,
        public readonly int $actingUserId,
        // Sourced from the authenticated request user, never client input —
        // spoofing this would defeat the whole point of the email check.
        public readonly string $actingUserEmail,
    ) {
    }
}
