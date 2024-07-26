<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    public function allServices(){
        return response()->json(Service::all());
    }

    public function allActiveServices(){
        return response()->json(Service::where('status',1)->get());
    }

    public function addService(Request $request){
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'link' => 'required',
        ]);

        $service = new Service();
        $service->name=$request->name;
        $service->description=$request->description;
        $service->link=$request->link;
        $service->save();
        
        return response()->json([
            "message" => "Service Save Successfully",
            "service" => $service,
        ], 200);
    }

    public function getService($id){
        return response()->json(Service::findOrFail($id));
    }

    public function updateSerive(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $service = Service::findOrFail($id);
        $service->name=$request->name;
        $service->description=$request->description;
        $service->save();

        return response()->json(['message' => 'Service updated successfully', $service]);
    }
    
    
    public function updateSeriveStatus($id)
    {
        $service = Service::find($id);
        $service->status = $service->status ? 0 : 1;
        $service->save();
        return response()->json(Service::all());
    }
}
