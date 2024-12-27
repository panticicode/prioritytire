<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use App\Helpers\UtilityHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use App\Models\Import;
use Auth;
use Gate;
use DB;

class ImportsController extends Controller
{
    protected $user;

    /**
     * Constructor for the class.
     * This constructor applies middleware that checks if the user is authenticated.
     * If the user is authenticated, it sets the `$user` property to the authenticated user
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware(function ($request, $next){
   
            if(Auth::check())
            {
                $this->user = Auth::user();
            }
            return $next($request);
        });
    }

    protected function config()
    {
        $columns = array_map(function ($column) {
            return $column === "user_id" ? "user" : $column;
        }, DB::getSchemaBuilder()->getColumnListing('imports'));

        $columnsToFormat = [
            'id',
            'user',
            'type',
            'filename',
            'status',
        ];

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

        $heads  = array_values(array_filter($formattedColumns));

        $imports = $this->imports('imports', $columnsToFormat);

        $config  = [
            'heads' => $heads,
            'data' => $imports->map(function ($import) {
                return [
                    $import->id,
                    $import->user->name,
                    $import->type,
                    $import->filename,
                    $import->status,
                    $import->action
                ];
            })->toArray(),
            'order'   => [[0, 'desc']],
            'columns' => array_merge(
                [
                    null,
                    null,
                    null,
                    null,
                    null,
                    ['orderable' => false], 
                ]
            )
        ];
        
        return $config;
    }

    /**
     * Retrieve imports based on user role.
     *
     * This method retrieves a list of imports with specific fields, such as import ID, user, type, filename, 
     * and status. If the user is an admin, 
     * it retrieves all imports. If the user is not an admin, only imports associated with users will be returned.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */

    protected function imports($model, $columns)
    {  
        $select = UtilityHelper::model($model)->formatColumnsForSelect($columns);
        $query  = UtilityHelper::model($model)::with('user')->select($select);

        if (!$this->user->isAdmin()) 
        {
            $query->whereHas('users', function($q){
                $q->where('id', $this->user->id);
            });
        }

        return $query->get();
    }

    public function index()
    {
        abort_if(Gate::denies('imports_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $config = $this->config();
        $heads  = ['id', 'user', 'type', 'row', 'column', 'value', 'message'];
        return view('dashboard.imports.index', compact('config', 'heads'));
    }

    public function show(Request $request, Import $import)
    {
        if($request->ajax())
        {
            $logs = $import->logs()
                        ->get()->map(function($log){
                return [
                    'id'      => $log->id,
                    'user'    => $log->user->email,
                    'type'    => $log->type,
                    'row'     => $log->row,
                    'column'  => $log->column,
                    'value'   => $log->value,
                    'message' => $log->message  
                ];
            });

            return response()->json($logs);
        }
    }
}
