<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Dashboard\Users\CreateUserRequest;
use App\Http\Requests\Dashboard\Users\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Auth;
use DB;

class UsersController extends Controller
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
        $columns = DB::getSchemaBuilder()->getColumnListing('users');

        $columnsToFormat = ['id', 'name', 'email'];

        $formattedColumns = array_map(function ($column) use ($columnsToFormat) {
            if (in_array($column, $columnsToFormat)) 
            {
                if ($column === 'id') 
                {
                    return [
                        'label' => Str::upper($column)
                    ]; 
                }
                return [
                    'label' => Str::title( $column )
                ];
            }
        }, $columns);

        $formattedColumns[] = ['label' => 'Actions', 'width' => 5, 'classes' => 'text-center'];

        $heads = array_values(array_filter($formattedColumns));

        $users = User::whereNotNull('parent_id')
                        ->where('id', '!=', $this->user->id)
                        ->select('id', 'name', 'email', 'created_at')->get();
            
        $config = [
            'heads' => array_merge(
                [[
                    'label' => '<input type="checkbox" id="bulk" />', 
                    'width' => 1, 
                    'classes' => 'text-center'
                ]], $heads
            ),
            'data' => $users->map(function ($user) {
                return [
                    $user->checkbox, 
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->action,
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
        return view('dashboard/users/index', compact('config'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request)
    {
        if($request->ajax())
        {
            try {
                $data = $request->only(['name', 'email', 'password']);
                $data['parent_id'] = $this->user->id;
                $data['password']  = Hash::make($data['password']);
       
                $user = User::create($data);
                //return redirect()->back()->with('success', 'New User Created');

                return response()->json([
                    'status'  => 200,
                    'data'    => $user,
                    'theme'   => 'success',
                    'message' => 'New User Created',
                ]);
            } catch (Exception $e) {
                \Log::error('User creation failed: ' . $e->getMessage());
                return response()->json([
                    'status'  => 500,
                    'theme'   => 'error',
                    'message' => 'An error occurred while creating the user.',
                ]);
            }
        } 
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        if($request->ajax())
        {
            return $user;
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        if($request->ajax())
        {
            try {
                $data = $request->only(['name', 'email', 'password']);

                if($request->filled('password'))
                {
                    $data['password']  = Hash::make($data['password']);
                }
                else
                {
                    unset($data['password']);
                }
                
                $user->update($data);

            } catch (Exception $e) {
                
            }
        }
        return response()->json([
            'status'  => 200,
            'data'    => $user,
            'theme'   => 'success',
            'message' => 'User Updated',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();

            return response()->json([
                'status'  => 200,
                'theme'   => 'success',
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('User deleting failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while deleting the user.',
            ]);
        }
    }

    public function bulk_delete($ids)
    {
        try {
            $ids = explode(',', $ids);  
            $users = User::whereIn('id', $ids)->get();

            foreach($users as $user)
            {
                $user->delete();
            }

            return response([
                'status' => 200,
                'theme'   => 'success',
                'message' => "Selected Users deleted successfully"
            ]);
        } catch (Exception $e) {
             \Log::error('User bulk deleting failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'theme'   => 'error',
                'message' => 'An error occurred while bulk deleting the user.',
            ]);
        }
    }
}
