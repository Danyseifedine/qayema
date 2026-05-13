<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to Qayema</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f0e8;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f0e8;padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">


                    {{-- Main card --}}
                    <tr>
                        <td style="background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(15,15,16,0.08);">

                            {{-- Gold top bar --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background:linear-gradient(135deg,#C8A85A,#e0c07a);height:6px;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>

                            {{-- Body --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding:48px 48px 0;">

                                        {{-- Congrats badge --}}
                                        <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
                                            <tr>
                                                <td style="background-color:#fdf8ef;border:1px solid #e8d9b0;border-radius:999px;padding:6px 16px;">
                                                    <span style="font-size:12px;font-weight:600;color:#A8863C;letter-spacing:0.08em;text-transform:uppercase;">You're live</span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Heading --}}
                                        <h1 style="margin:0 0 16px;font-size:32px;font-weight:700;color:#0f0f10;line-height:1.15;letter-spacing:-0.025em;">
                                            Your menu is ready,<br>
                                            <span style="color:#C8A85A;">{{ $restaurant->name }}.</span>
                                        </h1>

                                        {{-- Subtext --}}
                                        <p style="margin:0 0 32px;font-size:16px;line-height:1.65;color:#555;max-width:440px;">
                                            Hi {{ $user->name }}, your digital menu is now live on Qayema. Share the link below with your guests, one scan and they're ordering.
                                        </p>

                                        {{-- Menu link box --}}
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
                                            <tr>
                                                <td style="background-color:#f9f6f0;border:1.5px solid #e8d9b0;border-radius:12px;padding:20px 24px;">
                                                    <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#999;">Your menu link</p>
                                                    <a href="{{ url('/' . $restaurant->slug) }}" style="font-size:16px;font-weight:600;color:#A8863C;text-decoration:none;word-break:break-all;">
                                                        {{ parse_url(config('app.url'), PHP_URL_HOST) }}/{{ $restaurant->slug }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- CTA button --}}
                                        <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:48px;">
                                            <tr>
                                                <td style="background-color:#0f0f10;border-radius:999px;">
                                                    <a href="{{ route('dashboard') }}" style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;letter-spacing:-0.01em;">
                                                        Go to your dashboard &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                            {{-- What's next section --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background-color:#fafafa;border-top:1px solid #f0ede8;padding:32px 48px 40px;">
                                        <p style="margin:0 0 20px;font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#999;">What you can do now</p>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding-bottom:14px;vertical-align:top;width:28px;">
                                                    <span style="display:inline-block;width:22px;height:22px;background-color:#0f0f10;border-radius:50%;text-align:center;line-height:22px;font-size:11px;font-weight:700;color:#C8A85A;">1</span>
                                                </td>
                                                <td style="padding-bottom:14px;padding-left:12px;vertical-align:top;">
                                                    <p style="margin:0;font-size:14px;font-weight:600;color:#0f0f10;">Add your dishes</p>
                                                    <p style="margin:4px 0 0;font-size:13px;color:#888;line-height:1.5;">Upload your full menu with photos, descriptions, and prices.</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom:14px;vertical-align:top;width:28px;">
                                                    <span style="display:inline-block;width:22px;height:22px;background-color:#0f0f10;border-radius:50%;text-align:center;line-height:22px;font-size:11px;font-weight:700;color:#C8A85A;">2</span>
                                                </td>
                                                <td style="padding-bottom:14px;padding-left:12px;vertical-align:top;">
                                                    <p style="margin:0;font-size:14px;font-weight:600;color:#0f0f10;">Download your QR code</p>
                                                    <p style="margin:4px 0 0;font-size:13px;color:#888;line-height:1.5;">Print it and place it on every table so guests can scan and order.</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="vertical-align:top;width:28px;">
                                                    <span style="display:inline-block;width:22px;height:22px;background-color:#0f0f10;border-radius:50%;text-align:center;line-height:22px;font-size:11px;font-weight:700;color:#C8A85A;">3</span>
                                                </td>
                                                <td style="padding-left:12px;vertical-align:top;">
                                                    <p style="margin:0;font-size:14px;font-weight:600;color:#0f0f10;">Share your menu link</p>
                                                    <p style="margin:4px 0 0;font-size:13px;color:#888;line-height:1.5;">Post it on Instagram, WhatsApp, or Google, anywhere your guests are.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:28px 0 0;text-align:center;">
                            <p style="margin:0 0 6px;font-size:13px;color:#aaa;">Made with care &#10084; <a href="{{ config('app.url') }}" style="color:#C8A85A;text-decoration:none;">qayema.com</a></p>
                            <p style="margin:0;font-size:12px;color:#ccc;">&copy; {{ date('Y') }} Lebify Group. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
