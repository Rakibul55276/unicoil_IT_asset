# Google Apps Script mail gateway

1. Sign in to `unicoil43@gmail.com` and open https://script.google.com/.
2. Create a new project named `IT Asset Mail Gateway`.
3. Replace the editor contents with `Code.gs` from this folder and save.
4. Open **Project Settings** > **Script properties** and add `ASSET_MAIL_SECRET` with a long random value (at least 32 characters).
5. Select **Deploy** > **New deployment** > **Web app**.
6. Set **Execute as** to **Me** and access to **Anyone**, then authorize Gmail access and deploy.
7. Copy the deployed URL ending in `/exec`.
8. On the hosting server, copy `app/config/mail_secret.example.php` to `app/config/mail_secret.php` and enter the same URL and secret.

Never put the shared secret in source control or send it in chat.
