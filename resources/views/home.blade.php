<html>
    <head>
        <title>Home</title>
        <style>
            * {
                font-family:Helvetica;
            }

            #container {
                margin:10px auto;
                width:1000px;
            }
        </style>

    </head>
    <body>
        <div id="container">

            <h1>Welcome Home, {{auth()->user()->name}}</h1>

            Access Token:

            <br>

            <code><textarea>{{ $accessToken }}</textarea></code>

            @if ($localhostAccessToken)
                <br><br><a href="http://localhost/save-token?token={{ $accessToken }}">Save token to LocalHost</a>'
            @endif

            <br>

            <h2>Instagram Accounts you gave us access to:</h2>

            @foreach ($accounts as $account)

                <div style="margin-bottom:20px; border-bottom:1px solid #ccc; padding-bottom:10px;">
                    <div style="margin-bottom:5px;">Name: <b>{{ $account['name'] }}</b></div>

                    @if ($dbAccount = \App\Models\Account::where('external_id', $account['instagram_business_account']['id'])->first())
                        <div>
                            <a href="{{ route('facebook.ig.remove', ['accountId' => $dbAccount['id']]) }}" style="color:red;" onclick="if (! confirm('All scheuled posts will be deleted. Continue?')) { return false; }">Remove From app</a>
                        </div>
                    @else
                        <div><a href="{{ route('facebook.ig.add', ['accountId' => $account['id']]) }}">Import to App</a></div>
                    @endif
                </div>

            @endforeach
        </div>

    </body>
</html>
