<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <nav class="navbar navbar-light navbar-expand-lg mb-5" style="background-color: #e3f2fd;">
        <div class="container">
            <a class="navbar-brand mr-auto" href="#">Chat Application</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav">
                    @guest

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @else
                        <li class="nav-item">
                            @if (Auth::user()->user_image != '')
                                <a class="nav-link" href="#">
                                    <b>Welcom <img src="{{ asset('images/profile-images/' . Auth::user()->user_image) }}"
                                            width="35" class="rounded-circle" />
                                        {{ Auth::user()->name }}
                                    </b>
                                </a>
                            @else
                                <a class="nav-link" href="#">
                                    <b>Welcom <img src="{{ asset('images/profile-images/no-image.png') }}" width="35"
                                            class="rounded-circle" />&nbsp;
                                        {{ Auth::user()->name }}
                                    </b>
                                </a>
                            @endif
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('profile') }}">Profile</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('logout') }}">Logout</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('Chat') }}">Go Chat</a>
                        </li>

                    @endguest
                </ul>

            </div>
        </div>
    </nav>
    <div class="container mt-5">

        @yield('content')

    </div>

</body>

</html>
