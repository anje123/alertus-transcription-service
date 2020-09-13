<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'api_token' => Str::random(60),
        ]);
    }
    
    public function updateToken(Request $request)
    {
        $token = Str::random(60);

        $request->user()->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return ['token' => $token];
    }

    public function updateUser(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return ['response' => 'Validation Error'. $validator->errors()];
        }
        
        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->save();
        return response()->json($user, 200);

    }

}
