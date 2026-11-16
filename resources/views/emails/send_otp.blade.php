<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your OTP</title>
</head>

<body style="font-family: Arial, sans-serif; padding-top: 20px; margin: 0; background-color: #ffffff;">
    <div style="background-color: #fff;">
        <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #000;">
            Welcome to Water Supply!
        </h1>
        {{-- <img src="{{ url('assets/logo/logo.png') }}" alt="" srcset=""> --}}
        <p style="font-size: 13px; color: #333;">
            Your One-Time OTP is:
            <strong style="font-size: 14px; color: #000;">{{ $otp }}</strong>
        </p>
    </div>
</body>

</html>
