<?php
// app/Policies/AnnouncementPolicy.php
namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    // Izinkan admin/superadmin melakukan semuanya
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(['admin', 'superadmin'])) {
            return true;
        }
        return null; // Lanjutkan ke method di bawah jika bukan admin
    }

    public function viewAny(User $user): bool { return $user->can('manage-announcements'); }
    public function view(User $user, Announcement $announcement): bool { return $user->can('manage-announcements'); }
    public function create(User $user): bool { return $user->can('manage-announcements'); }
    public function update(User $user, Announcement $announcement): bool { return $user->can('manage-announcements'); }
    public function delete(User $user, Announcement $announcement): bool { return $user->can('manage-announcements'); }
    public function restore(User $user, Announcement $announcement): bool { return $user->can('manage-announcements'); }
    public function forceDelete(User $user, Announcement $announcement): bool { return $user->can('manage-announcements'); }
}