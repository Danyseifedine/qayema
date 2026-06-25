<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="x-apple-disable-message-reformatting" />
  <meta name="color-scheme" content="light" />
  <meta name="supported-color-schemes" content="light" />
  <title>Your menu is live, welcome to Qayema</title>
  <!--[if mso]>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <![endif]-->
  <style>
    /* Client resets */
    html, body { margin: 0 !important; padding: 0 !important; height: 100% !important; width: 100% !important; }
    * { -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important; }
    table { border-spacing: 0 !important; border-collapse: collapse !important; table-layout: fixed !important; margin: 0 auto !important; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; display: block; }
    a { text-decoration: none; }
    .editorial { font-family: 'Instrument Serif', Georgia, 'Times New Roman', serif; }

    /* Hover */
    .btn-primary:hover { background-color: #1c1c1f !important; }
    .btn-ghost:hover { background-color: #efe8da !important; }
    .link-u:hover { border-bottom-color: #4F5C3A !important; color: #4F5C3A !important; }

    /* Dark-mode hinting kept minimal/light-locked */
    @media (prefers-color-scheme: dark) {
      body, .bg-paper { background-color: #F6F1E8 !important; }
    }

    /* Mobile */
    @media screen and (max-width: 600px) {
      .container { width: 100% !important; }
      .px { padding-left: 24px !important; padding-right: 24px !important; }
      .py-lg { padding-top: 40px !important; padding-bottom: 40px !important; }
      .h1 { font-size: 40px !important; line-height: 42px !important; }
      .stack { display: block !important; width: 100% !important; }
      .stack-pad { padding: 0 0 16px 0 !important; }
      .stat-cell { padding: 16px 0 !important; }
      .hide-mobile { display: none !important; }
      .center-mobile { text-align: center !important; }
      .full-btn { width: 100% !important; display: block !important; }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet" />
</head>
<body style="margin:0; padding:0; background-color:#EFE8DA; -webkit-font-smoothing:antialiased;">

  <!-- Preheader (hidden) -->
  <div style="display:none; font-size:1px; color:#EFE8DA; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden; mso-hide:all;">
    Your menu is live on Qayema. Here's how to build your menu, print your QR code, and welcome your first guests. No app required.
    &#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;
  </div>

  <!-- Outer wrapper -->
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#EFE8DA;">
    <tr>
      <td align="center" style="padding:32px 12px;">

        <!-- Preheader bar -->
        <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">
          <tr>
            <td class="px" style="padding:0 8px 14px 8px;" align="center">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:11px; letter-spacing:1.4px; text-transform:uppercase; color:#7c765f;">
                    Qayema &nbsp;·&nbsp; Onboarding
                  </td>
                  <td align="right" style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:11px; letter-spacing:0.6px; color:#7c765f;">
                    <a href="{{ url('/'.$restaurant->slug) }}" class="link-u" style="color:#7c765f; border-bottom:1px solid #cfc6b2; padding-bottom:1px;">View your menu</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>

        <!-- Card -->
        <table role="presentation" class="container bg-paper" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#F6F1E8; border:1px solid rgba(15,15,16,0.08); border-radius:20px; overflow:hidden;">

          <!-- ───────── Header / brand ───────── -->
          <tr>
            <td class="px" style="padding:28px 40px 0 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" valign="middle">
                    <img src="{{ asset('images/logo/q-logo.png') }}" alt="Qayema" height="34" style="display:block; height:34px; width:auto; border:0; outline:none; text-decoration:none;" />
                  </td>
                  <td align="right" valign="middle" class="hide-mobile" style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:12px; color:#7c765f;">
                    {{ $restaurant->name }}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ───────── Hero ───────── -->
          <tr>
            <td class="px py-lg" style="padding:40px 40px 8px 40px;">
              <!-- headline -->
              <h1 class="h1" style="margin:0; font-family:'Geist',Helvetica,Arial,sans-serif; font-weight:400; font-size:52px; line-height:52px; letter-spacing:-1.6px; color:#0F0F10;">
                Your menu is<br />
                <span class="editorial" style="font-style:italic; color:#4F5C3A;">live</span>.
              </h1>

              <!-- lead -->
              <p style="margin:22px 0 0 0; font-family:'Geist',Helvetica,Arial,sans-serif; font-size:16px; line-height:25px; color:rgba(15,15,16,0.6);">
                Hi {{ $user->name }}, {{ $restaurant->name }} is set up on Qayema. Add your dishes, print your QR code, and your guests can order from their phone the moment they sit down. Here's how to go live.
              </p>

              <!-- spacer above button -->
              <div style="font-size:0; line-height:34px; height:34px;">&nbsp;</div>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td>
                    <!--[if mso]>
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ url('/') }}" style="height:50px;v-text-anchor:middle;width:220px;" arcsize="50%" fillcolor="#0F0F10" stroke="f">
                      <w:anchorlock/>
                      <center style="color:#F6F1E8;font-family:Helvetica,Arial,sans-serif;font-size:15px;font-weight:500;">Open Qayema  →</center>
                    </v:roundrect>
                    <![endif]-->
                    <!--[if !mso]><!-->
                    <a href="{{ url('/') }}" class="btn-primary full-btn" style="display:inline-block; background-color:#0F0F10; color:#F6F1E8; font-family:'Geist',Helvetica,Arial,sans-serif; font-size:15px; font-weight:500; letter-spacing:-0.2px; line-height:50px; padding:0 26px; border-radius:999px;">
                      Open Qayema &nbsp;&rarr;
                    </a>
                    <!--<![endif]-->
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ───────── Menu link ───────── -->
          <tr>
            <td class="px" style="padding:36px 40px 8px 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:14px;">
                <tr>
                  <td style="padding:18px 22px;">
                    <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:10px; letter-spacing:1.4px; text-transform:uppercase; color:#7c765f; padding-bottom:6px;">Your menu link</div>
                    <a href="{{ url('/'.$restaurant->slug) }}" class="link-u" style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:16px; font-weight:600; letter-spacing:-0.2px; color:#A8863C; word-break:break-all;">
                      {{ parse_url(config('app.url'), PHP_URL_HOST) }}/{{ $restaurant->slug }}
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ───────── Steps section ───────── -->
          <tr>
            <td class="px" style="padding:36px 40px 0 40px;">
              <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:11px; letter-spacing:1.6px; text-transform:uppercase; color:#6B7A4F;">
                Three steps to service
              </div>
            </td>
          </tr>

          <!-- Step rows -->
          <tr>
            <td class="px" style="padding:20px 40px 0 40px;">

              <!-- Step 1 -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:14px;">
                <tr>
                  <td valign="top" style="padding:20px 22px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td valign="top" width="44" style="padding-right:16px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="36" height="36" style="background-color:#F6F1E8; border-radius:50%;">
                            <tr><td align="center" valign="middle" class="editorial" style="font-family:'Instrument Serif',Georgia,serif; font-style:italic; font-size:17px; color:#4F5C3A; line-height:36px;">01</td></tr>
                          </table>
                        </td>
                        <td valign="top">
                          <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:16px; font-weight:500; letter-spacing:-0.3px; color:#0F0F10;">Build your menu</div>
                          <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:13.5px; line-height:20px; color:rgba(15,15,16,0.55); padding-top:4px;">Add your dishes and categories from the dashboard, or photograph your paper menu and let the AI scanner import everything in seconds.</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <div style="font-size:0; line-height:10px;">&nbsp;</div>

              <!-- Step 2 -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:14px;">
                <tr>
                  <td valign="top" style="padding:20px 22px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td valign="top" width="44" style="padding-right:16px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="36" height="36" style="background-color:#F6F1E8; border-radius:50%;">
                            <tr><td align="center" valign="middle" class="editorial" style="font-family:'Instrument Serif',Georgia,serif; font-style:italic; font-size:17px; color:#4F5C3A; line-height:36px;">02</td></tr>
                          </table>
                        </td>
                        <td valign="top">
                          <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:16px; font-weight:500; letter-spacing:-0.3px; color:#0F0F10;">Print your QR code</div>
                          <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:13.5px; line-height:20px; color:rgba(15,15,16,0.55); padding-top:4px;">Generate your table QR, download the print-ready file, and place one on every table. No app for your guests, they just scan.</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <div style="font-size:0; line-height:10px;">&nbsp;</div>

              <!-- Step 3 -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:14px;">
                <tr>
                  <td valign="top" style="padding:20px 22px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td valign="top" width="44" style="padding-right:16px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="36" height="36" style="background-color:#F6F1E8; border-radius:50%;">
                            <tr><td align="center" valign="middle" class="editorial" style="font-family:'Instrument Serif',Georgia,serif; font-style:italic; font-size:17px; color:#4F5C3A; line-height:36px;">03</td></tr>
                          </table>
                        </td>
                        <td valign="top">
                          <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:16px; font-weight:500; letter-spacing:-0.3px; color:#0F0F10;">Go live &amp; share</div>
                          <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:13.5px; line-height:20px; color:rgba(15,15,16,0.55); padding-top:4px;">Set your menu live, then share the link on Instagram, WhatsApp, or Google, wherever your guests find you.</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- ───────── Quote band (dark) ───────── -->
          <tr>
            <td class="px" style="padding:36px 40px 0 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0F0F10; border-radius:16px;">
                <tr>
                  <td style="padding:44px 32px 30px 32px;">
                    <div class="editorial" style="font-family:'Instrument Serif',Georgia,serif; font-style:italic; font-size:24px; line-height:31px; letter-spacing:-0.3px; color:#F6F1E8;">
                      &ldquo;It felt like the restaurant <span style="color:#A8B388;">cared about us</span> before we even ordered.&rdquo;
                    </div>

                    <!-- spacer above attribution -->
                    <div style="font-size:0; line-height:26px; height:26px;">&nbsp;</div>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td align="right">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                              <td valign="middle" style="padding-right:12px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="38" height="38" style="background-color:#E8DCCB; border-radius:50%;">
                                  <tr><td align="center" valign="middle" class="editorial" style="font-family:'Instrument Serif',Georgia,serif; font-style:italic; font-size:17px; color:#0F0F10; line-height:38px;">S</td></tr>
                                </table>
                              </td>
                              <td valign="middle" align="left">
                                <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:13px; font-weight:500; color:#F6F1E8;">Sushi By Ahmad</div>
                                <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:11.5px; letter-spacing:0.4px; color:rgba(246,241,232,0.55);">Restaurant owner</div>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ───────── Help row ───────── -->
          <tr>
            <td class="px" style="padding:32px 40px 8px 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td class="stack" valign="middle" width="62%" style="padding-right:16px;">
                    <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:15px; font-weight:500; letter-spacing:-0.2px; color:#0F0F10;">Need a hand getting set up?</div>
                    <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:13.5px; line-height:20px; color:rgba(15,15,16,0.55); padding-top:3px;">Reply to this email or reach our team anytime, we're glad to help you get your first service running.</div>
                  </td>
                  <td class="stack center-mobile" valign="middle" width="38%" align="right">
                    <a href="{{ route('contact') }}" class="btn-ghost" style="display:inline-block; background-color:#F6F1E8; color:#0F0F10; font-family:'Geist',Helvetica,Arial,sans-serif; font-size:13.5px; font-weight:500; line-height:42px; padding:0 20px; border-radius:999px; border:1px solid #0F0F10;">
                      Contact us
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ───────── Divider ───────── -->
          <tr>
            <td class="px" style="padding:28px 40px 0 40px;">
              <div style="height:1px; background-color:rgba(15,15,16,0.10); font-size:0; line-height:1px;">&nbsp;</div>
            </td>
          </tr>

          <!-- ───────── Footer ───────── -->
          <tr>
            <td class="px" style="padding:24px 40px 32px 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td valign="top">
                    <div style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:12px; color:#7c765f; line-height:18px;">
                      Qayema, the digital menu your restaurant deserves.<br />
                      by Lebify Group
                    </div>
                  </td>
                  <td valign="top" align="right">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="padding-left:16px;"><a href="{{ route('contact') }}" class="link-u" style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:12px; color:#7c765f; border-bottom:1px solid #cfc6b2; padding-bottom:1px;">Help</a></td>
                        <td style="padding-left:16px;"><a href="{{ route('privacy') }}" class="link-u" style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:12px; color:#7c765f; border-bottom:1px solid #cfc6b2; padding-bottom:1px;">Privacy</a></td>
                        <td style="padding-left:16px;"><a href="{{ route('terms') }}" class="link-u" style="font-family:'Geist',Helvetica,Arial,sans-serif; font-size:12px; color:#7c765f; border-bottom:1px solid #cfc6b2; padding-bottom:1px;">Terms</a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>
        <!-- /Card -->

        <!-- Sub-footer -->
        <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">
          <tr>
            <td class="px" align="center" style="padding:22px 40px 8px 40px; font-family:'Geist',Helvetica,Arial,sans-serif; font-size:11px; line-height:18px; color:#9b937e;">
              You're receiving this because you created a Qayema account for {{ $restaurant->name }}.<br />
              &copy; {{ date('Y') }} Lebify Group. All rights reserved.
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>

</body>
</html>
