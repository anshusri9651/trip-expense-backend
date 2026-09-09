<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->guard($request);
        $search = trim((string) $request->query('q', ''));
        $userQuery = User::query();
        $tripQuery = Trip::query();
        $expenseQuery = Expense::query();
        $memberQuery = TripMember::query();
        if ($search !== '') {
            $like = '%'.$search.'%';
            $userQuery->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            $tripQuery->where(fn ($q) => $q->where('trip_name', 'like', $like)->orWhere('group_name', 'like', $like));
            $expenseQuery->where(fn ($q) => $q->where('title', 'like', $like)->orWhereHas('trip', fn ($q) => $q->where('trip_name', 'like', $like)));
            $memberQuery->where(fn ($q) => $q->where('name', 'like', $like)->orWhereHas('trip', fn ($q) => $q->where('trip_name', 'like', $like)));
        }
        return view('admin.index', [
            'admin' => $request->user(), 'search' => $search,
            'users' => $userQuery->latest()->paginate(10, ['*'], 'users')->withQueryString(),
            'trips' => $tripQuery->with('user')->withCount(['members', 'expenses'])->latest()->paginate(10, ['*'], 'trips')->withQueryString(),
            'expenses' => $expenseQuery->with(['trip', 'paidBy'])->latest()->paginate(10, ['*'], 'expenses')->withQueryString(),
            'members' => $memberQuery->with('trip')->latest()->paginate(10, ['*'], 'members')->withQueryString(),
            'activities' => AdminActivity::with('user')->when($search !== '', fn ($q) => $q->where('action', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%'))->latest()->paginate(10, ['*'], 'activity')->withQueryString(),
            'stats' => ['users' => User::count(), 'active_users' => User::where('is_active', true)->count(), 'trips' => Trip::count(), 'expenses' => Expense::count(), 'volume' => Expense::sum('amount')],
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->guard($request); $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email,'.$user->id]]);
        $user->update($data); $this->log($request, 'user.updated', $user, 'Updated user profile.'); return back()->with('status', 'User updated.');
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        $this->guard($request); abort_if($user->id === $request->user()->id, 422, 'You cannot disable your own account.');
        $user->update(['is_active' => ! $user->is_active]); $this->log($request, $user->is_active ? 'user.enabled' : 'user.disabled', $user, 'Changed user active status.'); return back()->with('status', 'User status updated.');
    }

    public function deleteUser(Request $request, User $user): RedirectResponse
    {
        $this->guard($request); abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');
        $id = $user->id; $user->delete(); $this->log($request, 'user.deleted', null, "Deleted user #{$id}."); return back()->with('status', 'User deleted.');
    }

    public function setAdmin(Request $request, User $user): RedirectResponse
    {
        $this->guard($request); abort_if($user->id === $request->user()->id, 422, 'You cannot change your own admin role.');
        $user->update(['is_admin' => ! $user->is_admin]); $this->log($request, $user->is_admin ? 'user.promoted' : 'user.demoted', $user, 'Changed admin role.'); return back()->with('status', 'Admin role updated.');
    }

    public function deleteTrip(Request $request, Trip $trip): RedirectResponse
    { $this->guard($request); $id = $trip->id; $trip->delete(); $this->log($request, 'trip.deleted', null, "Deleted trip #{$id} from admin panel."); return back()->with('status', 'Trip deleted.'); }

    public function deleteExpense(Request $request, Expense $expense): RedirectResponse
    { $this->guard($request); $id = $expense->id; $expense->splits()->delete(); $expense->delete(); $this->log($request, 'expense.deleted', null, "Deleted expense #{$id} from admin panel."); return back()->with('status', 'Expense deleted.'); }

    public function deleteMember(Request $request, TripMember $member): RedirectResponse
    { $this->guard($request); abort_if($member->is_owner, 422, 'The trip owner cannot be removed.'); abort_if($member->expensesPaid()->exists() || $member->expenseSplits()->exists(), 422, 'This member has financial records and cannot be removed.'); $member->delete(); $this->log($request, 'member.deleted', null, 'Removed a trip member from admin panel.'); return back()->with('status', 'Member removed.'); }

    private function guard(Request $request): void { abort_unless($request->user()?->is_admin && $request->user()?->is_active, 403); }
    private function log(Request $request, string $action, $subject, string $description): void { AdminActivity::create(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => $subject ? get_class($subject) : null, 'subject_id' => $subject?->id, 'description' => $description, 'ip_address' => $request->ip()]); }
}
