<?php

namespace App\Http\Controllers;
use App\Survey;
use App\Category;
use App\Organisation;
use App\Entity;
use App\Microservice;
use Maklad\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\SurveyResult;
use Illuminate\Http\Request;
use Auth;
use Redirect;

class SurveyController extends Controller
{

    public function saveSurveyForm(Request $request)
    {
        $r = json_decode($request->json,true);
        $k = null;

        foreach($r as $key=>$value)
        {
            if($key == 'title')
                $k = $value;
        }    
      
        
        list($orgId, $dbName) = $this->connectTenantDatabase($request->orgId);
       
        $id = DB::collection('surveys')->insertGetId(
            [
                'name' => $k ,
                'json' => $request->json,
                'creator_id' => $request->creator_id,
                'active' => $request->active,
                'editable' => $request->editable,
                'multiple_entry' => $request->multiple_entry,
                'assigned_roles' => $request->assigned_roles,
                'category_id' => $request->category_id,
                'project_id' => $request->project_id,
                'microservice_id' => $request->microservice_id,
                'entity_id' => $request->entity_id,
                'created_at' => \Carbon\Carbon::now()->getTimestamp()
            ]
        );

        foreach($id as $key=>$value)
            $survey_id = $value;

        return json_encode($survey_id);
     
    }
    public function setKeys()
    {
        $keys = array();

        //Breaks up url into an array of substrings using delimiter '/'
        $uri = explode("/",$_SERVER['REQUEST_URI']);
        $orgId = $uri[1];
        $survey_id = $uri[3];

        list($orgId, $dbName) = $this->connectTenantDatabase($orgId);

        //Returns fields _id,json of survey having survey id=$survey_id
        $survey = Survey::where('_id','=',$survey_id)->get(['json']);   

        // Converts json string to array
        $data = json_decode($survey[0]->json,true);     
          
        // Accessing the value of key pages
        $pages = $data['pages'];
        $numberOfKeys = 0;

        foreach($pages as $page)
        {
            // Accessing the value of key elements to obtain the names of the questions
            foreach($page['elements'] as $element)
            {
                $keys[] = $element['name'];
                $numberOfKeys++;
            }
        }

        $primaryKeySet = array();
        return view('admin.surveys.editKeys',compact('primaryKeySet','keys','numberOfKeys','orgId','survey_id'));
    }
    public function editKeys()
    {
         //Breaks up url into an array of substrings using delimiter '/'
        $uri = explode("/",$_SERVER['REQUEST_URI']);

        $keys = array();
        
        $orgId = $uri[1];
        $survey_id = $uri[3];
        list($orgId, $dbName) = $this->connectTenantDatabase($orgId);

        //Returns fields _id,primaryKeys,json of survey having survey id=$survey_id
        $survey = Survey::where('_id','=',$survey_id)->get(['form_keys','json']);

        //obtains only the primary keys from $survey as an array
        $primaryKeySet = $survey[0]->form_keys;

        // Converts json string to array
        $data = json_decode($survey[0]->json,true);        

        // Accessing the value of key pages
        $pages = $data['pages'];
        $numberOfKeys = 0;

        foreach($pages as $page)
        {
             // Accessing the value of key elements to obtain the names of the questions
            foreach($page['elements'] as $element)
            {
                $keys[] = $element['name'];
                $numberOfKeys++;
            }
        }
   
            return view('admin.surveys.editKeys',compact('primaryKeySet','keys','numberOfKeys','orgId','survey_id'));
    }

    public function storeKeys(Request $request)
    {
        $survey_id = $request->surveyID;

        //Returns $request->primaryKeys[]
        $primaryKeys = $request->except(['_token','surveyID']); 
        
        list($orgId, $dbName) = $this->connectTenantDatabase();
        if (!empty($primaryKeys)) {
            DB::collection('surveys')->where('_id',$survey_id)->update($primaryKeys);
        }

        //Redirects to index function
        return redirect($orgId . '/forms');
    }
    public function sendResponse(Request $request)
    {
        $uri = explode("/",$_SERVER['REQUEST_URI']);
        list($orgId, $dbName) = $this->connectTenantDatabase($uri[1]);
        DB::collection('survey_results')->insert([ 'survey_id'=>$request->surveyId,'user_id'=>$request->userId,'json_response'=>$request->jsonString]);

        return null;
    }
    public function display(Request $request)
    {
        $uri = explode("/",$_SERVER['REQUEST_URI']);

        list($orgId, $dbName) = $this->connectTenantDatabase($uri[1]);

        $json = $request->json;
        $json=json_decode($json);
        $json=json_encode($json);
        $survey_id = $request->surveyID;

        return view('layouts.survey',compact('json','survey_id','orgId'));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        //getOranisation
        $uri = explode("/",$_SERVER['REQUEST_URI']);
        list($orgId, $dbName) = $this->connectTenantDatabase($uri[1]);
        $surveys=Survey::paginate(5);
       
        return view('admin.surveys.survey_index',compact('surveys','orgId'));
    }

    public function viewResults(Request $request)
    {
        $survey_id=$request->surveyID;
        $uri = explode("/",$_SERVER['REQUEST_URI']);

        $organisation=Organisation::find($uri[1]);
        list($orgId, $dbName) = $this->connectTenantDatabase($uri[1]);
        $survey_results=SurveyResult::where('survey_id',$survey_id)->get();
        return view('user.formResults',compact('survey_results','orgId'));
    }

    public function showCreateForm()
    {
        $uri = explode("/",$_SERVER['REQUEST_URI']);

        $organisation=Organisation::find($uri[1]);
        $orgId=$organisation->id;
        $org_roles = $this->getOrganisationRoles($orgId);

        list($orgId, $dbName) = $this->connectTenantDatabase($orgId);

		$projects = DB::collection('projects')->get(); 
        $categories = Category::where('type','Form')->get();         
        $microservices = Microservice::where('is_active',true)->get(); 
        $entities = Entity::where('is_active',true)->get(); 

        return view('index',compact(['orgId', 'microservices','org_roles','projects','categories','entities']));
    }

    public function editForm($orgId,$survey_id)
    {
        $org_roles = $this->getOrganisationRoles($orgId);

        list($orgId, $dbName) = $this->connectTenantDatabase($orgId);
        
        $survey = DB::collection('surveys')->where('_id',$survey_id)->get();

        $surveyJson = $survey[0]['json'];
        $surveyID = $survey[0]['_id'];

        $surveys = $survey[0]['project_id'].' '.$survey[0]['category_id'].' '.$survey[0]['microservice_id'].' '.$survey[0]['creator_id'].' '.$survey[0]['active'].' '.$survey[0]['editable'].' '.$survey[0]['multiple_entry'].' '.$survey[0]['entity_id'];
        $roles = $survey[0]['assigned_roles'];

		$projects = DB::collection('projects')->get(); 
        $categories = Category::where('type','Form')->get(); 
        $microservices = Microservice::where('is_active',true)->get(); 
        $entities = Entity::where('is_active',true)->get(); 

        return view('admin.surveys.edit',compact('surveyID','surveys','roles','microservices','entities','surveyJson','orgId', 'org_roles','projects','categories'));
    }
    public function saveEditedForm(Request $request)
    {
        $r = json_decode($request->json,true);
        $k = null;

        foreach($r as $key=>$value)
        {
            if($key == 'title')
                $k = $value;
        }
        list($orgId, $dbName) = $this->connectTenantDatabase($request->orgId);
        DB::collection('surveys')->where('_id',$request->surveyID)->update(
            ['name'=>$k ,'json'=>$request->json,
            'active'=>$request->active,'editable'=>$request->editable,
            'multiple_entry'=>$request->multiple_entry,'assigned_roles'=>$request->assigned_roles,
            'category_id'=>$request->category_id,'project_id'=>$request->project_id,
            'microservice_id'=>$request->microservice_id,
            'entity_id'=>$request->entity_id
            ]
        );

        return json_encode($request->surveyID);
     
    }

    public function getOrganisationRoles($orgId){
        $roles= Role::where('org_id','=',$orgId)->get();
        return $roles;
    }   

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($survey_id)
    {
        list($orgId, $dbName) = $this->connectTenantDatabase();
        DB::collection('surveys')->where('_id',$survey_id)->delete();

        return Redirect::back()->withMessage('Form Deleted');
    }
}
