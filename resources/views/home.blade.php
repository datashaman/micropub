<div>
Logged in as <a href="{{ Session::get('user.me') }}">{{ Session::get('user.me') }}</a>  with scope(s) <em>{{ Session::get('user.scope') }}</em>.
<a href="{{ route('indieauth.logout') }}">Logout</a>
</div>

@auth
    <div>
        Logged in as {{ auth()->user()->name }}.
    </div>
@endauth

@guest
    <div>
        <a href="{{ route('github.redirect') }}">Login with GitHub</a>
    </div>
@endguest
