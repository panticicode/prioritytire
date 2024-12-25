<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
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

    protected function config($model, $type)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($model);

        $columnsToFormat = [
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
        
        $orders = $this->orders($model, array_merge(['id'], $columnsToFormat));
       
        $config = [
            'heads' => $heads,
            'data' => $orders->map(function ($order) {
                return [
                    $order->order_date,
                    $order->channel,
                    $order->sku,
                    $order->item_description,
                    $order->origin,
                    $order->so_num,
                    $order->cost,
                    $order->shipping_cost,
                    $order->total_price,
                    $order->action,
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
                    null,
                    null,
                    null,
                    null,
                    ['orderable' => false], 
                ]
            )
        ];

        if(!Gate::check('imported_data_show') && !Gate::check('imported_data_delete'))
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

    protected function orders($model, $columns)
    {
        $query = $this->model($model)::select($columns);

        if (!$this->user->isAdmin()) 
        {
            $query->whereHas('users');
        }

        return $query->get();
    }
    public function index($model, $type)
    {
        abort_if(Gate::denies('imported_data_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $config = $this->config($model, $type);
        $user   = $this->user;
        return view('dashboard.imported-data.index', compact('config', 'user', 'model', 'type'));
    }

    public function show($model, $type)
    {

    }

    public function destroy($model, $type, $id)
    {
        try {
            $this->model($model)->find($id)->delete();

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
    /**
     * Resolves and returns an instance of a model class.
     *
     * This method takes a model or model name as input and returns an instance of the corresponding model class.
     * 
     * - If the provided `$model` is an instance of a `Model`, it constructs the class name based on the model's class name
     *   and returns an instance of that class.
     * - If the provided `$model` is a string (assumed to be a model name), it converts the name to its singular form (if needed)
     *   and then constructs the class name accordingly, returning an instance of the model class.
     *
     * @param mixed $model The model instance or model name.
     * @return Model An instance of the corresponding model class.
     */
    protected function model($model): Model
    {
        if ($model instanceof Model) 
        {
            return App::make('App\\Models\\' . ucfirst(class_basename($model)));
        } 
        else 
        {
            return App::make('App\\Models\\' . ucfirst(Str::singular($model)));
        }
    }
}
