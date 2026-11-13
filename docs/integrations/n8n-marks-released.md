# Marks released, for n8n

This is the contract for a school webhook when an exam set is released. The application does not send this webhook yet. The file is the contract the sender must follow. It is not a school guide.

This is not the demo webhook in [#1323](https://github.com/KlassApp-Foundation/KlassApp/pull/1323). That job is `SendDemoMarksReleaseWebhook`. It runs only for a demo school, only when a flag is on, only outside production, and it tries once. It also sends an exam label. Leave that job as it is.

## What is sent

One HTTPS POST per release. The body is counts and ids. It has no learner name, no mark, no phone number, no parent, and no report card.

```json
{
  "event": "marks.released",
  "release_id": 1,
  "school_id": 1,
  "term_id": 1,
  "exam_type_id": 9,
  "learners": 24,
  "classes": 2,
  "subjects": 4,
  "missing": 0,
  "absent": 6,
  "timestamp": 1763000000
}
```

`missing` is the number of marks that were never entered. `absent` is the number of ABS marks. Both already count as 0 on the report. The webhook does not repeat the marks.

The JSON is encoded with Unicode and slashes left as they are. The signature is over those exact bytes. A retry sends the same bytes.

## Signature

Headers:

- `Content-Type: application/json`
- `X-KlassApp-Timestamp`: the same unix seconds as `timestamp`
- `X-KlassApp-Signature`: `sha256=` plus HMAC-SHA256 of the raw body, hex, using the school's webhook secret

No secret in the URL. If the secret is empty, send nothing.

n8n must check the signature against the raw body. Do not parse the JSON and encode it again. Accept the call only when the timestamp header matches `timestamp`, and only when that time is within about 10 minutes. Answer first, then do the rest of the workflow. Drop a repeat of the same `release_id`, `event`, and `timestamp`.

## Retries and the log

The sender tries five times. It waits 10 seconds, then 30, then 60, then 120. A failed try is logged with the release id, the event name, and the HTTP status or the exception class. The log does not include the body, the secret, or the URL. When the tries are used up, one warning is logged with the same three fields.

A slow or failing webhook must not stop the release, and it must not change the released report.

## Example workflow

`docs/integrations/n8n-marks-released.workflow.json` is an n8n workflow you can import. It is inactive. It answers as soon as the POST arrives, then checks the signature in a Code node. Set the workflow static data key `klassappMarksSecret` to the same secret the school saved. Do not commit that secret.

The Webhook node must be set to pass the raw body through. If n8n has already parsed the JSON, the signature check in this example cannot be trusted, because the bytes will not match.
