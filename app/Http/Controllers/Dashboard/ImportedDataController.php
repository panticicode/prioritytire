<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use App\Helpers\UtilityHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\User;
use Auth;
use Gate;
use DB;

class ImportedDataController extends Controller
{
    protected $user;
    protected $config;

    /**
     * Constructor to initialize middleware for setting user and configuration.
     *
     * This constructor applies middleware to handle actions before processing the request. 
     * It checks if the user is authenticated using `Auth::check()`. If the user is logged in, 
     * it sets the `user` property to the currently authenticated user (`Auth::user()`). 
     * Additionally, it sets the `config` property by calling a method to retrieve configuration 
     * based on the `model` and `type` parameters from the current route.
     *
     * The middleware ensures that user data and configuration are available for the controller's 
     * subsequent actions, based on the current request.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware(function ($request, $next){
            if(Auth::check())
            {
                $this->user   = Auth::user();
                $this->config = $this->config($request->route('model'), $request->route('type'));
            }
            return $next($request);
        });
    }

    /**
     * Get columns to format based on the specified model and type.
     *
     * This method returns an array containing the columns that need to be formatted for the specified model and type. 
     * It supports dynamic column selection for different models including items, clients, sales, and a default model.
     * Additionally, if the model is 'clients_and_sales', it uses the type to determine the columns.
     *
     * @param string $model The name of the model for which the columns are retrieved.
     * @param string $type The type used to determine columns when model is 'clients_and_sales'.
     * @return array An array containing the columns to format and the columns retrieved from the database schema.
     */

    protected function columnsToFormat($model, $type)
    {
        if($model === 'clients_and_sales')
        {
            $model = $type;
        }
        switch($model)
        {
            case('items'):
                    $columns = [
                        'item_id',
                        'name',
                        'category',
                        'price',
                        'stock'
                    ];
                break; 
             case('clients'):  
                    $columns = [
                        'client_id',
                        'name',
                        'email',
                        'phone',
                    ];
                break;       
            case('sales'):  
                    $columns = [
                        'sale_id',
                        'client_id',
                        'sale_date',
                        'total',
                    ];
                break;  
            default:
                    $columns = [
                        'order_date',
                        'channel',
                        'sku',
                        'item_description',
                        'origin',
                        'so_num',
                        'cost',
                        'shipping_cost',
                        'total_price'
                    ];
                break;     
        }
       
        return [
            'columns' => $columns,
            'db'      => DB::getSchemaBuilder()->getColumnListing($model)
        ];
    }
    
    /**
     * Configure column headers and data for displaying models.
     *
     * This method generates a configuration array for displaying data in a tabular format. It retrieves the column 
     * names from the specified model and formats the column headers based on predefined rules. The method also 
     * fetches the model data and prepares it for display. If the user lacks certain permissions, the Actions column 
     * is removed from the configuration.
     *
     * @param string $model The name of the model for which the configuration is generated.
     * @param string $type The type used for determining dynamic columns when the model is 'clients_and_sales'.
     * @return array The configuration array containing column headers, data, sorting order, and column attributes.
     */

    protected function config($model, $type)
    {   
        $columnsToFormat = $this->columnsToFormat($model, $type)['columns'];

        $columns         = $this->columnsToFormat($model, $type)['db'];


        $formattedColumns = array_map(function ($column) use ($columnsToFormat) {
            if (in_array($column, $columnsToFormat)) 
            {
                switch ($column) 
                {
                    case ('sku'):
                            return [
                                'label' => Str::upper($column)
                            ]; 
                        break;
                    case ('so_num'):
                            return [
                                'label' => Str::upper( Str::replace('_num', '#', $column) )
                            ];
                        break;
                    
                    default:
                            return [
                                'label' => Str::title( Str::replace('_', ' ', $column) )
                            ];
                        break;
                }
                return $column;
            }
        }, $columns);
   
        $formattedColumns[] = [
            'label' => 'Actions', 
            'no-export' => true, 
            'width' => 5, 
            'classes' => 'text-center'
        ];

        $heads  = array_values(array_filter($formattedColumns));

        $result = $this->model($model, $type, array_merge(['id'], $columnsToFormat));
       
        $config = [
            'heads' => $heads,
            'data' => $result->map(function ($model) use ($columnsToFormat) {
                $rowData = [];
                foreach ($columnsToFormat as $column) {
                    $rowData[] = $model->$column;
                }
                $rowData[] = $model->action; // Dodajemo akciju na kraju
                return $rowData;
            })->toArray(),
            'order'   => [[0, 'desc']],
            'columns' => array_merge(
                array_fill(0, count($columnsToFormat), null),
                [['orderable' => false]]
            )
        ];

        if(
            !Gate::check('imported_data_show')   && 
            !Gate::check('imported_data_delete') && 
            !Gate::check('import_' . $model . '_access')
        )
        {
            array_pop( $config['heads'] );
            array_pop( $config['columns'] );
        }


        return $config;
    }

    /**
     * Retrieve orders based on user role.
     *
     * This method retrieves a list of orders with specific fields, such as order ID, order date, channel, SKU, 
     * item description, origin, sales order number, cost, shipping cost, and total price. If the user is an admin, 
     * it retrieves all orders. If the user is not an admin, only orders associated with users will be returned.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */

    protected function model($model, $type, $columns)
    {   
        if($model === 'clients_and_sales')
        {
            $model = $type;
        }

        $query = UtilityHelper::model($model)::select($columns);

        if (!$this->user->isAdmin()) 
        {
            $query->whereHas('users', function($q){
                $q->where('id', $this->user->id);
            });
        }

        return $query->get();
    }

    /**
     * Display the imported data.
     *
     * This method checks if the user has permission to access imported data. If the user has access, it retrieves 
     * the configuration for displaying the orders, the current user, and sets the table headers. It then returns 
     * the view for displaying the imported data.
     *
     * @param string $model The name of the model for which the data is being displayed.
     * @param string $type The type of data being displayed.
     * @return \Illuminate\View\View The view displaying the imported data.
     * @throws \Illuminate\Auth\Access\AuthorizationException If the user does not have access.
     */

    public function index($model, $type)
    {   
        abort_if(Gate::denies('imported_data_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $config = $this->config($model, $type);
        $user   = $this->user;
        $heads  = ['id', 'import', 'row', 'column', 'old value', 'new value'];

        $model = Str::replace('_', ' ', Str::title($model));
        $type = Str::title($type);

        return view('dashboard.imported-data.index', compact('config', 'user', 'model', 'type', 'heads'));
    }

    /**
     * Show the audit logs for a specific record.
     *
     * This method retrieves the audit logs for a specific record in the specified model. If the request is made via 
     * AJAX, it returns the audit logs in JSON format.
     *
     * @param \Illuminate\Http\Request $request The current HTTP request instance.
     * @param string $model The name of the model containing the record.
     * @param string $type The type of data being displayed.
     * @param int $id The ID of the record for which audit logs are being retrieved.
     * @return \Illuminate\Http\JsonResponse The audit logs in JSON format if the request is AJAX.
     */

    public function show(Request $request, $model, $type, $id)
    {
        if($request->ajax())
        {
            $audits = UtilityHelper::model($model)->find($id)->audits()
                        ->get()->map(function($audit){
                return [
                    'id'        => $audit->id,
                    'import'    => $audit->import->filename,
                    'row'       => $audit->row,
                    'column'    => $audit->column,
                    'old_value' => $audit->old_value,
                    'new_value' => $audit->new_value  
                ];
            });

            return response()->json($audits);
        }
    }

    /**
     * Delete a specific record.
     *
     * This method deletes a specific record in the specified model. If the deletion is successful, it returns a 
     * JSON response with a success message. If an error occurs during deletion, it logs the error and returns a 
     * JSON response with an error message.
     *
     * @param string $model The name of the model containing the record to be deleted.
     * @param string $type The type of data being deleted.
     * @param int $id The ID of the record to be deleted.
     * @return \Illuminate\Http\JsonResponse The result of the delete operation.
     */
    
    public function destroy($model, $type, $id)
    {
        try {
            UtilityHelper::model($model)->find($id)->delete();

            return response()->json([
                'status'  => 200,
                'theme'   => 'success',
                'message' => 'Record deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Data Import deleting failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while deleting the Record.',
            ]);
        }
    }
}
