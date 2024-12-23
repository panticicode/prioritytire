<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Dashboard\Permissions\CreatePermissionRequest;
use App\Http\Requests\Dashboard\Permissions\UpdatePermissionRequest;
use Illuminate\Support\Str;
use App\Models\Permission;
use Auth;
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
                $this->user   = Auth::user();
                $this->config = $this->config();
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
            'label' => 'Actions', 
            'no-export' => true, 
            'width' => 5, 
            'classes' => 'text-center'
        ];

        $heads = array_values(array_filter($formattedColumns));

        $permissions = Permission::select('id', 'name', 'description', 'created_at')->get();

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
                    ['orderable' => false], 
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
        $config = $this->config;
        return view('dashboard/permissions/index', compact('config'));
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
               
                $permission = Permission::create($data);

                return response()->json([
                    'status'  => 200,
                    'data'    => [
                        $permission->checkbox,
                        $permission->id,
                        $permission->name,
                        $permission->description,
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
            return [
                'checkbox'    => $permission->checkbox,
                'id'          => $permission->id,
                'name'        => $permission->name,
                'description' => $permission->description,
                'action'      => $permission->action
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

                return response()->json([
                    'status'  => 200,
                    'data'    => [
                        $permission->checkbox,
                        $permission->id,
                        $permission->name,
                        $permission->description,
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
                'message' => "Selected permissions deleted successfully"
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
