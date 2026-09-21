<?php

namespace App\Domain\Label\Repositories;

use App\Infrastructure\Persistence\Models\Label;
use Illuminate\Support\Collection;

interface LabelRepositoryInterface
{
    public function find(int $id): ?Label;

    /**
     * @return Collection<int, Label>
     */
    public function forWorkspace(int $workspaceId): Collection;

    public function create(array $attributes): Label;

    public function update(Label $label, array $attributes): Label;

    public function delete(Label $label): void;
}
