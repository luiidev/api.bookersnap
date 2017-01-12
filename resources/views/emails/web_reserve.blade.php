<!DOCTYPE html>
<html>
    <head>
        <title>bookersnap.com</title>
    </head>
    <body>
        <div style="max-width: 600px;padding: 15px; background-color: #2B2C2C;color: #fff;font-family: Verdana;">
                    
            <div  style="width: 100%;">
                <div  style="font-size: 26px;text-align: center;padding-bottom: 25px;">
                    <label style="display: block;">Gracias <span>{{ $reservation->guest["first_name"] }}</span>!</label> 
                    <label style="display: block;">Esta es tu invitación.</label>
                </div>

                @if( $reservation->event )
                <div style="margin-bottom: 15px; width: 100%; position: relative;">
                    <div style="margin: 0 auto; max-width: 240px;">
                        <img src="{{ $reservation->event['url_image'] }}" style="width: 100%;" />
                    </div>
                </div>
                @endif

                <div  style="margin-bottom: 15px;">               
                    <label style="display: block;line-height: 25px;font-weight: bold;" class="bold">{{ $reservation->guest["first_name"].' '.$reservation->guest["last_name"] }}</label>
                    <label style="display: block;line-height: 25px;">Email: <span class="bold">{{  $site->email}}</span></label>
                    <label style="display: block;line-height: 25px;">Visítanos en:  <a href="{{ $site->domain }}"><span class="bold">{{  $site->site_name }}</span></a></label>
                </div>
                <hr width="100%" align="center" size="1" style="background-color: rgba(75, 75, 75, 1);margin-bottom: 15px;">

                <div  style="text-align: justify;font-size: 13px;line-height: 20px;margin-bottom: 15px;">
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
        </div>
    </body>
</html>