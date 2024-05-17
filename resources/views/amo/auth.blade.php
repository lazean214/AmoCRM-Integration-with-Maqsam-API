<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    @php
        $clientId = $data->client_id;
        $clientSecret = $data->client_secret;
        $redirectUri = env('AMO_CLIENT_REDIRECT_URI');


    @endphp
    <script
    class="amocrm_oauth"
    charset="utf-8"
    data-client-id="{{$clientId}}"
    data-title="Button"
    data-compact="false"
    data-class-name="className"
    data-color="default"
    data-state="state"
    data-error-callback="functionName"
    data-mode="popup"
    src="https://www.amocrm.ru/auth/button.min.js"
  ></script>
</body>
</html>