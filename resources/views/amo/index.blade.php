<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
  
  <form action="/amocrm/addlead" method="POST">
    @csrf
    <input type="hidden" name="PIPELINEID" value="3441670">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="PHONE" placeholder="PHONE">
    <input type="text" name="EMAIL" placeholder="EMAIL">
    <input type="text" name="LEAD_NAME" placeholder="LEAD_NAME">
    <button type="submit">Add Lead</button>
  </form>

  
</body>
</html>