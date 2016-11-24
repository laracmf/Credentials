@extends(Config::get('credentials.email'))

@section('content')
<p>You have requested we resend the activation link for <a href="{!! $url !!}" target="_blank">{!! Config::get('app.name') !!}</a>.</p>
<p>To activate your account, <a href="{!! $link !!}">click here</a>.</p>
@stop
