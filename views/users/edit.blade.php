@extends(Config::get('credentials.admin-layout'))

@section('title')
    <?php $__navtype = 'admin'; ?>
    Edit {{ $user->name }}
@stop

@section('top')
    <div class="page-header">
        <h1>Edit {{ $user->name }}</h1>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="pull-right">
                &nbsp;<a class="btn btn-success" href="{!! URL::route('users.show', array('users' => $user->id)) !!}"><i
                            class="fa fa-file-text"></i> Show User</a>
                @if(isAdmin())
                    <a class="btn btn-default" href="#reset_user" data-toggle="modal" data-target="#reset_user"><i
                                class="fa fa-lock"></i> Reset Password</a>
                    <a class="btn btn-danger" href="#delete_user" data-toggle="modal" data-target="#delete_user"><i
                                class="fa fa-times"></i> Delete</a>
                @endif
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="half">
            <div class="box box-group">
                <div class="box-header">
                    <h3 class="box-title">Edit user</h3>
                </div>
                <div class="box-body">
                    <?php
                    $form = ['url' => URL::route('users.update', ['users' => $user->id]),
                            '_method' => 'PATCH',
                            'button' => 'Save User',
                            'roles' => $roles,
                            'userRoles' => $userRoles,
                            'defaults' => [
                                    'first_name' => $user->first_name,
                                    'last_name' => $user->last_name,
                                    'email' => $user->email,
                            ],];

                    foreach ($roles as $role) {
                        $form['defaults']['role_' . $role->id] = in_array($role->id, $userRoles);
                    }
                    ?>
                    @include('credentials::users.form')
                </div>
            </div>
        </div>
    </div>
@stop

@section('bottom')
    @include('credentials::users.suspend')
    @if(isAdmin())
        @include('credentials::users.reset')
        @include('credentials::users.delete')
    @endif
@stop

@section('css')
    <link rel="stylesheet" type="text/css"
          href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.1.0/css/bootstrap3/bootstrap-switch.min.css">
@stop

@section('js')
    <script type="text/javascript"
            src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.1.0/js/bootstrap-switch.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $(".make-switch").bootstrapSwitch();
        });
    </script>
@stop
