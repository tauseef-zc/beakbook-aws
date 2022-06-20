<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarnResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Barn;

class BarnController extends Controller
{

    public function getBarnList($farmId)
    {
        $barns = Barn::where('farm_id', $farmId)
            ->with('farm')
            ->get();
        return  $barns;
    }

    /**
     * Create barn
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @return [string] message
     */
    public function createBarn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required|max:255',
            'name' => 'required|max:255',
            'farm_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $barn = new Barn;

        if (Barn::where('name', $request->name)->where('farm_id', $request->farm_id)->exists()) {
            return response()->json(['message' => 'Barn already exists'], 400);
        }

        try {
            $barn->location = $request->location;
            $barn->name = $request->name;
            $barn->farm_id = $request->farm_id;
            $barn->save();
            return response()->json(['message' => 'Barn created successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Barn not created.'], 500);
        }
    }

    /**
     * Get barn list
     *
     * @param  [string] searchText
     * @param  [string] sortBy
     */
    public function getBarn(Request $request)
    {

        $searchText = $request->input('searchText');
	$farmId = $request->input('farmId');


        $barns = Barn::with('farm')
            ->with(['cycles' => function ($query) {
                $query->orderBy('starting_date', 'desc');
            }])
            ->with(['cycles.barnStatistics' => function ($query) {
                $query->orderBy('timestamp', 'desc');
            }])
            ->with('favouriteBarns');
        if (!empty($searchText)) {
            $barns = $barns->where('name', 'like', '%' . $searchText . '%')
                ->orWhereRelation('farm', function ($query) use ($searchText) {
                    $query->where('name', 'like', '%' . $searchText . '%');
                });
        }
	
		$barns->where('farm_id', $farmId);
        $barns->orderBy('id', 'desc');
        $barns = $barns->paginate(12);
  
        return BarnResource::collection($barns);
    }

    /**
     * update barn
     * @param  [int] id
     * @param  [string] name
     */
    public function updateBarn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $barn = Barn::find($request->id);
        if (!$barn) {
            return response()->json(['message' => 'Barn not found'], 404);
        }

        if (Barn::where('name', $request->name)->where('id', '!=', $request->id)->exists()) {
            return response()->json(['message' => 'Barn already exists'], 400);
        }

        try {
            $barn->name = $request->name;
            $barn->save();
            return response()->json(['message' => 'Barn updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Barn not updated.'], 500);
        }
    }
}
