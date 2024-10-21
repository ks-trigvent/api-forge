<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Interfaces\AuthRepositoryInterface;
use App\Classes\ApiResponseClass as ResponseClass;
use App\Http\Resources\AuthResource;

class AuthController extends Controller
{
    private AuthRepositoryInterface $AuthRepositoryInterface;
    
    public function __construct(AuthRepositoryInterface $AuthRepositoryInterface)
    {
        $this->AuthRepositoryInterface = $AuthRepositoryInterface;
    }
    public function register(Request $request) {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        $createUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);
        return response()->json($createUser, 201);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function userProfile()
    {  
        $getCurrentUser = auth('api')->user()->id;
        $data = $this->AuthRepositoryInterface->getById($getCurrentUser);
        return ResponseClass::sendResponse(new AuthResource($data),'',200);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }
}
