<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Dashboard\Users\CreateUserRequest;
use App\Http\Requests\Dashboard\Users\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use App\Models\User;
use Auth;
use Gate;
use DB;

class UsersController extends Controller
{
    protected $user;
    protected $config;

    /**
     * Constructor for the class.
     * This constructor applies middleware that checks if the user is authenticated.
     * If the user is authenticated, it sets the `$user` property to the authenticated user and
     * loads the configuration into the `$config` property.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware(function ($request, $next){
   
            if(Auth::check())
            {
                $this->user   = Auth::user();
                $this->config = $this->config();
            }
            return $next($request);
        });
    }

    /**
     * Generate configuration for user listing table.
     * This method fetches columns from the 'users' table, formats them based on a predefined list, 
     * and returns a configuration array that includes table headers, user data, and specific column options.
     *
     * The method also checks user permissions and adjusts the configuration to include or exclude 
     * actions like view, edit, or delete based on the user's gate permissions.
     *
     * @return array The configuration array used to render the user listing.
     */

    protected function config()
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('users');

        $columnsToFormat = ['id', 'name', 'email'];

        $formattedColumns = array_map(function ($column) use ($columnsToFormat) {
            if (in_array($column, $columnsToFormat)) 
            {
                if ($column === 'id') 
                {
                    return [
                        'label' => Str::upper($column),
                        'width' => 5, 
                    ]; 
                }
                return [
                    'label' => Str::title( $column )
                ];
            }
        }, $columns);

        $formattedColumns[] = [
            'label' => 'Actions', 
            'no-export' => true, 
            'width' => 5, 
            'classes' => 'text-center'
        ];

        $heads = array_values(array_filter($formattedColumns));
       
        $users = User::whereNotNull('parent_id')
                        ->where('id', '!=', $this->user->id)
                        ->select('id', 'name', 'email', 'created_at')->get();
            
        $config = [
            'heads' => array_merge(
                [[
                    'label' => '<input type="checkbox" id="bulk" />', 
                    'no-export' => true, 
                    'width' => 1, 
                    'classes' => 'text-center'
                ]], $heads
            ),
            'data' => $users->map(function ($user) {
                return [
                    $user->checkbox, 
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->action,
                ];
            })->toArray(),
            'order'   => [[1, 'desc']],
            'columns' => array_merge(
                [['orderable' => false]], 
                [
                    null,
                    null,
                    null,
                    ['orderable' => false], 
                ]
            ),
            'with-buttons' => Gate::check('users_export') ? 'with-buttons' : null
        ];

        if(!Gate::check('user_view') && !Gate::check('user_edit') && !Gate::check('user_delete'))
        {
            array_pop( $config['heads'] );
            array_pop( $config['columns'] );
        }
        return $config;
    }
    
    /**
     * Display a listing of the users.
     * This method checks if the current user has access to the 'users_access' permission.
     * If access is denied, it aborts with a 403 Forbidden response.
     * It retrieves the user listing configuration and the current authenticated user,
     * then returns a view to display the user listing with the necessary data.
     *
     * @return \Illuminate\View\View The view displaying the user listing with the configuration and user data.
     */

    public function index()
    {   
        abort_if(Gate::denies('users_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $config = $this->config;
        $user   = $this->user;
        return view('dashboard/users/index', compact('config', 'user'));
    }

    /**
     * Store a newly created user in the database.
     * 
     * This method handles the creation of a new user. It checks if the request is an AJAX request, validates the data, hashes the password,
     * and creates the user in the database. If the creation is successful, a JSON response with the user data is returned.
     * In case of failure, an error response is sent back with an appropriate message.
     *
     * @param \App\Http\Requests\CreateUserRequest $request The incoming request containing user data.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response containing the status, data, and message.
     */

    public function store(CreateUserRequest $request)
    {
        if($request->ajax())
        {
            try {
                $data = $request->only(['name', 'email', 'password']);
                $data['parent_id'] = $this->user->id;
                $data['password']  = Hash::make($data['password']);
       
                $user = User::create($data);
                //return redirect()->back()->with('success', 'New User Created');

                return response()->json([
                    'status'  => 200,
                    'data'    => [
                        $user->checkbox,
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->action
                    ],
                    'theme'   => 'success',
                    'message' => 'New User Created',
                ]);
            } catch (Exception $e) {
                \Log::error('User creation failed: ' . $e->getMessage());
                return response()->json([
                    'status'  => 500,
                    'theme'   => 'error',
                    'message' => 'An error occurred while creating the user.',
                ]);
            }
        } 
    }

    /**
     * Display the specified user and their associated permissions.
     * 
     * This method checks if the request is an AJAX request and then retrieves the user and their permissions. 
     * It returns the user data along with the associated permissions in the response.
     *
     * @param \Illuminate\Http\Request $request The incoming request.
     * @param \App\Models\User $user The user to be displayed.
     * 
     * @return array The user data and associated permissions.
     */

    public function show(Request $request, User $user)
    {
        if($request->ajax())
        {
            $permissions = $user->permissions()
                        ->get()->map(function($permission){
                return [
                    'id'    => $permission->id,
                    'name'  => $permission->name,
                ];
            });

            return [
                'data' => [
                    'checkbox' => $user->checkbox,
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'action'   => $user->action
                ],
                'permissions' => $permissions
            ];
        }
    }

    /**
     * Update the specified user in the database.
     * 
     * This method handles the update of an existing user. It checks if the request is an AJAX request, validates the data,
     * updates the user's details, and returns a JSON response with the updated user data. If the user password is provided, it is hashed.
     * In case of failure, an error response is returned.
     *
     * @param \App\Http\Requests\UpdateUserRequest $request The incoming request containing updated user data.
     * @param \App\Models\User $user The user to be updated.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response containing the status, updated data, and message.
     */

    public function update(UpdateUserRequest $request, User $user)
    {
        if($request->ajax())
        {
            try {
                $data = $request->only(['name', 'email', 'password']);

                if($request->filled('password'))
                {
                    $data['password']  = Hash::make($data['password']);
                }
                else
                {
                    unset($data['password']);
                }
                
                $user->update($data);

                return response()->json([
                    'status'  => 200,
                    'data'    => [
                        $user->checkbox,
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->action
                    ],
                    'theme'   => 'success',
                    'message' => 'User Updated',
                ]);

            } catch (Exception $e) {
                \Log::error('User updating failed: ' . $e->getMessage());

                return response()->json([
                    'status'  => 500,
                    'theme'   => 'error',
                    'message' => 'An error occurred while updating the user.',
                ]);
            }
        }
    }

    /**
     * Remove the specified user from the database.
     * 
     * This method handles the deletion of a user from the database. It attempts to delete the user and returns a JSON response
     * indicating whether the operation was successful. If an error occurs, an error message is returned.
     *
     * @param \App\Models\User $user The user to be deleted.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the status and message of the operation.
     */

    public function destroy(User $user)
    {
        try {
            $user->delete();

            return response()->json([
                'status'  => 200,
                'theme'   => 'success',
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('User deleting failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while deleting the user.',
            ]);
        }
    }

    /**
     * Bulk delete users from the database.
     * 
     * This method handles the bulk deletion of users based on the provided user IDs. It iterates through the users and deletes
     * each one. If the operation is successful, a success message is returned; otherwise, an error message is provided.
     *
     * @param string $ids A comma-separated string of user IDs to be deleted.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the status and message of the bulk delete operation.
     */
    
    public function bulk_delete($ids)
    {
        try {
            $ids = explode(',', $ids);  
            $users = User::whereIn('id', $ids)->get();

            foreach($users as $user)
            {
                $user->delete();
            }

            return response([
                'status' => 200,
                'theme'   => 'success',
                'message' => "Selected Users deleted successfully"
            ]);
        } catch (Exception $e) {
             \Log::error('User bulk deleting failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while bulk deleting the user.',
            ]);
        }
    }
}
