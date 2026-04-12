<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║           PROBURST — PRODUCTION MAILER CONFIG               ║
 * ║  Socket-based SMTP (no PHPMailer dependency required)       ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * ── HOW TO SET UP ON HOSTINGER ──────────────────────────────────
 * 1. Log in to hPanel (hpanel.hostinger.com)
 * 2. Go to: Emails → Email Accounts → Create Email Account
 * 3. Create:  noreply@yourdomain.com  with a strong password
 * 4. Fill in MAILER_USER and MAILER_PASS below
 * 5. Set MAILER_DEV_MODE to false
 * 6. Set SITE_URL to your live domain
 * 7. Upload — done. Email works instantly.
 *
 * ── HOSTINGER SMTP SETTINGS (already correct below) ─────────────
 * Host : smtp.hostinger.com
 * Port : 465  (SSL)  — most reliable on Hostinger
 * Auth : Your full email address + password
 *
 * ── FOR LOCALHOST TESTING ───────────────────────────────────────
 * Keep MAILER_DEV_MODE = true
 * The reset link shows on screen instead of being emailed.
 */

// ════════════════════════════════════════════════════════════════
//  EDIT THESE VALUES
// ════════════════════════════════════════════════════════════════

define('MAILER_DEV_MODE',   true);                         // ← false on live server

define('SITE_URL',          'http://localhost/proburst');   // ← change to https://yourdomain.com/proburst on live

define('MAILER_HOST',       'smtp.hostinger.com');         // Do not change for Hostinger
define('MAILER_PORT',       465);                          // Do not change (SSL port)
define('MAILER_SECURE',     'ssl');                        // Do not change

define('MAILER_USER',       'noreply@yourdomain.com');     // ← YOUR Hostinger email address
define('MAILER_PASS',       'YourEmailPasswordHere');      // ← YOUR email password

define('MAILER_FROM',       'noreply@yourdomain.com');     // ← Same as MAILER_USER
define('MAILER_FROM_NAME',  'Proburst');

// ════════════════════════════════════════════════════════════════


/**
 * Send password reset email.
 * Returns: true = sent OK | ['dev'=>true,'link'=>$url] = dev mode | string = error message
 */
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {

    // Dev mode — skip SMTP, return link for on-screen display
    if (MAILER_DEV_MODE) {
        return ['dev' => true, 'link' => $resetLink];
    }

    $subject = 'Reset Your Proburst Password';

    // ──  HTML email ─────────────────────────────────────────
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password</title>
</head>
<body style="margin:0;padding:0;background-color:#0d0d0d;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
         style="background:#0d0d0d;padding:48px 20px">
    <tr>
      <td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0"
               style="max-width:560px;width:100%;background:#111111;border:1px solid #1e1e1e;
                      border-radius:16px;overflow:hidden">

          <!-- ── HEADER ── -->
          <tr>
            <td style="background:#e63946;padding:30px 40px;text-align:center">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <h1 style="color:#ffffff;margin:0;font-size:26px;
                               font-weight:800;letter-spacing:2px;
                               font-family:Arial,Helvetica,sans-serif">
                      PROBURST
                    </h1>
                    <p style="color:rgba(255,255,255,0.75);margin:5px 0 0;
                              font-size:12px;letter-spacing:1px">
                      BE FIT. BE PRO.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ── BODY ── -->
          <tr>
            <td style="padding:40px 40px 32px">

              <h2 style="color:#ffffff;margin:0 0 14px;font-size:20px;font-weight:700">
                Password Reset Request
              </h2>

              <p style="color:#aaaaaa;font-size:14px;line-height:1.75;margin:0 0 8px">
                Hi <strong style="color:#ffffff">' . htmlspecialchars($toName) . '</strong>,
              </p>
              <p style="color:#aaaaaa;font-size:14px;line-height:1.75;margin:0 0 28px">
                We received a request to reset the password for your Proburst account
                linked to this email. Click the button below to choose a new password.
                This link will expire in <strong style="color:#ffffff">1 hour</strong>.
              </p>

              <!-- ── CTA BUTTON ── -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding-bottom:32px">
                    <a href="' . $resetLink . '"
                       style="display:inline-block;background:#e63946;color:#ffffff;
                              text-decoration:none;padding:15px 40px;
                              border-radius:8px;font-size:15px;font-weight:700;
                              letter-spacing:0.5px;font-family:Arial,Helvetica,sans-serif">
                      RESET MY PASSWORD &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <p style="color:#555555;font-size:12px;margin:0 0 6px">
                Button not working? Copy and paste this link in your browser:
              </p>
              <p style="margin:0 0 30px">
                <a href="' . $resetLink . '"
                   style="color:#e63946;font-size:12px;word-break:break-all;
                          text-decoration:none">
                  ' . $resetLink . '
                </a>
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="border-top:1px solid #1e1e1e;padding-top:24px">
                  <p style="color:#444444;font-size:12px;line-height:1.65;margin:0">
                    ⚠ If you did not request a password reset, no action is needed.
                    Your password will remain unchanged and this link will expire automatically.
                  </p>
                </td></tr>
              </table>

            </td>
          </tr>

          <!-- ── FOOTER ── -->
          <tr>
            <td style="background:#0d0d0d;padding:20px 40px;
                       border-top:1px solid #1a1a1a;text-align:center">
              <p style="color:#333333;font-size:11px;margin:0;line-height:1.6">
                &copy; ' . date('Y') . ' Proburst &mdash; Intra Life Pvt. Ltd.
                &nbsp;&bull;&nbsp; All rights reserved<br>
                This is an automated email. Please do not reply.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

    // ── Plain text fallback ──────────────────────────────────────────
    $text = implode("\n", [
        "PROBURST — Password Reset",
        str_repeat("-", 40),
        "",
        "Hi " . $toName . ",",
        "",
        "We received a request to reset your Proburst account password.",
        "Click the link below (valid for 1 hour):",
        "",
        $resetLink,
        "",
        "If you did not request this, ignore this email.",
        "Your password will remain unchanged.",
        "",
        str_repeat("-", 40),
        "Proburst — Intra Life Pvt. Ltd.",
        "This is an automated email. Please do not reply.",
    ]);

    return _smtpSend(
        MAILER_FROM, MAILER_FROM_NAME,
        $toEmail, $toName,
        $subject, $html, $text
    );
}


/**
 * Low-level SMTP sender using PHP native sockets.
 * Supports SSL (port 465) and STARTTLS (port 587).
 * No external library required — works on any PHP 7.2+ host.
 *
 * @return true|string   true on success, error message on failure
 */
function _smtpSend($from, $fromName, $to, $toName, $subject, $html, $text) {
    $host    = MAILER_HOST;
    $port    = (int) MAILER_PORT;
    $secure  = MAILER_SECURE;
    $user    = MAILER_USER;
    $pass    = MAILER_PASS;
    $timeout = 20;

    // ── Open connection ──────────────────────────────────────────────
    $errno  = 0;
    $errstr = '';
    $remote = ($secure === 'ssl') ? "ssl://$host" : $host;

    $socket = @fsockopen($remote, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        return "SMTP connection failed to $host:$port — $errstr ($errno). "
             . "Check MAILER_HOST/PORT in config/mailer.php";
    }

    stream_set_timeout($socket, $timeout);

    // ── Read one SMTP response ───────────────────────────────────────
    $readResponse = function () use ($socket) {
        $data = '';
        while (!feof($socket)) {
            $line  = fgets($socket, 515);
            if ($line === false) break;
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break; // final line
        }
        return trim($data);
    };

    // ── Send command and optionally assert code ──────────────────────
    $send = function ($cmd, $expectCode = null) use ($socket, $readResponse) {
        fwrite($socket, $cmd . "\r\n");
        $r = $readResponse();
        if ($expectCode !== null) {
            if (strpos($r, (string)$expectCode) !== 0) {
                throw new RuntimeException("SMTP[$expectCode] after '$cmd': $r");
            }
        }
        return $r;
    };

    try {
        $readResponse(); // server greeting

        if ($secure === 'tls') {
            $send("EHLO proburst.com", 250);
            $send("STARTTLS",          220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException("STARTTLS negotiation failed");
            }
        }

        $send("EHLO proburst.com",       250);
        $send("AUTH LOGIN",              334);
        $send(base64_encode($user),      334);
        $send(base64_encode($pass),      235);
        $send("MAIL FROM:<$from>",       250);
        $send("RCPT TO:<$to>",           250);
        $send("DATA",                    354);

        // ── Compose MIME multipart message ───────────────────────────
        $boundary = 'b_' . md5(uniqid('', true));
        $msgId    = '<' . time() . '.' . md5($to) . '@proburst.com>';
        $encFrom  = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $encTo    = '=?UTF-8?B?' . base64_encode($toName)   . '?=';
        $encSubj  = '=?UTF-8?B?' . base64_encode($subject)  . '?=';

        $headers = implode("\r\n", [
            "From: $encFrom <$from>",
            "To: $encTo <$to>",
            "Subject: $encSubj",
            "Date: " . date('r'),
            "Message-ID: $msgId",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
            "X-Mailer: Proburst-Mailer/2.0",
        ]);

        $body = implode("\r\n", [
            "--$boundary",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($text), 76, "\r\n"),
            "--$boundary",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($html), 76, "\r\n"),
            "--$boundary--",
        ]);

        // Send message data then end with a single dot on its own line
        fwrite($socket, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
        $resp = $readResponse();
        if (strpos($resp, '250') !== 0) {
            throw new RuntimeException("Message rejected by server: $resp");
        }

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;

    } catch (RuntimeException $e) {
        if (is_resource($socket)) fclose($socket);
        error_log('[Proburst SMTP] ' . $e->getMessage());
        return $e->getMessage();
    }
}
