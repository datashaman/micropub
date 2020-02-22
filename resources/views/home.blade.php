<div>
Logged in as <a href="{{ Session::get('user.me') }}">{{ Session::get('user.me') }}</a>  with scope(s) <em>{{ Session::get('user.scope') }}</em>.
<a href="{{ route('indieauth.logout') }}">Logout</a>
</div>

<div>
    <a href="{{ route('github.redirect') }}">Link GitHub</a>
</div>
