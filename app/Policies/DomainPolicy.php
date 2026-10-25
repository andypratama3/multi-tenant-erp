<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthUser;

abstract class DomainPolicy
{
    use HandlesAuthorization;

    abstract protected static function subject(): string;

    public function viewAny(AuthUser $user): bool
    {
        return $this->allows($user, 'ViewAny');
    }

    public function view(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'View');
    }

    public function create(AuthUser $user): bool
    {
        return $this->allows($user, 'Create');
    }

    public function update(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Update');
    }

    public function delete(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Delete');
    }

    public function deleteAny(AuthUser $user): bool
    {
        return $this->allows($user, 'DeleteAny');
    }

    public function restore(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Restore');
    }

    public function restoreAny(AuthUser $user): bool
    {
        return $this->allows($user, 'RestoreAny');
    }

    public function forceDelete(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'ForceDelete');
    }

    public function forceDeleteAny(AuthUser $user): bool
    {
        return $this->allows($user, 'ForceDeleteAny');
    }

    public function replicate(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Replicate');
    }

    public function reorder(AuthUser $user): bool
    {
        return $this->allows($user, 'Reorder');
    }

    public function approve(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Approve');
    }

    public function send(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Send');
    }

    public function cancel(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Cancel');
    }

    public function confirm(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Confirm');
    }

    public function post(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Post');
    }

    public function recordPayment(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'RecordPayment');
    }

    public function downloadPdf(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'DownloadPdf');
    }

    public function void(AuthUser $user, Model $model): bool
    {
        return $this->allows($user, 'Void');
    }

    protected function allows(AuthUser $user, string $ability): bool
    {
        return $user->can($ability.':'.static::subject());
    }
}
