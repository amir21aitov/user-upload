<?php

namespace App\Policies;

use App\Models\Image;
use App\Models\User;

class ImagePolicy
{
    public function view(User $user, Image $image): bool
    {
        return $user->id === $image->user_id;
    }

    public function delete(User $user, Image $image): bool
    {
        return $user->id === $image->user_id;
    }
}
