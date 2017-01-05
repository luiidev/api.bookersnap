<?php

return [
    "default" => [
        "from_email" => "no-reply@bookersnap.com",
        "from_name" => "bookersnap.com",
        "subject" => strtoupper(str_random(5))." Mensaje de confirmacion bookersnap.com."
    ],
    "web_reserve" => [
        "from_email" => "confirmacion@bookersnap.com",
        "from_name" => "bookersnap.com",
        "subject" => strtoupper(str_random(5))." Confirmación de reserva"
    ],
    "web_master_reserve" => [
        "from_email" => "notificacion@bookersnap.com",
        "from_name" => "bookersnap.com",
        "subject" => strtoupper(str_random(5))." Se realizo una reservacion"
    ],
];
