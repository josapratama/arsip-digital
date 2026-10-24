<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view-documents');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('view-documents');
    }

    public function create(User $user): bool
    {
        return $user->can('create-documents');
    }

    public function update(User $user, Document $document): bool
    {
        if (!$user->can('edit-documents')) {
            return false;
        }

        // Sekdes/Operator can edit all, or original uploader can edit
        return $user->hasRole('sekdes-operator') || $document->uploaded_by === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        if (!$user->can('delete-documents')) {
            return false;
        }

        // Sekdes/Operator can delete, or original uploader
        return $user->hasRole('sekdes-operator') || $document->uploaded_by === $user->id;
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->can('restore-documents');
    }

    public function forceDelete(User $user, Document $document): bool
    {
        // Only admin or Sekdes can permanently purge documents
        return $user->hasRole('admin');
    }
}
