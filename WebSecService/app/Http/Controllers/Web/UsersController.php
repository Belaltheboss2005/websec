<?php
namespace App\Http\Controllers\Web;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Artisan;

use App\Http\Controllers\Controller;
use App\Models\User;

class UsersController extends Controller {

	use ValidatesRequests;

    public function list()
    {
        $user = auth()->user();
        if (!auth()->user()->hasPermissionTo('show_users')) {
            abort(401); // Unauthorized
        }

        if ($user->hasRole('Employee')) {
            // Employees can only see customers
            $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'Customer');
            })->get();
        } else {
            // Admins or other roles can see all users
            $users = User::all();
        }

        return view('users.list', compact('users'));
    }

	public function register(Request $request) {
        return view('users.register');
    }

    public function doRegister(Request $request) {

        try {
            $this->validate($request, [
                'name' => ['required', 'string', 'min:5'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'confirmed', Password::min(6)->numbers()->letters()->mixedCase()->symbols()],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withInput($request->input())->withErrors('Invalid registration information.');
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password); // Secure
        $user->credit = 80000; // Assign 5000 credit to the user
        $user->save();

        // Assign the "customer" role to the user
        $user->assignRole('customer');

        return redirect('/');
    }
    public function addUser()
{
    if (!auth()->user()->hasPermissionTo('add_users')) {
        abort(401); // Unauthorized
    }

    $roles = Role::all(); // Fetch all roles for the dropdown
    return view('users.add', compact('roles'));
}




    public function storeUser(Request $request)
    {
        
        if(!auth()->user()->hasPermissionTo('add_users')) abort(401);

        Log::info('storeUser method called', ['request' => $request->all()]);

        if (!auth()->user()->hasPermissionTo('add_users')) {
            Log::warning('Unauthorized access attempt to storeUser by user ID: ' . auth()->id());
            abort(401); // Unauthorized
        }

        $this->validate($request, [
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(6)->numbers()->letters()->mixedCase()->symbols()],
            'credit' => ['required', 'integer', 'min:0'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->credit = $request->credit;
            $user->save();

            // Assign the selected role to the user
            $user->assignRole($request->role);

            Log::info('New user created successfully', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'role' => $request->role,
            ]);

            return redirect()->route('users')->with('success', 'User added successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating user', [
                'admin_id' => auth()->id(),
                'error_message' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors('An error occurred while adding the user.');
        }
    }
    public function login(Request $request) {
        return view('users.login');
    }

    public function doLogin(Request $request) {

    	if(!Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return redirect()->back()->withInput($request->input())->withErrors('Invalid login information.');

        $user = User::where('email', $request->email)->first();
        Auth::setUser($user);

        return redirect('/');
    }

    public function doLogout(Request $request) {

    	Auth::logout();

        return redirect('/');
    }

    public function profile(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) abort(401);
        }

        $permissions = [];
        foreach($user->permissions as $permission) {
            $permissions[] = $permission;
        }
        foreach($user->roles as $role) {
            foreach($role->permissions as $permission) {
                $permissions[] = $permission;
            }
        }

        return view('users.profile', compact('user', 'permissions'));
    }

    public function edit(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }

        $roles = [];
        foreach(Role::all() as $role) {
            $role->taken = ($user->hasRole($role->name));
            $roles[] = $role;
        }

        $permissions = [];
        $directPermissionsIds = $user->permissions()->pluck('id')->toArray();
        foreach(Permission::all() as $permission) {
            $permission->taken = in_array($permission->id, $directPermissionsIds);
            $permissions[] = $permission;
        }

        return view('users.edit', compact('user', 'roles', 'permissions'));
    }

    public function save(Request $request, User $user) {

        if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);

        $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'credit' => ['required', 'numeric', 'min:0'], // Validation for credit
        ]);

        // Ensure the credit can only be increased
        if ($request->credit < $user->credit) {
            return redirect()->back()->withErrors(['credit' => 'You cannot decrease the user\'s credit.'])->withInput();
        }

        $user->name = $request->name;
        $user->credit = $request->credit; // Save the credit value
        $user->save();

        // Only sync roles and permissions if the user has the appropriate permission
        if (auth()->user()->hasPermissionTo('admin_users')) {
            // Sync roles only if roles are provided in the request
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            // Sync permissions only if permissions are provided in the request
            if ($request->has('permissions')) {
                $user->syncPermissions($request->permissions);
            }
            // Clear cache to reflect changes
            Artisan::call('cache:clear');
        }

        return redirect(route('profile', ['user' => $user->id]));
    }

    public function delete(Request $request, User $user) {

        if(!auth()->user()->hasPermissionTo('delete_users')) abort(401);

        $user->delete();

        return redirect()->route('users');
    }

    public function editPassword(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }

        return view('users.edit_password', compact('user'));
    }

    public function savePassword(Request $request, User $user) {

        if(auth()->id()==$user?->id) {

            $this->validate($request, [
                'password' => ['required', 'confirmed', Password::min(6)->numbers()->letters()->mixedCase()->symbols()],
            ]);

            if(!Auth::attempt(['email' => $user->email, 'password' => $request->old_password])) {

                Auth::logout();
                return redirect('/');
            }
        }
        else if(!auth()->user()->hasPermissionTo('edit_users')) {

            abort(401);
        }

        $user->password = bcrypt($request->password); //Secure
        $user->save();

        return redirect(route('profile', ['user'=>$user->id]));
    }
}
