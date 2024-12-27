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

    /**
     * Configure column headers and data for displaying imports.
     *
     * This method generates a configuration array for displaying import records in a tabular format. It retrieves 
     * the column names from the 'imports' table and formats the column headers based on predefined rules. The method 
     * also fetches the import data and prepares it for display.
     *
     * @return array The configuration array containing column headers, data, sorting order, and column attributes.
     */

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

    /**
     * Display the list of imports.
     *
     * This method checks if the user has permission to access import records. If the user has access, it retrieves 
     * the configuration for displaying the imports and sets the table headers. It then returns the view for displaying 
     * the imports.
     *
     * @return \Illuminate\View\View The view displaying the list of imports.
     * @throws \Illuminate\Auth\Access\AuthorizationException If the user does not have access.
     */

    public function index()
    {
        abort_if(Gate::denies('imports_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $config = $this->config();
        $heads  = ['id', 'user', 'type', 'row', 'column', 'value', 'message'];
        return view('dashboard.imports.index', compact('config', 'heads'));
    }

    /**
     * Show the logs for a specific import.
     *
     * This method retrieves the logs for a specific import record. If the request is made via AJAX, it returns the logs 
     * in JSON format.
     *
     * @param \Illuminate\Http\Request $request The current HTTP request instance.
     * @param \App\Models\Import $import The import record for which logs are being retrieved.
     * @return \Illuminate\Http\JsonResponse The logs in JSON format if the request is AJAX.
     */
    
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
