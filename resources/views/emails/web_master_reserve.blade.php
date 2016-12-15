<!DOCTYPE html>
<html>
<head>
    <title>bookersnap.com</title>
    <style type="text/css">
        .principal {
            background-color: #2B2C2C;
            width: 600px; height: 550px;
            color: #2B2C2C;
            margin:0 auto 0 auto;
            font-family: Verdana;
        }
        #header {
            font-size: 26px;
            text-align: center;
            padding: 25px 0px;
        }
        hr {
            background-color: rgba(75, 75, 75, 1);
        }
        .content {
            padding: 10px;
            color: #fff;
            text-align: justify;
        }
        .footer {
            margin-top: 20px;
            text-align: justify;
            font-size: 13px;
            line-height: 20px;
            padding: 0px 17px;
        }
    </style>
</head>
<body>
    <div class="principal">
        @if( $site->image_logo )
        <div id="header" class="nombre">
            <img src="'{{ $site->image_logo }}" alt="log" height="60">
        </div>
        @endif
        <hr width="95%" align="center" size="1">
        <div class="content">
               <p><span>{{ $reservation->guest["first_name"].' '.$reservation->guest["last_name"] }}</span> ha hecho una reservacion en {{ $site->name }}</p>
               <p>Fecha de reservación: <span><strong>{{ $reservation->date_reservation }}</strong></span></p>
               <p>Hora de reservación: <span><strong>{{ date('h:i A', strtotime($reservation->hours_reservation)) }}</strong></span></p>
               <p>Num. de invitados: <span><strong>{{ $reservation->num_guest }}</strong></span></p>
               <p>Email del usuario: <span><strong>{{ $reservation->email }}</strong></span></p>
               <p>Telefono: <span><strong>{{ $reservation->phone }}</strong></span></p>
               <p>Tipo de reservación: <span><strong>{{ $type_reserve or 'WEB'}}</strong></span></p>
               <p></p>
               <p>Si desea ver el libro de reservaciones inicie sesión en <strong><a href="'{{ $reservation->domain }}'">{{ $reservation->domain }}</a></strong></p>
        </div>
        <hr width="95%" align="center" size="1">
        <div class="footer"></div>
    </div>
</body>
</html>

