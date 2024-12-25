<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Dashboard\Permissions\CreatePermissionRequest;
use App\Http\Requests\Dashboard\Permissions\UpdatePermissionRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\User;
use Auth;
use Gate;
use DB;

class PermissionsController extends Controller
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
                $this->user    = Auth::user();
                $this->config  = $this->config();
            }
            return $next($request);
        });
    }

    /**
     * Generate configuration for displaying permissions table.
     * This method fetches the columns of the 'permissions' table, formats them, and creates a table configuration
     * for displaying the data. It also applies user permissions and dynamically adjusts the columns based on available permissions.
     *
     * @return array Configuration array for permissions table.
     */

    protected function config()
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('permissions');

        $columnsToFormat = ['id', 'name', 'description'];

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
            'label' => 'Users', 
            'width' => 1, 
        ];

        $formattedColumns[] = [
            'label' => 'Actions', 
            'no-export' => true, 
            'width' => 5, 
            'classes' => 'text-center'
        ];

        $heads = array_values(array_filter($formattedColumns));

        $permissions = Permission::select('id', 'name', 'description', 'created_at')
                                    ->withCount(['users' => function ($query) {
                                        $query->admin('is_admin', false); 
                                    }])->get();   

        $config = [
            'heads' => array_merge(
                [[
                    'label' => '<input type="checkbox" id="bulk" />', 
                    'no-export' => true, 
                    'width' => 1, 
                    'classes' => 'text-center'
                ]], $heads
            ),
            'data' => $permissions->map(function ($permission) {
                return [
                    $permission->checkbox, 
                    $permission->id,
                    $permission->name,
                    $permission->description,
                    $permission->users_count,// Exclude Admin for counting
                    $permission->action,
                ];
            })->toArray(),
            'order'   => [[1, 'desc']],
            'columns' => array_merge(
                [['orderable' => false]], 
                [
                    null,
                    null,
                    null,
                    null,
                    ['orderable' => false], 
                ]
            ),
            'with-buttons' => $this->user->can('permissions_export') ? 'with-buttons' : null
        ];

        if(!Gate::check('permission_show') && !Gate::check('permission_edit') && !Gate::check('permission_delete'))
        {
            array_pop( $config['heads'] );
            array_pop( $config['columns'] );
        }

        return $config;
    }
    
    /**
     * Generate configuration for displaying users table with permission options.
     * This method fetches the columns of the 'users' table, formats them, and creates a table configuration
     * for displaying user data. It also adjusts for bulk permission assignment or removal, and includes necessary access controls.
     *
     * @param string $params Parameter used to differentiate between bulk assignment and removal of permissions.
     * @return array Configuration array for users table.
     */

    protected function users($params)
    {
        abort_if(Gate::denies('permissions_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $columns = DB::getSchemaBuilder()->getColumnListing('users');

        $columnsToFormat = ['id', 'name', 'email'];

        $formattedColumns = array_map(function ($column) use ($columnsToFormat) {
            if (in_array($column, $columnsToFormat)) 
            {
                if ($column === 'id') 
                {
                    return [
                        'label' => Str::upper($column),
                        'width' => 1, 
                    ]; 
                }
                return [
                    'label' => Str::title( $column )
                ];
            }
        }, $columns);

        $heads = array_values(array_filter($formattedColumns));

        $users = User::admin('is_admin', false)
                        ->where('id', '!=', $this->user->id)
                        ->select('id', 'name', 'email', 'created_at')->get();
            
        $config = [
            'heads' => array_merge(
                [[
                    'label' => '<input type="checkbox" id="'. $params .'" />', 
                    'width' => 1
                ]], $heads
            ),
            'data' => $users->map(function ($user) use ($params) {

                if( $user->permissions->isEmpty() || $params === 'bulkAssignPermission' )
                {
                    $class   = '';
                }
                else
                {
                    $class   = ' bulkChecked'; 
                }
                return [
                    '<input type="checkbox" class="'. $params . $class .'" data-id="' . $user->id . '" />', 
                    $user->id,
                    $user->name,
                    $user->email,
                ];
            })->toArray(),
            'order'   => [[1, 'desc']],
            'columns' => array_merge(
                [['orderable' => false]], 
                [
                    null,
                    null,
                    null
                ]
            ),
        ];

        return $config;
    }
    
    /**
     * Display a listing of the resource.
     * This method combines the configuration for permissions and users, and passes it to the view for rendering.
     *
     * @return \Illuminate\View\View The view for displaying the permissions index page with the configured data.
     */
    public function index()
    {
        $config             = $this->config;
        $assignPermissions  = $this->users("bulkAssignPermission");
        $removePermissions  = $this->users("bulkRemovePermission");
        return view('dashboard/permissions/index', compact('config', 'assignPermissions', 'removePermissions'));
    }

    /**
     * Store a newly created permission in storage.
     * This method handles the creation of a new permission via an AJAX request, returning a JSON response
     * indicating success or failure. It includes the permission details in the response.
     *
     * @param CreatePermissionRequest $request The request containing the necessary data to create a permission.
     * @return \Illuminate\Http\JsonResponse The JSON response with the status, message, and data.
     */

    public function store(CreatePermissionRequest $request)
    {
        if($request->ajax())
        {
            try {
                $data = $request->only(['name', 'description']);
               
                $permission = Permission::withCount('users')->create($data);

                return response()->json([
                    'status'  => 200,
                    'data'    => [
                        $permission->checkbox,
                        $permission->id,
                        $permission->name,
                        $permission->description,
                        $permission->users_count ?? 0,
                        $permission->action
                    ],
                    'theme'   => 'success',
                    'message' => 'New Permission Created',
                ]);
            } catch (Exception $e) {
                \Log::error('Permission creation failed: ' . $e->getMessage());
                return response()->json([
                    'status'  => 500,
                    'theme'   => 'error',
                    'message' => 'An error occurred while creating the permission.',
                ]);
            }
        } 
    }

    /**
     * Display the specified permission resource.
     * This method retrieves and returns the details of a specified permission and the associated users who have the permission.
     * The data is returned in a JSON format for AJAX requests.
     *
     * @param Request $request The incoming request.
     * @param Permission $permission The permission instance to display.
     * @return array The response containing the permission details and users.
     */

    public function show(Request $request, Permission $permission)
    {
        if($request->ajax())
        {
            $users = $permission->users()
                        ->admin('is_admin', false)
                        ->get()->map(function($user){
                return [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email
                ];
            });

            return [
                'data' => [
                    'checkbox'    => $permission->checkbox,
                    'id'          => $permission->id,
                    'name'        => $permission->name,
                    'description' => $permission->description,
                    'action'      => $permission->action
                ],
                'users'    => $users
            ];
        }
    }

    /**
     * Update the specified permission resource in storage.
     * This method handles the updating of an existing permission via an AJAX request, returning a JSON response
     * indicating success or failure. The updated permission data is included in the response.
     *
     * @param UpdatePermissionRequest $request The request containing the data to update the permission.
     * @param Permission $permission The permission instance to update.
     * @return \Illuminate\Http\JsonResponse The JSON response with the status, message, and data.
     */

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        if($request->ajax())
        {
            try {
                $data = $request->only(['name', 'description']);

                $permission->update($data);

                $permission->loadCount('users');

                return response()->json([
                    'status'  => 200,
                    'data'    => [
                        $permission->checkbox,
                        $permission->id,
                        $permission->name,
                        $permission->description,
                        $permission->users_count ?? 0,
                        $permission->action
                    ],
                    'theme'   => 'success',
                    'message' => 'Permission Updated',
                ]);

            } catch (Exception $e) {
                \Log::error('Permission updating failed: ' . $e->getMessage());

                return response()->json([
                    'status'  => 500,
                    'theme'   => 'error',
                    'message' => 'An error occurred while updating the permission.',
                ]);
            }
        }
    }

    /**
     * Remove the specified permission from storage.
     * This method handles the deletion of a specified permission via an AJAX request, returning a JSON response
     * indicating success or failure.
     *
     * @param Permission $permission The permission instance to delete.
     * @return \Illuminate\Http\JsonResponse The JSON response with the status and message of the operation.
     */

    public function destroy(Permission $permission)
    {
        try {
            $permission->delete();

            return response()->json([
                'status'  => 200,
                'theme'   => 'success',
                'message' => 'Permission deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Permission deleting failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while deleting the permission.',
            ]);
        }
    }

    /**
     * Handle the action of attaching or detaching users to a permission.
     * This method checks if the selected action is to attach or detach the permission to/from users,
     * performs the action, and returns a response with the operation status and message.
     *
     * @param Permission $permission The permission to be assigned or removed from users.
     * @param array $existingUsers The users who already have the permission.
     * @param array $userIds The IDs of the users to be assigned or removed from the permission.
     * @param string $action The action to perform: either 'attach' or 'detach'.
     * @return \Illuminate\Http\Response The response with the status and message.
     */

    private function handleAction($permission, $existingUsers, $userIds, $action)
    {
        if($action === 'attach')
        {
            $newUserIds = array_diff($userIds, $existingUsers);

            if (empty($newUserIds)) 
            {
                return response([
                    'status'  => 409,
                    'theme'   => 'warning',
                    'message' => 'Permission ' . $permission->name . ' is already assigned to the selected users.'
                ]);
            }
            $message   = 'The selected permissions have been successfully assigned to the selected users.';
            $operation = '+';
        }
        else
        {
            $newUserIds = array_intersect($userIds, $existingUsers);

            if (empty($newUserIds)) 
            {
                return response([
                    'status'  => 409,
                    'theme'   => 'warning',
                    'message' => 'Permission ' . $permission->name . ' is already removed from the selected users.'
                ]);
            }
            $message   = 'The selected permissions have been successfully removed from the selected users.';
            $operation = '-';
        }

        $this->performAction($permission, $newUserIds, $action);

        return [
            'status'    => 200,
            'data'      => $this->config(),
            'operation' => $operation,
            'theme'     => 'success',
            'message'   => $message
        ];
    }

    /**
     * Perform the actual action of attaching or detaching users to/from the permission.
     * This method attaches or detaches the users from the permission based on the action provided.
     *
     * @param Permission $permission The permission to which users will be attached or detached.
     * @param array $newUserIds The IDs of the users to be attached or detached.
     * @param string $action The action to perform: either 'attach' or 'detach'.
     * @return void
     */

    private function performAction($permission, $newUserIds, $action)
    {
        $permission->users()->$action($newUserIds);
    }

    /**
     * Handle the bulk assignment or removal of permissions for users.
     * This method processes the assignment or removal of permissions to users in bulk.
     * It iterates over the permissions and calls handleAction for each permission.
     *
     * @param string $user_ids A comma-separated list of user IDs.
     * @param string $permission_ids A comma-separated list of permission IDs.
     * @param string $action The action to perform: either 'attach' or 'detach'.
     * @return \Illuminate\Http\JsonResponse The response with the status, theme, and message.
     */

    public function handle_user_permissions($user_ids, $permission_ids, $action)
    {
        try {
            $userIds       = explode(',', $user_ids);
            $permissionIds = explode(',', $permission_ids);

            $permissions   = Permission::with('users')->whereIn('id', $permissionIds)->get();

            foreach($permissions as $permission)
            {
                $existingUsers = $permission->users()
                                            ->whereIn('id', $userIds)
                                            ->pluck('id')
                                            ->toArray();

                $newUserIds = array_diff($userIds, $existingUsers);

                $response = $this->handleAction($permission, $existingUsers, $userIds, $action);
            }

            return response()->json($response);

        } catch (Exception $e) {
             \Log::error('Assign Permissions failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while assigning the permission.',
            ]);
        }
    }

    /**
     * Perform a bulk delete operation for selected permissions.
     * This method deletes the permissions specified by their IDs in bulk.
     *
     * @param string $ids A comma-separated list of permission IDs to delete.
     * @return \Illuminate\Http\Response The response with the status and message.
     */
    
    public function bulk_delete($ids)
    {
        try {
            $ids = explode(',', $ids);  
            $permissions = Permission::whereIn('id', $ids)->get();

            foreach($permissions as $permission)
            {
                $permission->delete();
            }

            return response([
                'status' => 200,
                'theme'   => 'success',
                'message' => 'Selected permissions deleted successfully'
            ]);
        } catch (Exception $e) {
             \Log::error('Permission bulk deleting failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while bulk deleting the permission.',
            ]);
        }
    }
}
