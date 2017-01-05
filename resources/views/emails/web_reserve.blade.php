<!DOCTYPE html>
<html>
<head>
    <title>bookersnap.com</title>
    <style type="text/css">
        .principal {
            background-color: #2B2C2C;
            width: 600px;
/*            height: 780px;*/
            display: table;
            color: #2B2C2C;
            margin:0 auto 0 auto;
            padding-bottom: 20px;
            font-family: Verdana;
        }
        #header {
            font-size: 26px;
            text-align: center;
            padding: 25px 0px;
        }
        #header label {
            display: block;
            color: #fff;
        }
        .info .promo{
            text-align: center;
        }
        .info .user{
            margin: 20px;
        }
        .info label{
            display: block;
            line-height: 25px;
            color: #fff;
        }
        .bold {
            font-weight: bold;
        }
        hr {
            background-color: rgba(75, 75, 75, 1);
        }
        .detail {
            margin-top: 40px;
            text-align: justify;
            font-size: 13px;
            line-height: 20px;
            padding: 0px 17px;
        }
        .detail label {
            color: #fff;
        }
    </style>
</head>
    <body>
        <div class="principal">
            <div id="header">
                <label>Gracias <span>{{ $reservation->guest["first_name"] }}</span>!</label> 
                <label>Esta es tu invitación.</label>
            </div>

            <div class="info">
                @if( $reservation->event )
                <div class="promo">
                    <img src="{{ $reservation->event['url_image'] }}" />
                </div>
                @endif

                <div class="user">               
                  <label class="bold">{{ $reservation->guest["first_name"].' '.$reservation->guest["last_name"] }}</label>
                  <label>Email: <span class="bold">{{  $site->email}}</span></label>
                  <label>Visítanos en:  <a href="{{ $site->domain }}"><span class="bold">{{  $site->site_name }}</span></a></label>
                </div>

                <hr width="95%" align="center" size="1">
            </div>

            <div class="detail">
                 <div>
                      <label>
                          <p>Tu reservación es para la fecha {{ $reservation->date_reservation }} a las {{ date('h:i A', strtotime($reservation->hours_reservation)) }}.
                          @if( $site->configuration["time_tolerance"] )
                              y la tolerancia es de {{ $site->configuration["time_tolerance"] }} minutos.
                          @endif

                          @if( $site->configuration["res_percentage_id"] > 1 )
                              Te recodamos que para otorgarte tu mesa debe estar presente el {{ $site->configuration["percentage"]["percentage"] }} de tu reservación.
                          @endif
                          </p>
                      </label>
                 </div>

                 <div>
                    <label>
                      Gracias <span>{{ $reservation->guest["first_name"].' '.$reservation->guest["last_name"] }}</span> recibimos tu reservación satisfactoriamente, protege tus datos y no reserves en ninguna otra página pirata que diga 'sitio oficial', este es el único Sitio Oficial y cumple con todas las normas de IFAI (Instituto Federal de Acceso a la Información y Protección de Datos). Todos los derechos reservados, {{  $site->site_name }}. {{  $site->address }} - {{  $site->country["name"] }}. Teléfono {{  $site->phone }} Su privacidad es importante para nosotros. 
                    </label>
                 </div>

            </div>
        </div>
    </body>
</html>