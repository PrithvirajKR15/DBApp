<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Store;
use App\Models\Role;
use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::where('name', '!=', 'User')->where('is_system', true)->get();
        $stores = Store::where('status', 'active')->get();
        return view('content.pages.users', compact('roles', 'stores'));
    }

    public function list(Request $request)
    {
        $query = User::with(['role', 'store'])->whereHas('role', function ($q) {
            $q->where('name', '!=', 'User');
        });

        // Search
        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%");
            });
        }


        // Status filter
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }


        // Sorting
        switch ($request->sort) {

            case 'oldest':
                $query->oldest();
                break;

            case 'name-asc':
                $query->orderBy('name', 'asc');
                break;

            case 'name-desc':
                $query->orderBy('name', 'desc');
                break;

            default:
                $query->latest();
        }


        $users = $query->paginate(10);


        return response()->json([
            'status' => true,

            'users' => $users->map(function ($user) {

                return [
                    'id' => $user->id,
                    'name' => trim($user->name),
                    'email' => $user->email,
                    'phone' => $user->mobile,
                    'store' => optional($user->store)->name ?? '-',
                    'role' => optional($user->role)->name ?? '-',
                    'joined' => $user->created_at->format('d M Y'),

                    'avatar' => $user->image
                        ? asset('storage/'.$user->image)
                        : asset('assets/img/avatars/1.png'),

                    'status' => $user->status,
                ];
            }),

            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
            'from' => $users->firstItem(),
            'to' => $users->lastItem(),
        ]);
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
    public function store(StoreUserRequest $request)
    {
        try {

            $this->userService->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'User created successfully'
            ]);

        } catch(\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ],500);

        }
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
    public function edit($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'user'=>[
                'id'=>$user->id,
                'name'=>$user->name,
                'email'=>$user->email,
                'mobile'=>$user->mobile,
                'dob'=>$user->dob,
                'gender'=>$user->gender,
                'address'=>$user->address,
                'role_id'=>$user->role_id,
                'store_id'=>$user->store_id,
                'image'=>$user->image,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        try {

            $user = User::findOrFail($id);


            $user->update([

                'name'=>$request->input('user-first-name').' '.$request->input('user-last-name'),

                'email'=>$request->input('user-email'),

                'mobile'=>$request->input('user-phone'),

                'dob'=>$request->input('user-dob'),

                'gender'=>$request->input('user-gender'),

                'address'=>$request->input('user-address'),

                'role_id'=>$request->input('user-role'),

                'store_id'=>$request->input('user-store'),

            ]);


            if($request->hasFile('user-avatar-file'))
            {

                $user->image = 
                    $request->file('user-avatar-file')
                    ->store('users','public');


                $user->save();

            }


            return response()->json([
                'status'=>true,
                'message'=>'User updated successfully'
            ]);


        } catch(\Exception $e) {


            return response()->json([

                'status'=>false,

                'message'=>$e->getMessage()

            ],500);

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function toggleStatus($id)
    {
        try {

            $user = User::findOrFail($id);


            $user->status = $user->status === 'Active'
                ? 'Inactive'
                : 'Active';


            $user->save();


            return response()->json([
                'status'=>true,
                'message'=>'User status updated successfully'
            ]);


        } catch(\Exception $e){

            return response()->json([
                'status'=>false,
                'message'=>$e->getMessage()
            ],500);

        }
    }
}
