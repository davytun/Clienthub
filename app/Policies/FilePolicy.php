<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    /**
     * Any staff member of the owning business can download.
     */
    public function download(User $user, File $file): bool
    {
        return $user->business_id === $file->business_id;
    }

    public function delete(User $user, File $file): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $file->business_id;
    }
}
