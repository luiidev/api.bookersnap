<!DOCTYPE html>
<html>
<head>
    <title>bookersnap.com</title>
</head>
<body>
    <div style="max-width: 600px;padding: 15px;background-color: #2B2C2C;color: #fff;font-family: Verdana;">
        <div  style="width: 100%;">
            @if( $site->url_image_logo )
                <div style="margin-bottom: 15px; width: 100%; position: relative;">
                    <div style="margin: 0 auto; max-width: 240px;">
                        <img src="{{ $site->url_image_logo }}" height="60" />
                    </div>
                </div>
            @else
                <div  style="font-size: 26px;text-align: center;padding-bottom: 25px;">
                    <label style="display: block;">{{ $site->name }}</label>
                </div>
            @endif
            <hr width="100%" align="center" size="1" style="background-color: rgba(75, 75, 75, 1);margin-bottom: 15px;">
            <div  style="text-align: justify;font-size: 13px;line-height: 20px;margin-bottom: 15px;">
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
            <hr width="100%" align="center" size="1" style="background-color: rgba(75, 75, 75, 1);margin-bottom: 15px;">            
        </div>
    </div>
</body>
</html>

