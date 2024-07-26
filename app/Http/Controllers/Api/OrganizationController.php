<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;

class OrganizationController extends Controller
{
    public function allOrgs(){
        return response()->json(Organization::all());
    }

    public function allActiveOrgs(){
        return response()->json(Organization::where('status',1)->get());
    }

    public function addOrg(Request $request){
        $request->validate([
            'name' => 'required',
            'number' => 'required',
            'street' => 'required',
        ]);

        $org = new Organization();
        $org->name=$request->name;
        $org->number=$request->number;
        $org->street=$request->street;
        if($request->prompt){
            $org->prompt=$request->prompt;
        }
        $org->save();
        
        return response()->json([
            "message" => "Organization Save Successfully",
            "org" => $org,
        ], 200);
    }

    public function getOrg($id){
        return response()->json(Organization::findOrFail($id));
    }

    public function updateOrg(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'number' => 'required',
            'street' => 'required',
        ]);

        $org = Organization::findOrFail($id);
        $org->name=$request->name;
        $org->number=$request->number;
        $org->street=$request->street;
        $org->prompt=$request->prompt;
        $org->save();

        return response()->json(['message' => 'Organization updated successfully', $org]);
    }
    
    
    public function updateOrgStatus($id)
    {
        $org = Organization::find($id);
        $org->status = $org->status ? 0 : 1;
        $org->save();
        return response()->json(Organization::all());
    }
}