# Testing

WhatsApp integration tests use PHPUnit with Laravel's testing utilities.

---

## Test Suites

### Feature Tests

| File | Tests | What It Covers |
|---|---|---|
| `tests/Feature/WhatsApp/WebhookValidationTest.php` | 7 | Inbound webhook FormRequest validation rules |
| `tests/Feature/WhatsApp/OutboundNotificationTest.php` | 4 | Phone helper utilities and outbound service methods |

### Factories

| Factory | Model |
|---|---|
| `WhatsAppUserFactory` | `WhatsAppUser` |
| `MessageDeliveryLogFactory` | `MessageDeliveryLog` |

---

## Running Tests

```bash
# Run all WhatsApp tests
php artisan test tests/Feature/WhatsApp/

# Run with verbose output
php artisan test tests/Feature/WhatsApp/ --verbose

# Run a specific test file
php artisan test tests/Feature/WhatsApp/WebhookValidationTest.php

# Run a specific test method
php artisan test tests/Feature/WhatsApp/WebhookValidationTest.php --filter=test_valid_webhook_passes
```

### Test Database

- `phpunit.xml` configures SQLite `:memory:` for tests
- WhatsApp environment variables are set in `phpunit.xml` for testing
- Some tests that depend on pivot tables or pre-existing database records may skip when run with in-memory SQLite

---

## WebhookValidationTest (7 tests)

Tests the `StoreWhatsAppWebhookRequest` FormRequest validation rules.

| Test | Description |
|---|---|
| `test_valid_webhook_passes` | Well-formed webhook payload with all required fields should pass validation |
| `test_missing_event_fails` | Payload without `event` field should fail |
| `test_invalid_event_fails` | Non-`messages.upsert` event should fail |
| `test_group_message_ignored` | `remoteJid` containing `@g.us` should fail |
| `test_own_message_ignored` | `fromMe=true` should fail |
| `test_invalid_phone_format` | Non-Uganda phone format should fail |
| `test_oversized_payload` | Payload exceeding 1MB should fail |

### Test Pattern

```php
// Tests use Laravel's Validator directly to avoid DB dependency
$validator = Validator::make($payload, (new StoreWhatsAppWebhookRequest())->rules());
$this->assertTrue($validator->fails());
$this->assertArrayHasKey('event', $validator->errors()->toArray());
```

---

## OutboundNotificationTest (4 tests)

Tests phone helper utilities and basic outbound service methods.

| Test | Description |
|---|---|
| `test_phone_normalisation` | `WhatsAppPhoneHelper::normalise()` handles various input formats |
| `test_phone_validation` | `WhatsAppPhoneHelper::validate()` correctly accepts/rejects phone formats |
| `test_send_text_formatting` | `WhatsAppPhoneHelper::formatMessage()` produces correctly formatted output |
| `test_outbound_service_resolves_phones` | `OutboundWhatsAppService::getParentPhones()` returns expected phone numbers (requires DB) |

---

## Factories

### WhatsAppUserFactory

```php
WhatsAppUser::factory()->create([
    'phone'   => '+256701234567',
    'user_id' => $user->id,
]);

// Opted-out user
WhatsAppUser::factory()->create([
    'phone'           => '+256712345678',
    'opted_in'        => false,
    'unsubscribed_at' => now(),
]);
```

### MessageDeliveryLogFactory

```php
MessageDeliveryLog::factory()->create([
    'phone'     => '+256701234567',
    'status'    => 'delivered',
    'flow_type' => 'grades',
    'direction' => 'outbound',
]);
```

---

## Writing New Tests

### Webhook Validation Tests

Use Laravel's `Validator` directly for rules-only tests (no DB needed):

```php
public function test_invalid_phone_rejected(): void
{
    $validator = Validator::make(
        [
            'event'     => 'messages.upsert',
            'remoteJid' => '+254701234567@s.whatsapp.net',
            'key'       => ['id' => 'abc123'],
        ],
        (new StoreWhatsAppWebhookRequest())->rules()
    );

    $this->assertTrue($validator->fails());
}
```

### Service Tests

Mock `WhatsAppService` HTTP calls to avoid real API calls:

```php
public function test_grades_notification_sent(): void
{
    Http::fake([
        '*/message/sendText/*' => Http::response(['key' => ['id' => 'mock_msg_1']], 200),
    ]);

    $student = User::factory()->create();
    $parent  = User::factory()->create();
    WhatsAppUser::factory()->create([
        'user_id' => $parent->id,
        'phone'   => '+256701234567',
    ]);

    $service = app(OutboundWhatsAppService::class);
    $service->notifyGradesPublished($examId);

    Http::assertSent(function ($request) {
        return Str::contains($request->url(), '/message/sendText/');
    });
}
```

### Delivery Log Tests

Test that delivery tracking works by checking the database:

```php
public function test_message_logged_on_send(): void
{
    Http::fake(['*/message/sendText/*' => Http::response(['key' => ['id' => 'test_123']], 200)]);

    $service = app(WhatsAppService::class);
    $service->sendText('+256701234567', 'Test message', 'test', 1);

    $this->assertDatabaseHas('message_delivery_log', [
        'whatsapp_message_id' => 'test_123',
        'phone'               => '+256701234567',
        'status'              => 'sent',
        'flow_type'           => 'test',
    ]);
}
```

---

## Known Test Limitations

| Limitation | Reason | Workaround |
|---|---|---|
| 3 tests SKIP with in-memory SQLite | Need `whatsapp_users` / pivot tables | Run with `--env=testing` and migrated database |
| No HTTP mock for Evolution API in existing tests | Initial tests focused on validation only | New tests should use `Http::fake()` |
| No coverage for multi-child parent flows | Feature not yet implemented when tests were written | To be added |
| No coverage for cost-optimization queue | Queue feature not yet implemented when tests were written | To be added |

---

## CI Integration

The test suite can be integrated into CI:

```yaml
# Example GitHub Actions job
test-whatsapp:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    - run: composer install
    - run: cp .env.example .env
    - run: php artisan key:generate
    - run: php artisan test tests/Feature/WhatsApp/
```

Note: The project's CI pipeline (`.github/workflows/klassapp-ci.yml`) is currently commented out. Uncomment and add this job to enable automated WhatsApp test runs.
