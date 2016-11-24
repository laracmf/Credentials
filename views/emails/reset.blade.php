@extends(Config::get('credentials.email'))

@section('content')
<p>To reset your password, <a href="{!! $link !!}" target="_blank">click here.</a></p>
<p>After confirming this request, you will receive an email with your temporary password.</p>
@stop
