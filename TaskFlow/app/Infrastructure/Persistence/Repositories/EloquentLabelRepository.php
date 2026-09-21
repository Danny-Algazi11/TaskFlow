<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Infrastructure\Persistence\Models\Label;
use Illuminate\Support\Collection;

class EloquentLabelRepository implements LabelRepositoryInterface
{
    public function find(int $id): ?Label
    {
        return Label::find($id);
    }

    public function forWorkspace(int $workspaceId): Collection
    {
        return Label::where('workspace_id', $workspaceId)->get();
    }

    public function create(array $attributes): Label
    {
        return Label::create($attributes);
    }

    public function update(Label $label, array $attributes): Label
    {
        $label->update($attributes);

        return $label;
    }

    public function delete(Label $label): void
    {
        $label->delete();
    }
}
