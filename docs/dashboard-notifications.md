# Dashboard notifications

Implemented on 2026-10-27. Both dashboards use the same persistent inbox and private Laravel Reverb refresh channel. Business notifications are stored before broadcasting; a disconnected browser can retrieve them later.

## Event coverage

| Event | Superadmin | Tenant |
| --- | --- | --- |
| Subscription checkout/request | Yes | Upon paid approval/activation |
| Verified trial request | Yes | Upon approval and tenant provisioning |
| Contact request | Yes | — |
| New tenant support ticket | Yes | — |
| Tenant support message | Yes | — |
| Support reply | — | Ticket author |
| Subscription expires | Yes | Primary admin and Admin role holders |
| Subscription within 30 days | — | Primary admin and Admin role holders |
| Subscription within 7 days | Yes | Primary admin and Admin role holders |
| Coupon expiration date passes | Yes | — |
| Coupon usage limit reached | Yes | — |

Only active superadmins and active tenant recipients receive new notifications. Trial requests are announced after OTP verification. Local support events and successfully mirrored outbound Etmam actions use the same observer; an external support service needs an inbound integration to notify this application about changes made solely on that external service.

## Runtime

Apply the central migration:

```sh
php artisan migrate --path=database/migrations/admin --force
```

For local development, `composer dev` starts the web server, regular queue worker, auction report worker, Vite, Reverb, and notification deadline worker. If the web server is already running, start these in separate terminals:

```sh
composer notifications:work
composer notifications:scan
```

The first runs Reverb; the second scans deadlines every minute and retries pending notification deliveries. Production should supervise Reverb continuously and run Laravel's scheduler every minute (`php artisan schedule:run`). The scanner is registered in `routes/console.php` with overlap protection. Run either the dedicated scanner worker or the scheduler in production. A one-off scan is available as `php artisan dashboard-notifications:scan`.

Local Reverb defaults to loopback `127.0.0.1:8081`. For deployment configure `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`, `REVERB_HOST`, `REVERB_PORT`, and `REVERB_SCHEME`; terminate HTTPS/WSS at the reverse proxy. Set matching `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` for all processes. Local fallbacks derive from APP_KEY. Allowed origins come from the application's configured central domains and application host, with localhost for development. CSP permits only the configured WebSocket endpoint. Restart long-running services after configuration changes.

## Delivery and access contract

- Provider: `DashboardNotificationServiceProvider`; observers run after successful transaction commit.
- Service: `DashboardNotificationService` explicitly switches tenant context for tenant recipients and tenant notification storage. The central delivery ledger uses deterministic per-event, per-recipient UUIDs to prevent duplicates, including after the user deletes an inbox item.
- Model/migration: `NotificationDelivery` and `2026_10_27_000100_create_dashboard_notification_delivery_tables.php`; standard notification rows reside in each recipient's database.
- Deadline service: `NotificationDeadlineScanner` scans the latest subscription, its current period, coupon deadlines and pending deliveries. It does not change subscription lifecycle or grant access. Reminders are once per threshold and subscription end date; a subscription already within seven days receives the seven-day reminder instead of simultaneous 30-day and seven-day reminders.
- Controller/request: `DashboardNotificationController`, `DashboardNotificationRequest`, `DashboardBroadcastAuthRequest`. Guard, active account status, own notifiable relationship, tenant context and exact own channel enforce access. Cross-user, cross-tenant and cross-dashboard channel requests are denied. CSRF applies to mutations and private-channel authentication.
- Routes: `admin.dashboard-notifications.*` and tenant `dashboard-notifications.*`, including feed, all, auth, read, read-all and destroy. Tenant endpoints require authentication/2FA and remain outside subscription/license gates so expiration notifications remain readable in an authenticated session.
- UI: shared navbar menu, inbox Blade view, and `resources/assets/js/shared/dashboard-notifications.js`. New notices update the menu and unread counter and show a toast. Read/delete/read-all changes refresh other connected tabs for the same account.
- Channels: `private-dashboard.admin.{userId}` and `private-dashboard.tenant.{tenantId}.user.{userId}`. Broadcasts contain only a refresh signal; authenticated HTTP requests retrieve notification content. No application secret is returned to the browser.
- Reconnect/BFCache refreshes reconcile stored notifications; visible disconnected tabs retry every five seconds, with a one-minute reconciliation while connected.
- This feature delivers in-app notifications. It does not send email or SMS.

## Validation

`DashboardNotificationsTest` covers all business categories, deadline boundaries, duplicate prevention, rollback behavior, read/delete ownership, correct guards and private-channel isolation even when both dashboard guards have sessions. Regression validation passed: 25 tests, 185 assertions across notifications, trial provisioning, registration checkout, ticket isolation/authorization, CSP and report queue contracts. `npm run build` succeeded after the frontend changes.

A real local WebSocket smoke test authenticated the tenant notification endpoint, subscribed to both tenant and superadmin private channels, created a tenant notice and a real temporary contact request, received both pushed refresh events and verified the persisted unread feed. All temporary records were removed. Visual browser verification was blocked because the disposable account has no license; automatic approval review rejected assigning a temporary license. No licensing or user access rules were changed for the browser test.
