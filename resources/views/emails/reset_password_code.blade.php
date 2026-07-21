<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: 'DM Sans', sans-serif; background: #FAFAF5; padding: 24px;">
<div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; text-align: center;">
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" alt="KlassApp" style="width: 140px; height: auto; margin-bottom: 16px;">
    <h1 style="font-family: 'Sora', sans-serif; font-size: 20px; color: #0F172A; margin: 0 0 8px;">Password Reset Code</h1>
    <p style="color: #64748B; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
        Hi {{ $name }},
        <br><br>
        Use the code below to reset your password. It expires in 5 minutes.
    </p>
    <div style="background: #F0FDF4; border: 2px dashed #22C55E; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
        <span style="font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #16A34A;">{{ $code }}</span>
    </div>
    <p style="color: #94A3B8; font-size: 12px; margin: 0;">
        If you did not request this reset, please ignore this email.
    </p>
</div>
</body>
</html>
