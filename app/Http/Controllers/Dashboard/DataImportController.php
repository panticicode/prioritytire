<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Dashboard\DataImport\ImportRequest;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Response;
use App\Jobs\DataImportJob;
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

    /**
     * Retrieves the configuration for the import process.
     * If a specific type is provided, it returns the configuration for that type.
     * Otherwise, it returns the general configuration for orders.
     *
     * @param string|null $type The type of import configuration to retrieve.
     * @return array|false The configuration array or false if the type is not valid.
     */

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

    /**
     * Displays the data import page with the relevant configuration.
     * Checks if the user has permission to access the page using the `Gate` facade.
     * 
     * @return \Illuminate\View\View The view for the data import page.
     */

    public function index()
    {
        abort_if(Gate::denies("data_import_access"), Response::HTTP_FORBIDDEN, "403 Forbidden");
        $config = $this->config;

        return view("dashboard.data-import.index", compact("config"));
    }

    /**
     * Handles the data import process by validating the selected import type,
     * processing the uploaded files, and dispatching a job to handle the import asynchronously.
     * After processing, the user is notified about the status of the import.
     *
     * @param \App\Http\Requests\Dashboard\DataImport\ImportRequest $request The incoming request containing the files to import and the selected import type.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the status of the import process, including success or failure messages.
     */

    public function import(ImportRequest $request)
    {
        $type   = $request->type;
        $config = $this->config($type);

        if (!$config) 
        {
            return response()->json([
                'message' => 'Invalid import type selected.',
                'errors' => [
                    'files' => ['Invalid import type selected.']
                ]
            ], 422);
        }

        $message = 'Import is in progress. You will be notified when it is complete.';

        foreach ($request->file("files") as $file) 
        {
            $tempPath = $file->store('temp');
            DataImportJob::dispatch($tempPath, $config, $this->user, $type, $message);
        }

        return response()->json([
            'status'  => 200,
            'theme'   => 'success',
            'message' => $message,
        ]);
    }
}
