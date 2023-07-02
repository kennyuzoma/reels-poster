<html>
    <head>
        <title>Social Poster</title>
    </head>
    <body>

        <script>
            window.fbAsyncInit = function() {
                FB.init({
                    appId      : '660590535294909',
                    cookie     : true,
                    xfbml      : true,
                    version    : 'v2.7'
                });

                FB.AppEvents.logPageView();

            };

            (function(d, s, id){
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) {return;}
                js = d.createElement(s); js.id = id;
                js.src = "https://connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));

            function checkLoginState() {
                FB.getLoginStatus(function(response) {
                    statusChangeCallback(response);
                });
            }
        </script>

        <fb:login-button
            scope="public_profile,email"
            onlogin="checkLoginState();">
        </fb:login-button>
    </body>
</html>
