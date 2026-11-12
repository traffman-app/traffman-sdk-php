# Changelog

## 1.0.0 – 2026-11-12

- Client: confirmation, info (`iam`), dashboard, settings update.
- Content: push (with batching), pushed external IDs, statistics, targeted content.
- Health checks: common, connection callback, content URL.
- Webhook handlers of the client confirmation and the connection health check.
- Content blocks: signed script URL, redirect signatures and URLs.
- Transports: PSR-18, cURL, mock. Middleware: retry, cache (own or PSR-16 storage), headers, closures.
- Typed response models; each keeps the decoded response data in `$raw`. The model constructors are internal:
  create the models with `fromArray()`.
