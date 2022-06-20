<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FavouriteBarns;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class FavouriteBarnController extends Controller
{

    /**
     * Add favorite barns
     */
    public function createFavoriteBarns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barn_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $favouriteBarn = new FavouriteBarns;

        if (FavouriteBarns::where('barn_id', $request->barn_id)->where('user_id', $request->user_id)->exists()) {
            return response()->json(['message' => 'Favourite barn already exists'], 400);
        }

        try {
            $favouriteBarn->barn_id = $request->barn_id;
            $favouriteBarn->user_id = $request->user_id;
            $favouriteBarn->save();
            return response()->json(['message' => 'Favourite barn created successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Favourite barn not created.'], 500);
        }
    }

    /**
     * Get favorite barns
     */
    public function getFavoriteBarns()
    {
        $favouriteBarns = FavouriteBarns::where('user_id', Auth::user()->id)
            ->with('barn')
            ->get();
        return  $favouriteBarns;
    }

    /**
     * Delete favorite barns
     */
    public function deleteFavoriteBarns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barn_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([$validator->errors()], 400);
        }
        $favouriteBarn = FavouriteBarns::where('barn_id', $request->barn_id)->where('user_id', $request->user_id)->first();
        if (!$favouriteBarn) {
            return response()->json(['message' => 'Favourite barn not found'], 400);
        }
        try {
            $favouriteBarn->delete();
            return response()->json(['message' => 'Favourite barn deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Favourite barn not deleted.'], 500);
        }
    }
}
