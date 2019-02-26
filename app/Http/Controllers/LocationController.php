<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\JurisdictionType;
use App\Organisation;
use App\Location;
use Illuminate\Support\Facades\DB;
use Auth;
use Redirect;
use App\State;
use App\District;
use App\Taluka;
use App\Village;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() 
    {
       
        // $orgId = Auth::user()->org_id;
        // $organisation=Organisation::find($orgId);
        // $dbName=$organisation->name.'_'.$orgId;
        // \Illuminate\Support\Facades\Config::set('database.connections.'.$dbName, array(
        //     'driver'    => 'mongodb',
        //     'host'      => '127.0.0.1',
        //     'database'  => $dbName,
        //     'username'  => '',
        //     'password'  => '',  
        // ));
        // DB::setDefaultConnection($dbName);

        list($orgId, $dbName) = $this->connectTenantDatabase();

        $modules= DB::collection('modules')->get();

        $jurisdictions= JurisdictionType::all();

        return view('admin.locations.index',compact('jurisdictions','orgId'));
    }

    public function get(Request $request)
    {
        list($orgId, $dbName) = $this->connectTenantDatabase();

        $jurisdictions = explode(", ",strtolower($request->locationNames));

        $location = Location::where('jurisdiction_type_id',$request->jurisdictionTypeId)->get();
        // $location = Location::all();
        foreach($jurisdictions as $jurisdiction) {
                $jurisdiction = trim($jurisdiction);
                    $location = $location->load($jurisdiction);
        }
            
        return json_encode(['data' => $location ]);

        // return json_encode(['data' => Location::with('state', 'district', 'taluka', 'village')->get()]);
    }

    public function getDetailedLocation(Request $request)
    {
        list($orgId, $dbName) = $this->connectTenantDatabase();
        // list($orgId, $dbName) = $this->setDatabaseConfig();
        // DB::setDefaultConnection($dbName);

        $jurisdictions = explode(", ",$request->locationNames);
        $locationValues = array();

        foreach($jurisdictions as $jurisdiction) {
            $locationValues[strtolower($jurisdiction)] = DB::collection($jurisdiction)->get();
        }

        return json_encode( $locationValues );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Obtaining Organisation id of logged in user
        list($orgId, $dbName) = $this->connectTenantDatabase();
        $jurisdictions= JurisdictionType::all();

        return view('admin.locations.index',compact('jurisdictions','orgId','modules'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {    
        // $request contains _token, jurisdictionTypeId, vlaues of locations: location0,location1,location2,level0_location0, jurisdictionTypes e.g. state,unit, cluster, noOfJurisdictionTypes
        
        list($orgId, $dbName) = $this->setDatabaseConfig();
        DB::setDefaultConnection($dbName);

        $data = $request->all();

        if(isset($request->_id)) {
            $location = Location::find($request->_id);
            $location->jurisdiction_type_id = $request->jurisdiction_type_id;


            $fields = $request->except(['_token',"jurisdiction_type_id","createdBy","location_length",'_id']); 
            
            foreach($fields as $field=>$value)
                $location->$field = $value;
            
            $location->save();

            session()->flash('status', 'Location was edited!');
            return redirect()->route('locations.index',['orgId' => $orgId]);
        }

        if(isset($request->idForDelete)) {
            Location::find($request->idForDelete)->delete();
            session()->flash('status', 'Location was deleted!');
            return redirect()->route('locations.index',['orgId' => $orgId]);
        }
        
        Location::create($data);
        // {"jurisdiction_type_id":"5c6a331948b6714224001917","state_id":"5c66989ec7982d31cc6b86c3","district_id":"5c669d37c7982d31cc6b86ce","taluka_id":"5c66a53cd42f283b440013f8","village_id":"5c66a588d42f283b4400141b","created_by":"5c1cb0ce48b67128f4002ea7","updated_at":"2019-02-25 04:18:02","created_at":"2019-02-25 04:18:02","_id":"5c736c7a48b67128d00075e4"}

        session()->flash('status', 'Location was created!');
        return redirect()->route('locations.index',['orgId' => $orgId]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        //Breaks up url into an array of substrings using delimiter '/'
        $uri = explode("/",$_SERVER['REQUEST_URI']);
        // $orgId = $uri[1];
        $locationId = $uri[3];

        // Obtaining Organisation id of logged in user
        list($orgId, $dbName) = $this->connectTenantDatabase();

        $jurisdictions= JurisdictionType::all();

        $location = Location::find($locationId);

        // Converting $location->level from string to object
        $location->level = json_decode($location->level);
        
        return view('admin.locations.edit',compact('jurisdictions','location','orgId'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //Breaks up url into an array of substrings using delimiter '/'
        $uri = explode("/",$_SERVER['REQUEST_URI']);
        $locationId = $uri[3];

        // $request contains _method: PUT, _token, jurisdictionTypeId, values of locations: location0,location1,location2, jurisdictionTypes e.g. state,unit,cluster
        
        list($orgId, $dbName) = $this->connectTenantDatabase();

        // Converting the string $request->jurisdictionTypes to an array of substrings using delimiter ','
        $jurisdictionTypes = explode(',',$request->jurisdictionTypes);
        
        $location = Location::find($locationId);        
        $location->jurisdiction_type_id = $request->jurisdictionTypeId;

        // To create a collection of levels, e.g. { state:Goa, district:North Goa, taluka:Tiswadi }
        $arr = [];

        for($j = 0; $j<$request->noOfJurisdictionTypes; $j++)
        {
            $i = 0;
        foreach($jurisdictionTypes as $type)
        {
            $level = "level".$j."_location".$i;
            // e.g. $arr['state'] = 'Goa'
            $arr[$j][$type] = $request->$level;           
            $i = $i+1;
        }       
        }

        // Converting $arr to string
        $location->level = json_encode($arr);
        $location->save();

        session()->flash('status', 'Location was edited!');
        return redirect()->route('locations.index',['orgId' => $orgId]);   
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Breaks up url into an array of substrings using delimiter '/'
        $uri = explode("/",$_SERVER['REQUEST_URI']);
        // $orgId = $uri[1];
        $locationId = $uri[3];
        // Obtaining Organisation id of logged in user
        list($orgId, $dbName) = $this->connectTenantDatabase();

        Location::find($locationId)->delete();
        
        // Redirects back to index page i.e. the listing of locations
        return Redirect::back()->withMessage('Location Deleted');     
    }
}
