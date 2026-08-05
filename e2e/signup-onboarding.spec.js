const { test, expect } = require('@playwright/test');

function uniqueEmail(prefix) {
    return `${prefix}.${Date.now()}.${Math.random().toString(36).slice(2, 8)}@example.test`;
}

function uniquePhone() {
    return `070${String(Date.now()).slice(-7)}`;
}

async function fillSignupForm(page, {
    name,
    email,
    phone = uniquePhone(),
    password = 'Password123!',
}) {
    await page.goto('/register', { waitUntil: 'networkidle' });
    await page.getByLabel('Your Full Name').fill(name);
    await page.getByLabel('Email Address').fill(email);
    await page.getByLabel('Phone (WhatsApp)').fill(phone);
    await page.locator('#password').fill(password);
    await page.locator('#password-confirm').fill(password);
    await page.getByLabel(/I agree to/i).check();
}

async function submitSignup(page) {
    const validity = await page.locator('#saas-register-form').evaluate((form) => {
        const invalid = [...form.elements]
            .filter((element) => element.willValidate && !element.checkValidity())
            .map((element) => ({
                id: element.id,
                name: element.name,
                message: element.validationMessage,
            }));

        return {
            valid: form.checkValidity(),
            invalid,
        };
    });

    expect(validity.invalid).toEqual([]);
    await page.getByRole('button', { name: 'Create account with password' }).click();
}

async function expectDashboardOnboarding(page) {
    await page.waitForURL(/\/admin\/dashboard/);
    await expect(page.getByText('Continue school setup')).toBeVisible();
    await expect(page.locator('[data-toshi-root]')).toBeVisible();
    await expect(page.locator('textarea[placeholder*="Message Toshi"]:visible').first()).toBeVisible();
}

async function sendToshiMessage(page, text) {
    const composer = page.locator('textarea[placeholder*="Message Toshi"]:visible').last();
    await composer.click();
    await composer.pressSequentially(text);

    const sendButton = page.locator('button[title="Send"]:visible').last();
    await expect(sendButton).toBeEnabled();
    await sendButton.click();
}

function toshiText(page, text) {
    return page.getByText(text).last();
}

test.describe('signup and onboarding', () => {
    test('email signup reaches dashboard and Toshi onboarding without false validation', async ({ page }) => {
        await fillSignupForm(page, {
            name: 'Grace Nakato',
            email: uniqueEmail('e2e.signup'),
        });

        await submitSignup(page);
        await expectDashboardOnboarding(page);
    });

    test('Toshi onboarding asks for school name, curriculum, then academic year', async ({ page }) => {
        await fillSignupForm(page, {
            name: 'Ruth Namazzi',
            email: uniqueEmail('e2e.toshi'),
        });

        await submitSignup(page);
        await expectDashboardOnboarding(page);

        await sendToshiMessage(page, 'Sunrise Academy');
        await expect(toshiText(page, '🏫 Sunrise Academy')).toBeVisible();
        await expect(toshiText(page, 'Is the name correct?')).toBeVisible();

        await sendToshiMessage(page, 'yes');
        await expect(toshiText(page, 'School name updated to')).toContainText('Sunrise Academy');
        await expect(toshiText(page, 'Which curriculum does your school follow?')).toBeVisible();

        await sendToshiMessage(page, 'UNEB');
        await expect(toshiText(page, 'Curriculum set to')).toContainText('UNEB');
        await expect(toshiText(page, "Let's set your academic year next")).toBeVisible();
    });

    test('same first name signups still reach onboarding', async ({ browser }) => {
        for (const suffix of ['a', 'b']) {
            const context = await browser.newContext();
            const page = await context.newPage();

            await fillSignupForm(page, {
                name: `Grace ${suffix.toUpperCase()}`,
                email: uniqueEmail(`e2e.grace.${suffix}`),
                phone: uniquePhone(),
            });

            await submitSignup(page);
            await expectDashboardOnboarding(page);
            await context.close();
        }
    });

    test('Google OAuth redirect is available when a test account is configured', async ({ page }) => {
        test.skip(!process.env.PLAYWRIGHT_GOOGLE_TEST_EMAIL, 'Google OAuth test account not configured');
        await page.goto('/register', { waitUntil: 'networkidle' });
        await page.getByRole('button', { name: /Continue with Google/i }).click();
        await expect(page).toHaveURL(/accounts\.google\.com|google/);
    });
});
