<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        return $this->owns($user, $document)
            || $document->sharedWith()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        return $this->owns($user, $document)
            || $document->sharedWith()
                ->where('users.id', $user->id)
                ->wherePivot('permission', 'edit')
                ->exists();
    }

    /**
     * Determine whether the user can share the model.
     */
    public function share(User $user, Document $document): bool
    {
        return $this->owns($user, $document);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return $this->owns($user, $document);
    }

    private function owns(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }
}
