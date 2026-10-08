<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\Module;
use App\Models\PracticePackage;
use App\Models\User;
use Illuminate\Support\Collection;

class LearningAccess
{
    public function granted(User $user, string $type, int $id): bool
    {
        return $user->isAdmin() || $user->grants->contains(fn ($g) => $g->resource_type === $type && $g->resource_id === $id);
    }

    public function moduleActive(Module $module): bool
    {
        return $module->status === 'active' && $module->program->status === 'active';
    }

    public function package(User $user, PracticePackage $package): bool
    {
        return $package->status === 'active' && $this->moduleActive($package->module)
            && ($this->granted($user, 'program', $package->module->program_id)
                || $this->granted($user, 'module', $package->module_id)
                || $this->granted($user, 'package', $package->id));
    }

    public function material(User $user, LearningMaterial $material): bool
    {
        return $material->status === 'active' && $this->moduleActive($material->module)
            && ($this->granted($user, 'program', $material->module->program_id)
                || $this->granted($user, 'module', $material->module_id)
                || $this->granted($user, 'material', $material->id));
    }

    public function packages(User $user): Collection
    {
        return PracticePackage::with('module.program')->withCount(['questions' => fn ($q) => $q->where('status', 'active')])
            ->where('status', 'active')->get()->filter(fn ($p) => $this->package($user, $p))->values();
    }

    public function materials(User $user): Collection
    {
        return LearningMaterial::with('module.program')->where('status', 'active')->get()
            ->filter(fn ($m) => $this->material($user, $m))->values();
    }
}
