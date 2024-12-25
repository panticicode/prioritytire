<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Dashboard\DataImport\ImportRequest;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Response;
use App\Models\Order;
use Auth;
use Gate;

class DataImportController extends Controller
{
    protected $user;

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

    protected function config($type = null)
    {
        if(is_null($type))
        {
            $config = config("imports.orders");
        }
        else
        {
            $config = config("imports.orders.files")[$type] ?? false;
        }
        return $config;
    }

    public function index()
    {
        abort_if(Gate::denies("data_import_access"), Response::HTTP_FORBIDDEN, "403 Forbidden");
        $config = $this->config;
       
        return view("dashboard.data-import.index", compact("config"));
    }

    public function import(ImportRequest $request)
    {
        $type   = $request->type;
        $config = $this->config($type);

        if (!$config) 
        {
            return redirect()->back()->withErrors(["files.*" => "Invalid import type selected."]);
        }

        $orderIds = [];

        foreach ($request->file("files") as $file) 
        {
            $filePath  = $file->getRealPath();
            $extension = $file->getClientOriginalExtension();
            $fileName  = $file->getClientOriginalName();

            if (!in_array($extension, ["csv", "xlsx"])) 
            {
                return redirect()->back()->withErrors(["files.*" => "Only CSV and XLSX files are allowed."]);
            }

            $data    = Excel::toArray([], $file)[0];
            $headers = $data[0];

            // Validate headers
            $requiredHeaders = array_keys($config["headers_to_db"]);

            foreach ($requiredHeaders as $header) 
            {
                if (!in_array($header, $headers)) 
                {
                    return redirect()->back()->withErrors(["files.*" => "Missing header: $header"]);
                }
            }

            // Validate and insert data
            foreach (array_slice($data, 1) as $row) 
            {
                $rowData = array_combine($headers, $row);
                $rules = [];
                foreach ($config['headers_to_db'] as $key => $field) 
                {
                    $rules[$key] = $field['validation'];
                }

                $validator = Validator::make($rowData, $rules);

                if ($validator->fails()) 
                {
                    return redirect()->back()->withErrors($validator->errors());
                }

                $updateKeys = $config['update_or_create'];
                $updateData = array_intersect_key($rowData, array_flip($updateKeys));
                $createData = array_diff_key($rowData, $updateData);

                $order      = Order::updateOrCreate($updateData, $createData);

                $orderIds[] = $order->id;
            }
        }

        $this->user->orders()->attach($orderIds, ['type' => $request->type]);

        return redirect()->back()->with("success", "The import process has been successfully completed!");
    }
}
