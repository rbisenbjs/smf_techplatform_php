@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Dashboard</div>

                <div class="panel-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif
                        <h3>Edit Organisation</h3>
                        {!! Form::model($org, ['route' => [ 'organisation.update', $org->id ], 'method'=>'PUT', 'id' => '`-edit-form']) !!}
                        {{csrf_field()}} 
                        <legend></legend>
                             <div class="form-group">
                                 <label for="name">Name</label>
                                 <input type="text" name="name" placeholder="name"class="form-control" value="{{$org->name}}"/>
                             </div>
                            
                             <div class="form-group">
                                <label for="service">Service</label>
                                 <input type="text" name="service" placeholder="service"class="form-control" value="{{$org->service}}"/>
                            </div>
                           
                            
                            <input type="submit" class="btn btn-success"/>
                            {!! Form::close() !!} 
                        
                </div>
            </div>
        </div>
    </div>
</div>
@endsection