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
use Session;

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
                'userName' => $request->creator_id,
                'active' => $request->active,
                'editable' => $request->editable,
                'deletable' => $request->deletable,
                'multiple_entry' => $request->multiple_entry,
                'assigned_roles' => $request->assigned_roles,
                'category_id' => $request->category_id,
                'project_id' => $request->project_id,
                'microservice_id' => $request->microservice_id,
                'entity_id' => $request->entity_id,
                'createdDateTime' => \Carbon\Carbon::now()->getTimestamp(),
                'updatedDateTime' => \Carbon\Carbon::now()->getTimestamp()
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
        
        $title_fields = [];
        $pretext_title = '';
        $posttext_title = '';
        $separator = '';
          
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
        $title_fields_str='';
        return view('admin.surveys.editKeys',compact('primaryKeySet','keys','numberOfKeys','orgId','survey_id','title_fields','title_fields_str','pretext_title','posttext_title','separator'));
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
        $survey = Survey::where('_id','=',$survey_id)->get(['form_keys','json','title_fields','pretext_title','posttext_title','separator']);

        //obtains only the primary keys from $survey as an array
        $primaryKeySet = $survey[0]->form_keys;

        $title_fields = $survey[0]->title_fields;
        $pretext_title = $survey[0]->pretext_title;
        $posttext_title = $survey[0]->posttext_title;
        $separator = $survey[0]->separator;

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
        $title_fields_str = '';
        if ($title_fields != null) {
            $title_fields_str = implode(',',$title_fields);
        }
        return view('admin.surveys.editKeys',compact('primaryKeySet','keys','numberOfKeys','orgId','survey_id','title_fields','title_fields_str','pretext_title','posttext_title','separator'));
    }

    public function storeKeys(Request $request)
    {
        if($request->filled('title_fields')) {
            $title_fields = explode(',',$request->title_fields);
        } else {
            $title_fields = [];
        }
        $survey_id = $request->surveyID;

        //Returns $request->primaryKeys[]
        //$primaryKeys = $request->except(['_token','surveyID']);
        $primaryKeys = $request->filled('form_keys') ? $request->input('form_keys'):[]; 
        $pretext_title = $request->filled('pretext_title') ? $request->input('pretext_title'):'';
        // $title_fields = $request->filled('title_fields')? $request->input('title_fields'):[]; 
        $posttext_title = $request->filled('posttext_title')? $request->input('posttext_title'):''; 
        $separator = is_null($request->input('separator'))?'':$request->input('separator');  

        list($orgId, $dbName) = $this->connectTenantDatabase();
        if (!empty($primaryKeys)) {
            DB::collection('surveys')->where('_id',$survey_id)->update(['form_keys'=>$primaryKeys]);
        }

        DB::collection('surveys')->where('_id',$survey_id)->update(['pretext_title'=>$pretext_title,
                                                                    'title_fields'=>$title_fields,
                                                                    'posttext_title'=>$posttext_title,
                                                                    'separator'=>$separator]);

        //Redirects to index function
        // return redirect($orgId . '/forms');
        return redirect($orgId . '/forms?page='.Session::get('page'));
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
        // session_start();
        
        $uri = explode("=",$_SERVER['REQUEST_URI']);
        Session::put('page', $uri[1]);
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
        //echo '<pre>';
        //print_r($survey[0]['editable']);exit;
        $surveyJson = $survey[0]['json'];
        $surveyID = $survey[0]['_id'];
        $editable = $survey[0]['editable'];
        $deletable = $survey[0]['deletable'];
        $multiple_entry = $survey[0]['multiple_entry'];
        $active = $survey[0]['active'];

        $surveys = $survey[0]['project_id'].' '.$survey[0]['category_id'].' '.$survey[0]['microservice_id'].' '.$survey[0]['active'].' '.$survey[0]['editable'].' '.$survey[0]['multiple_entry'].' '.$survey[0]['entity_id'];
        $roles = $survey[0]['assigned_roles'];

		$projects = DB::collection('projects')->get(); 
        $categories = Category::where('type','Form')->get(); 
        $microservices = Microservice::where('is_active',true)->get(); 
        $entities = Entity::where('is_active',true)->get(); 

        return view('admin.surveys.edit',compact('surveyID','surveys','roles','microservices','entities','surveyJson','orgId', 'org_roles','projects','categories', 'editable','deletable','multiple_entry','active'));
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
            'deletable' => $request->deletable,
            'multiple_entry'=>$request->multiple_entry,'assigned_roles'=>$request->assigned_roles,
            'category_id'=>$request->category_id,'project_id'=>$request->project_id,
            'microservice_id'=>$request->microservice_id,
            'entity_id'=>$request->entity_id,
            'updatedDateTime'=> \Carbon\Carbon::now()->getTimestamp()
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
