<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use App\Models\User;

class PermissionController extends Controller
{
    /**
     *create role
     * @param  [string] name
     * @return [string] message
     */
    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $role = Role::create(['name' => $request->name]);

        return response()->json(['data' => $role]);
    }

    /**
     *create permission
     * @param  [string] name
     * @return [string] message
     */
    public function createPermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $permission = Permission::create(['name' => $request->name]);

        return response()->json(['data' => $permission]);
    }

    /**
     *assign role to user
     * @param  [string] name
     * @return [string] message
     */
    public function assignRoleToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::find($request->user_id);
        $role = Role::find($request->role_id);

        $user->assignRole($role);

        return response()->json(['data' => $user]);
    }

    /**
     *assign permission to role
     * @param  [string] name
     * @return [string] message
     */
    public function assignPermissionToRole(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $role = Role::find($request->role_id);
        $role->syncPermissions($request->permissions);

        return response()->json(['data' => $role]);
    }
}
