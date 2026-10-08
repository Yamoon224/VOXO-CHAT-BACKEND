<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
</head>
<body style="margin:0;padding:24px;background:#f4f5f7;font-family:system-ui,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;">
        <tr>
            <td style="padding:28px 32px 8px;font-size:18px;font-weight:700;letter-spacing:-.01em;">VOXO</td>
        </tr>
        <tr>
            <td style="padding:8px 32px 24px;font-size:15px;line-height:1.6;">
                @yield('content')

                <p style="margin:24px 0;">
                    <a href="{{ $action_url }}" style="display:inline-block;padding:11px 18px;border-radius:6px;background:#0f766e;color:#ffffff;font-weight:600;text-decoration:none;">@yield('action')</a>
                </p>

                <p style="margin:0;color:#6b7280;font-size:13px;">
                    Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                    <span style="word-break:break-all;">{{ $action_url }}</span>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 32px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;">
                @yield('footnote')
            </td>
        </tr>
    </table>
</body>
</html>
