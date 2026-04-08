<?php

namespace App\Http\Controllers;

use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request): UsuarioResource
    {
        $request->user()->load('rol');

        return new UsuarioResource($request->user());
    }
}
