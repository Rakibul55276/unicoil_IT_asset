function doPost(e) {
  try {
    var data = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    var expectedSecret = PropertiesService.getScriptProperties().getProperty('ASSET_MAIL_SECRET') || '';
    if (!expectedSecret || !safeEqual_(String(data.secret || ''), expectedSecret)) {
      return json_({ok: false, error: 'Unauthorized request'});
    }

    var to = String(data.to || '').trim();
    var subject = String(data.subject || '').trim();
    var html = String(data.html || '');
    if (!validEmail_(to) || !subject || subject.length > 250 || !html) {
      return json_({ok: false, error: 'Invalid recipient, subject, or message body'});
    }

    var cc = Array.isArray(data.cc) ? data.cc.map(String).filter(validEmail_).slice(0, 20) : [];
    var files = Array.isArray(data.attachments) ? data.attachments.slice(0, 10) : [];
    var totalBytes = 0;
    var blobs = files.map(function(file) {
      var bytes = Utilities.base64Decode(String(file.content || ''));
      totalBytes += bytes.length;
      if (totalBytes > 15 * 1024 * 1024) throw new Error('Attachments exceed the 15 MB gateway limit');
      return Utilities.newBlob(bytes, String(file.mime || 'application/octet-stream'), String(file.name || 'attachment'));
    });

    var options = {
      htmlBody: html,
      name: String(data.fromName || 'UNICOIL IT Asset Management').slice(0, 100),
      attachments: blobs
    };
    if (cc.length) options.cc = cc.join(',');
    if (validEmail_(String(data.replyTo || ''))) options.replyTo = String(data.replyTo);

    GmailApp.sendEmail(to, subject, plainText_(html), options);
    return json_({ok: true, requestId: Utilities.getUuid()});
  } catch (error) {
    return json_({ok: false, error: String(error && error.message ? error.message : error)});
  }
}

function json_(value) {
  return ContentService.createTextOutput(JSON.stringify(value)).setMimeType(ContentService.MimeType.JSON);
}

function validEmail_(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
}

function plainText_(html) {
  return String(html || '')
    .replace(/<style[\s\S]*?<\/style>/gi, '')
    .replace(/<script[\s\S]*?<\/script>/gi, '')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/p>|<\/tr>|<\/h[1-6]>/gi, '\n')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/&#039;/gi, "'")
    .replace(/[ \t]+/g, ' ')
    .replace(/\n\s+/g, '\n')
    .trim();
}

function safeEqual_(left, right) {
  var a = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, left);
  var b = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, right);
  var difference = a.length ^ b.length;
  for (var i = 0; i < Math.min(a.length, b.length); i++) difference |= a[i] ^ b[i];
  return difference === 0;
}
