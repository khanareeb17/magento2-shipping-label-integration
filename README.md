# AkStackPro Shipping Label Integration for Magento 2

Magento 2 extension that integrates **UPS WorldShip** and **Endicia (Stamps.com / USPS)** shipping APIs directly into the Magento Admin. Store admins can fetch live shipping rates and generate shipping labels from the **Create Shipment** screen without leaving Magento.

| | |
|---|---|
| **Module name** | `AkStackPro_ShippingLabelIntegration` |
| **Composer package** | `akstackpro/module-shipping-label-integration` |
| **Version** | 2.0.0 |
| **Magento compatibility** | Magento 2.4.x (tested on 2.4.7-p5) |
| **Vendor** | Ak Stack Pro |

---

## What This Module Does

This module is built for merchants who ship orders via **UPS** and/or **USPS (Endicia)** and want to:

1. **View live shipping rates** for an order during shipment creation
2. **Generate shipping labels** (UPS GIF / Endicia PNG-PDF) from the Admin
3. **Automatically create the Magento shipment** with tracking number and shipping label attached
4. **Send the shipment notification email** to the customer

### Supported carriers

| Carrier | API | Admin button |
|---------|-----|--------------|
| UPS WorldShip | UPS OAuth 2.0 + Rating / Shipment APIs | **Get Worldship Shipping Rates** |
| Endicia / USPS | Stamps Endicia OAuth + Rates / Labels APIs | **Get Endicia Shipping Rates** |

### High-level workflow

```
Order (invoiced) → Admin: Create Shipment
    → Click "Get Worldship Shipping Rates" OR "Get Endicia Shipping Rates"
    → Select a rate from the popup
    → Enter package dimensions (Weight, Length, Width, Height)
    → Click "Submit & Generate Label"
    → Module calls carrier API → Creates shipment + tracking + label in Magento
```

---

## Requirements

| Requirement | Version |
|-------------|---------|
| Magento | 2.4.x (Open Source / Adobe Commerce) |
| PHP | 8.1, 8.2, or 8.3 |
| `Magento_Shipping` | Enabled |
| **Store Information** | Configured under **Stores → Configuration → General → Store Information** (used as ship-from address) |
| Carrier credentials | Valid UPS and/or Endicia API credentials for the carriers you plan to use |
| Invoiced orders | Orders must be **invoiced** before a shipment can be created |

### Required AkStackPro modules

This module **cannot run standalone**. You must install **AkStackPro Core** first — it provides the shared admin menu, configuration tab, and ACL structure used by all Ak Stack Pro extensions.

| Module | Composer package | Repository |
|--------|------------------|------------|
| **AkStackPro Core** | `akstackpro/module-core` | [github.com/khanareeb17/magento2-module-core](https://github.com/khanareeb17/magento2-module-core) |

**Install order:**

1. AkStackPro Core
2. AkStackPro Shipping Label Integration *(this module)*

---

## Installation

### Option A — Composer (recommended)

Install Core first, then this module:

```bash
composer require akstackpro/module-core
composer require akstackpro/module-shipping-label-integration
```

Then enable and upgrade:

```bash
php bin/magento module:enable AkStackPro_Core AkStackPro_ShippingLabelIntegration
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

> If the AkStackPro packages are not on Packagist, add the GitHub repositories to your root `composer.json` before running `composer require`:
>
> ```json
> "repositories": [
>     {
>         "type": "vcs",
>         "url": "https://github.com/khanareeb17/magento2-module-core"
>     },
>     {
>         "type": "vcs",
>         "url": "https://github.com/khanareeb17/magento2-module-shipping-label-integration"
>     }
> ]
> ```

### Option B — Manual installation

1. Clone or download the modules into `app/code/AkStackPro/`:

   ```
   app/code/AkStackPro/
   ├── Core/
   └── ShippingLabelIntegration/
   ```

   - Core: [magento2-module-core](https://github.com/khanareeb17/magento2-module-core)

2. Enable and upgrade:

   ```bash
   php bin/magento module:enable AkStackPro_Core AkStackPro_ShippingLabelIntegration
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

3. Assign ACL resources to your Admin role under **System → Permissions → User Roles**:

   | Resource | Purpose |
   |----------|---------|
   | **Ak Stack Pro → Shipping Label Integration** | Admin sidebar menu |
   | **Ak Stack Pro Configuration → UPS & Endicia Web Services** | Configuration access |

---

## Configuration

Navigate to:

**Admin → Ak Stack Pro → Shipping Label Integration → Settings**

Or:

**Stores → Configuration → Ak Stack Pro → UPS & Endicia Web Services**

### General

| Field | Description |
|-------|-------------|
| **Enable Module** | Master switch. When disabled, rate buttons and API calls are not shown/used. |
| **Debug Logging** | When enabled, API request/response details are written to `var/log/system.log`. |

### Endicia Shipping Integration

Configure these fields to use **USPS via Endicia**:

| Field | Description |
|-------|-------------|
| **Shipping API End Point** | Base URL for Endicia API (e.g. `https://api.testing.stampsendicia.com/sera/v1/`). The module appends `rates` or `labels` to this URL. |
| **Client ID** | Endicia OAuth client ID (stored encrypted). |
| **Password** | Endicia client secret (stored encrypted). |
| **Refresh Token** | OAuth refresh token for token renewal (stored encrypted). |
| **Endicia Auth URL** | OAuth authorization URL. Default (sandbox): `https://signin.testing.stampsendicia.com/authorize` |
| **Endicia Access Token URL** | OAuth token endpoint. Default (sandbox): `https://signin.testing.stampsendicia.com/oauth/token` |
| **Callback URL** | OAuth callback URL registered with Endicia. |
| **Endicia Mail Classes** | Multiselect of USPS service + packaging combinations to show on the shipment screen. Options are loaded live from the Endicia Rates API after credentials are saved. |

**Configuration order for Endicia Mail Classes:**

1. Save all Endicia API credentials first
2. Reload the configuration page
3. The **Endicia Mail Classes** dropdown will populate from the API
4. Select the service/package types you want available during shipment creation
5. Save configuration again

> If no mail classes are selected, all rates returned by the Endicia API are shown on the shipment page.

### UPS WorldShip Integration

Configure these fields to use **UPS**:

| Field | Description |
|-------|-------------|
| **UPS Account Number** | Your UPS shipper account number. |
| **Shipping API End Point** | UPS API base URL. Default (sandbox): `https://wwwcie.ups.com/api` |
| **Client ID** | UPS OAuth client ID (stored encrypted). |
| **Secret ID** | UPS OAuth client secret (stored encrypted). |
| **UPS Access Token URL** | UPS OAuth token endpoint. Default (sandbox): `https://wwwcie.ups.com/security/v1/oauth/token` |

### Default sandbox URLs

The module ships with testing/sandbox defaults in `etc/config.xml`. Replace these with **production URLs** before going live.

---

## How to Use (Admin)

### Step 1 — Prepare the order

1. Create and process the order as usual
2. **Create an invoice** for the order (required before shipping)
3. Go to **Sales → Orders → [Order] → Ship**

### Step 2 — Fetch shipping rates

When the module is **enabled**, two buttons appear on the Create Shipment page under **Shipping Information**:

- **Get Worldship Shipping Rates** — UPS rates
- **Get Endicia Shipping Rates** — USPS / Endicia rates

Click the button for your carrier. A popup opens with available rates and costs.

### Step 3 — Generate the label

1. Select a shipping rate (radio button)
2. Enter package details:
   - **Weight** (lbs)
   - **Length** (in)
   - **Width** (in)
   - **Height** (in)
3. Click **Submit & Generate Label**

The module will:

- Call the carrier API to create the label
- Create the Magento shipment for all shippable items
- Add a tracking number (UPS or USPS)
- Attach the shipping label to the shipment
- Send the shipment email notification
- Redirect back to the order view page

### UPS label details

- Label format: **GIF**
- Service code from selected rate is sent to UPS Shipment API
- Tracking carrier code: `ups`

### Endicia label details

- Label format: **PNG** (4x8.25 doctab), retrieved via URL from API response
- Label file is downloaded to `pub/media/endicia_labels/`
- Tracking carrier code: `usps`

---

## Console Command

A CLI command is registered for testing API connectivity from the terminal.

```bash
bin/magento ups:auth:test
```

| | |
|---|---|
| **Command** | `ups:auth:test` |
| **Description** | Sends a test request to verify module and API connectivity |
| **Requirement** | Module must be **enabled** in configuration |

**Behavior:**

- If the module is disabled, outputs: *"The module is disabled. Please enable it to perform this action"*
- If enabled, attempts an Endicia rates API test call and prints the JSON response or *"Request Failure."*

**Example:**

```bash
bin/magento ups:auth:test
```

> **Note:** Enable **Debug** in configuration and check `var/log/system.log` for detailed API logs when troubleshooting command or shipment issues.

---

## API Endpoints (Admin AJAX)

The shipment form uses AJAX controllers to generate labels:

| Carrier | Route | Controller |
|---------|-------|------------|
| UPS | `admin/shippinglabelintegration/shipping/generate` | `Generate` |
| Endicia | `admin/shippinglabelintegration/shipping/endiciagenerate` | `EndiciaGenerate` |

POST parameters sent from the shipment popup:

| Parameter | Description |
|-----------|-------------|
| `selectedRate` | Selected service code (UPS) or formatted service type (Endicia) |
| `weight` | Package weight in pounds |
| `length` | Package length in inches |
| `width` | Package width in inches |
| `height` | Package height in inches |
| `orderId` | Magento order entity ID |

---

## Module Structure

```
AkStackPro/ShippingLabelIntegration/
├── Block/Adminhtml/Order/Shipment/
│   └── NewShipment.php          # Fetches UPS & Endicia rates for shipment UI
├── Console/Command/
│   └── UpdateInventoryCommand.php  # CLI: ups:auth:test
├── Controller/Adminhtml/Shipping/
│   ├── Generate.php             # UPS label generation (AJAX)
│   └── EndiciaGenerate.php      # Endicia label generation (AJAX)
├── Helper/
│   └── Data.php                 # Store info, region mapping, UPS service codes
├── Model/
│   ├── Config.php               # Configuration reader
│   ├── Config/Source/
│   │   └── ConfigOption.php     # Endicia mail classes multiselect source
│   ├── Request/
│   │   ├── Builder.php          # API request builder (rates + labels)
│   │   ├── CurlRequest.php      # HTTP client wrapper
│   │   └── GetOAuthToken.php    # OAuth token management (UPS + Endicia)
│   ├── Response/
│   │   ├── Parser.php
│   │   └── Validator.php
│   └── Services/
│       └── Tracking.php         # Creates shipment, tracking, label attachment
├── etc/
│   ├── acl.xml
│   ├── adminhtml/
│   │   ├── menu.xml
│   │   ├── routes.xml
│   │   └── system.xml           # Admin configuration fields
│   ├── config.xml               # Default configuration values
│   ├── di.xml                   # CLI command registration
│   └── module.xml
├── composer.json
├── view/adminhtml/
│   ├── layout/
│   │   ├── adminhtml.xml
│   │   └── adminhtml_order_shipment_new.xml  # Overrides shipment create form
│   ├── templates/create/
│   │   └── form.phtml           # Shipment UI with rate popups & AJAX
│   └── web/css/custom.css
└── registration.php
```

---

## OAuth Token Caching

Access tokens are cached in Magento flags to avoid requesting a new token on every API call:

| Carrier | Flag code | Timeout |
|---------|-----------|---------|
| UPS | `ups_oauth_token` | ~4 hours (14399 sec) |
| Endicia | `endicia_oauth_token` | ~15 minutes (900 sec) |

Endicia uses the **refresh token** grant type. UPS uses **client credentials**.

---

## Debugging & Logs

1. Go to **Admin → Ak Stack Pro → Shipping Label Integration → Settings**
2. Set **Debug Logging** to **Enabled**
3. Reproduce the action (config page load, rate fetch, label generation)
4. Inspect `var/log/system.log`

Logged information includes OAuth token responses, request bodies, response status codes, and API response bodies.

---

## Troubleshooting

| Issue | Likely cause | Solution |
|-------|--------------|----------|
| Config page breaks on load | Endicia credentials missing / API unreachable | Save credentials first; module now handles empty API responses gracefully |
| Endicia Mail Classes dropdown empty | Credentials not saved or invalid | Configure Client ID, Secret, Refresh Token, and API endpoint, then reload config |
| Shipment page shows no rates | Module disabled, missing credentials, or order has no shipping address | Enable module; verify API config; ensure order has a valid shipping address |
| Label generation does nothing | Order not invoiced or already fully shipped | Invoice the order first; check `system.log` with Debug enabled |
| UPS rates not appearing | Invalid UPS credentials or sandbox/production URL mismatch | Verify Client ID, Secret, Account Number, and API endpoint |
| Endicia rates filtered out | Mail classes configured but don't match API response | Adjust **Endicia Mail Classes** selection in config, or clear selection to show all rates |

---

## Important Notes

- **Invoicing is required** — The module only creates shipments for invoiced orders that can still be shipped.
- **Ship-from address** — Pulled from Magento Store Information, not from the configuration screen.
- **Ship-to address** — Pulled from the order's shipping address at the time of rate/label request.
- **Admin URL in template** — The shipment form template (`form.phtml`) contains hardcoded AJAX URLs. If your custom Admin URL path differs from the default, update the `url` values in `view/adminhtml/templates/create/form.phtml` to use Magento's URL builder instead of a fixed path.
- **Sandbox vs production** — Default config values point to UPS CIE (sandbox) and Endicia testing endpoints. Update all URLs and credentials for production use.

---

## Uninstallation

```bash
php bin/magento module:disable AkStackPro_ShippingLabelIntegration
php bin/magento setup:upgrade
php bin/magento cache:flush
```

To remove via Composer:

```bash
composer remove akstackpro/module-shipping-label-integration
```

> Only uninstall **AkStackPro Core** after all Ak Stack Pro child extensions have been removed.

---

## License

Proprietary — Copyright © 2026 [AkStackPro](https://akstackpro.com). All rights reserved.

---

## Support

For issues, enable **Debug Logging**, reproduce the problem, and review `var/log/system.log` for API request/response details before contacting support.

**Ak Stack Pro** — [https://akstackpro.com](https://akstackpro.com)
