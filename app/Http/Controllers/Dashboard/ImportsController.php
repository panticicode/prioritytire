<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ImportLog;
use Auth;
use Gate;

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

    public function index()
    {
        abort_if(Gate::denies('imports_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return view('dashboard.imports.index');
    }
}
