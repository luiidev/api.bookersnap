<div style="background-color: #ccc">
@include('emails.partial.header')

    <h4 class="secondary" ><strong>Hola : {{ $to_name }}</strong></h4>
    <p>Mensaje : {{ $message }}</p>

@include('emails.partial.footer')
<div>
