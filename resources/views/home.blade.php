@if(Session::has('user'))
    Logged in as <a href="{{Session::get('user.me')}}">{{Session::get('user.me')}}</a>  with scope(s) <em>{{Session::get('user.scope')}}</em>.
@endif
