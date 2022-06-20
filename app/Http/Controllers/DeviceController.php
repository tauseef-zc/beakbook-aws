<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\DeviceResource;

class DeviceController extends Controller
{
    /**
     * Create device
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @return [string] message
     */
    public function createDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'serial_number' => 'required|max:25',
            'barn_id' => 'required|integer',
            'company_id' => 'required|integer',
            'aws_region' => 'required|max:25',
            'status' => 'required|max:25',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 400);
        }
        $device = new Device;

        if (Device::where('serial_number', $request->serial_number)->exists()) {
            return response()->json(['message' => 'Device already exists'], 400);
        }

        try {
            $device->serial_number = $request->serial_number;
            $device->firmware_version = $request->firmware_version;
            $device->hardware_version = $request->hardware_version;
            $device->aws_region = $request->aws_region;
            $device->status = $request->status;
            $device->uptime = $request->uptime;
            $device->barn_id = $request->barn_id;
            $device->company_id = $request->company_id;
            $device->comment = $request->comment;
            $device->is_connected = $request->is_connected;
            $device->section_id = $request->section_id;
            $device->save();
            return response()->json(['message' => 'Device created successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Device not created.'], 500);
        }
    }

    /**
     * Get device list
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @return [string] message
     */
    public function getDeviceDetails(Request $request)
    {
        $searchText = $request->input('searchText');
	  $companyId = $request->input('companyId');
        $devices = Device::where('company_id', $companyId);  
        if (!empty($searchText)) {
            $devices = $devices->where('serial_number', 'like', '%' . $searchText . '%');
        }	
        $devices = $devices->paginate(12);        
        return  DeviceResource::collection($devices);
    }
}
