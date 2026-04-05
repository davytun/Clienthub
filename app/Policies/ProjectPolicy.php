<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Staff/owner can view any project in their business.
     * The global scope already filters — this is a safety net.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->business_id === $project->business_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'staff']);
    }

    public function update(User $user, Project $project): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $project->business_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $project->business_id;
    }

    public function uploadFile(User $user, Project $project): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $project->business_id;
    }
}
