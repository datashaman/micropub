Logged in as <a href="{{ Session::get('user.me') }}">{{ Session::get('user.me') }}</a>  with scope(s) <em>{{ Session::get('user.scope') }}</em>.
<a href="{{ route('auth.logout') }}">Logout</a>

<a href="{{ route('github.redirect') }}">Link GitHub</a>
