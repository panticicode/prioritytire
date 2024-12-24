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
                                        $query->whereNotNull('parent_id'); 
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

        if(!Gate::check('permission_view') && !Gate::check('permission_edit') && !Gate::check('permission_delete'))
        {
            array_pop( $config['heads'] );
            array_pop( $config['columns'] );
        }

        return $config;
    }
    
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

        $users = User::whereNotNull('parent_id')
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
     */
    public function index()
    {
        $config             = $this->config;
        $assignPermissions  = $this->users("bulkAssignPermission");
        $removePermissions  = $this->users("bulkRemovePermission");
        return view('dashboard/permissions/index', compact('config', 'assignPermissions', 'removePermissions'));
    }

    /**
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(Request $request, Permission $permission)
    {
        if($request->ajax())
        {
            $users = $permission->users()
                        ->whereNotNull('parent_id')
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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

    private function performAction($permission, $newUserIds, $action)
    {
        $permission->users()->$action($newUserIds);
    }
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
