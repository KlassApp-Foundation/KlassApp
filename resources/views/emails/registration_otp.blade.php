<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KlassApp Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
        <tr>
            <td style="padding: 24px;">
                <h2 style="margin: 0 0 12px; color: #0b203e;">Verify your KlassApp account</h2>
                <p style="margin: 0 0 16px; line-height: 1.6;">Hello {{ $name ?? 'User' }},</p>
                <p style="margin: 0 0 16px; line-height: 1.6;">Use the verification code below to complete your registration:</p>
                <p style="margin: 0 0 20px; font-size: 30px; font-weight: 700; letter-spacing: 4px; color: #1d4ed8;">{{ $otp }}</p>
                <p style="margin: 0 0 8px; line-height: 1.6;">This code expires in 5 minutes.</p>
                <p style="margin: 0; line-height: 1.6; color: #64748b; font-size: 13px;">If you did not request this code, you can safely ignore this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>
