# Email Notification Module

## Files

- `app/core/AssignmentMailer.php`
- `app/core/GoogleAppsScriptTransport.php`
- `app/core/SmtpTransport.php`
- `app/config/mail_secret.example.php`
- private `app/config/mail_secret.php`
- `integrations/google-apps-script/`

## Operation

The mailer builds assignment/return notifications and logs delivery outcomes. A transport sends the message either through Google Apps Script over HTTPS or direct SMTP where supported.

Shared hosting often blocks outbound SMTP ports. The HTTPS transport is therefore the preferred fallback. Provider acceptance means the message was accepted for delivery; it does not create an item in a mailbox Sent Items folder.

## Security

Never commit Apps Script secrets, SMTP passwords, API keys, or mail logs containing sensitive content. Rotate a secret immediately if it appears in a screenshot, commit, or support conversation.
