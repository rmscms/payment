## Changelog

### [1.0.2] - 2025-11-23
- Added `payment:install` command to publish config, run core migrations, install driver migrations, and inject the admin sidebar menu stub.
- Introduced publishable sidebar stub for CMS integration.

### [1.0.1] - 2025-11-18
- Added detailed logging for Zarinpal initialization and verification (`payment.zarinpal.*` channels).
- Normalized Zarinpal metadata payload (forces string values for `order_id`, `user_id`, etc.).
- Improved Checkout API error message to surface the gateway’s actual response.

### [1.0.0] - 2025-11-18
- Initial release.

