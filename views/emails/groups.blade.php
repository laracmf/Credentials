@extends(Config::get('credentials.email'))

@section('content')
<p>An admin from <a href="{!! $url !!}" target="_blank">{!! Config::get('app.name') !!}</a> has changed your role.</p>
<p>Login to see your updated permissions.</p>
@stop
