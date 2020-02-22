<div>
    Logged in <a href="https://indieauth.com">IndieAuth</a> as <a href="{{ Session::get('user.me') }}">{{ Session::get('user.me') }}</a>  with scope(s) <em>{{ Session::get('user.scope') }}</em>.
    <a href="{{ route('indieauth.logout') }}">Logout</a>
</div>

@auth
    <div>
        Logged in to <a href="https://github.com">GitHub</a> as <a href="https://github.com/{{ auth()->user()->name }}">{{ auth()->user()->name }}</a>.
        <a href="{{ route('github.logout') }}">Logout</a>
    </div>
@endauth

@guest
    <div>
        <a href="{{ route('github.login') }}">Login with GitHub</a>
    </div>
@endguest
