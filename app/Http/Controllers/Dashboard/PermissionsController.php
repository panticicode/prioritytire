<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Auth;

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
        $heads = [
            'ID',
            'Name',
            ['label' => 'Phone', 'width' => 40],
            ['label' => 'Actions', 'no-export' => true, 'width' => 5],
        ];

        $button = array_map('trim', config('adminlte.plugins.Datatables.actions.buttons'));

        $config = [
            'heads' => $heads,
            'data' => [
                [22, 'John Bender', '+02 (123) 123456789', '<nobr>'.$button['edit'].$button['delete'].$button['view'].'</nobr>'],
                [19, 'Sophia Clemens', '+99 (987) 987654321', '<nobr>'.$button['edit'].$button['delete'].$button['view'].'</nobr>'],
                [3, 'Peter Sousa', '+69 (555) 12367345243', '<nobr>'.$button['edit'].$button['delete'].$button['view'].'</nobr>'],
            ],
            'order' => [[1, 'asc']],
            'columns' => [null, null, null, ['orderable' => false]],
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
