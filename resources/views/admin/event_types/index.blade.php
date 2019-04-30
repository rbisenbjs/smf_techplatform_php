@extends('layouts.userBased')

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<h1 class="h3 mb-2 text-gray-800">Event Types</h1>
<p class="mb-4">
    @if (session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif
    </p>

<!-- DataTales Example -->
<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">
        <a href="/{{$orgId}}/event-type/create" class="btn btn-primary btn-icon-split">
        <span class="icon text-white-50">
            <i class="fas fa-plus"></i>
        </span>
        <span class="text">Event Type</span>
        </a>
    </h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
        <thead>
        <tr>
            <th>Name</th>
            <th>Action</th>
        </tr>
        </thead>
        <tfoot>
         <tr>
            <th>Name</th>
            <th>Action</th>
          </tr>
        </tfoot>
        <tbody>
            @forelse($event_types as $event_type)
                <tr>
                    <td>{{$event_type->name}}</td>
                    <td> 
                        <div class="actions">
                            <div style="float:left !important;padding-left:5px;">
                                <a class="btn btn-primary btn-circle btn-sm" href={{route('event-type.edit',$event_type->id)}}><i class="fas fa-pen"></i></a>
                            </div>
                            <div style="float:left !important;padding-left:5px;">
                                {!!Form::open(['action'=>['EventTypeController@destroy',$event_type->id],'method'=>'DELETE','class'=>'pull-right' ])!!}
                                <button type="submit" class="btn btn-danger btn-circle btn-sm"><i class="fas fa-trash"></i></button>
                                {!!Form::close()!!}
                            </div>
                            <div style="clear:both !important;"></div>     
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td>No Event Types</td></tr>
            @endforelse
            </tbody>
      </table>
    </div>
    
  </div>
</div>

</div>
<!-- /.container-fluid -->
@endsection