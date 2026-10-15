# Design system changelog

## 2026-10-15

The Toshi entry pill shows only on dashboard and onboarding pages, and only for school admins and head teachers. Every other role and every other page keeps the side handle alone as the launcher, so the pill never covers list rows. The 80px collapse now follows whichever element really scrolls (the content area at 1280 and wider), and the phone drawer's Tab and Shift+Tab cycle through every visible control, including the items of an expanded group.

The Toshi entry pill no longer moves to dodge list rows. It stays fixed, 24px from the right and 24px from the bottom, or 16px above a bottom `data-sticky-bar`. After the page scrolls more than 80px it collapses to a 44px circle: the K stays in the ring, the label hides, and the aria-label stays the same. It expands again at the top. The page keeps 100px of extra bottom padding while the pill is showing.
