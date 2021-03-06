@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

                <div class="panel-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif
                        <h3>Create <h3 id="levelPage">Village</h3></h3>
                    <form action="{{route('village.store')}}" method="post">
                           {{csrf_field()}}     
                        <legend></legend>
                             <div class="form-group">
                                 <label for="villageName">Village Name</label>
                                 <input type="text" name="villageName" placeholder="name of the village" class="form-control"/>
                             </div>
                             <div>
                                <h4>State</h4>
                                    <select id="state_id" name="state_id" class="form-control">
                                            <option value="0"></option>
                                            @foreach($states as $s)
                                                <option value={{$s->id}}>{{$s->Name}}</option>
                                            @endforeach 
                                    </select>
                            </div>
                            <br/>
                                <div id="levelContainer"  class="form-group">
                                       
                                </div>

                            <input type="submit" class="btn btn-success"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>
@endsection