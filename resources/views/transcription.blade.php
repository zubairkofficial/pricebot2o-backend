<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transkriptions-E-Mail</title>
</head>

<body>
    <h1>Transkriptions-E-Mail</h1>

    <!-- Profilbild -->
    <img src="https://pricebot.martinmobiles.com/assets/logo.jpeg" alt="Profilbild" style="width: 100px; height: 100px; border-radius: 50%;">
{{-- 
    <p>Liebe/r {{ $data['name'] }},</p>
    <p>Titel: {{ $data['title'] }}</p> --}}
    
    @if(isset($data['transcriptionText']))
    <p>Hier ist der Transkriptionstext:</p>
    <p>{{ $data['transcriptionText'] }}</p>
    @endif
    <br>
    <br>
    
    @if(isset($data['listeningText']))
    <p>Hier ist der Hörtext:</p>
    <p>{{ $data['listeningText'] }}</p>
    @endif
    
    @if(isset($data['summary']))
    <br> <!-- Add an empty line for gap -->
    <br>
    <br>
    <p>Zusammenfassung:</p>
    <p style="white-space: break-spaces">{{ $data['summary'] }}</p>

    @endif

    <p>Vielen Dank,</p>
    <!-- <p>PriceBot</p> -->
</body>

</html>
