<div>
    Logged in to <a href="https://indieauth.com" target="_blank" rel="noopener noreferrer">IndieAuth</a>
    as <a href="{{ Session::get('user.me') }}">{{ Session::get('user.me') }}</a>  with scope(s) <em>{{ Session::get('user.scope') }}</em>.
    <a href="{{ route('indieauth.logout') }}">Logout</a>
</div>

@auth
    <div>
        Logged in to <a href="https://github.com" target="_blank" rel="noopener noreferrer">GitHub</a>
        as <a href="https://github.com/{{ auth()->user()->name }}" target="_blank" rel="noopener noreferrer">{{ auth()->user()->name }}</a>.
        <a href="{{ route('github.logout') }}">Logout</a>
    </div>

    <select>
        @foreach($repositories as $repo)
            <option value="{{ $repo['id'] }}">{{ $repo['full_name'] }}</option>
        @endforeach
    </select>
@endauth

@guest
    <div>
        <a href="{{ route('github.login') }}">Login with GitHub</a>
    </div>
@endguest
