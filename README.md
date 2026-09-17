# Nextcloud Secure Office

[![Nextcloud](https://img.shields.io/badge/Nextcloud-33%2B-0082c9.svg)](https://nextcloud.com/)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](LICENSE)
[![Compliance](https://img.shields.io/badge/Compliance-ENS%20(RD%20311%2F2022)%20Alto-green.svg)](https://www.ccn-cert.cni.es/ens.html)

**Nextcloud Secure Office** is an enterprise-grade security extension for Nextcloud 33+ designed to enforce data confidentiality, document traceability, at-rest cryptographic protection, and Data Loss Prevention (DLP) across collaborative office sessions powered by Nextcloud Office (Collabora Online / CODE).

The application is architected to address the rigorous compliance demands of public administrations, critical infrastructure, and highly regulated entities operating under security frameworks such as the Spanish **Esquema Nacional de Seguridad (ENS - Real Decreto 311/2022)**.

---

## ENS Compliance Mapping (RD 311/2022)

| ENS Measure | Security Dimension | Secure Office Implementation |
| :--- | :--- | :--- |
| **`[mp.info.2]`** | **Activity Logging & Traceability** | Automated, immutable audit trail recording every document access in `oc_secure_office_audit` with user ID, client IP, document metadata, classification, and DLP state. Includes CSV export for security audits. |
| **`[mp.info.3]`** | **Storage Encryption (At Rest)** | Full compatibility with Nextcloud Server-Side Encryption (SSE) using Master Key mode (AES-256-CTR). Protects raw document files on disk while maintaining seamless, secure collaborative editing in Collabora. |
| **`[mp.info.4]`** | **Channel Protection (In Transit)** | Enforces and audits secure TLS/HTTPS transit across browser sessions and Collabora WOPI endpoints. |
| **`[mp.info.6]`** | **Information Leak Prevention (DLP)** | Dynamic, non-removable forensic watermarking (`WatermarkText`) displaying user identity, client IP, and timestamps. Enforces `DisableExport`, `DisableCopy`, and `DisablePrint` controls. |

---

## Key Features & Security Capabilities

- **Dynamic Forensic Watermarking**:
  - Automatically embeds dynamic, non-removable background watermarks in real-time during Collabora Online editing and viewing sessions.
  - Configurable watermark payloads include authenticated user identifier (`{userId}`), display name (`{userDisplayName}`), client IP address (`{userIp}`), access timestamp, and document confidentiality classification labels (e.g., `CONFIDENCIAL (ENS RD 311/2022)`).
  - Acts as a forensic deterrent against data leakage through screen captures, photographs, or unauthorized redistribution.

- **Data Loss Prevention (DLP) Controls**:
  - **Export Restriction**: Prohibits users from exporting or downloading unencrypted document copies (`DisableExport`) while preserving in-browser collaborative editing.
  - **Clipboard Isolation**: Enforces copy/paste restrictions (`DisableCopy`) to prevent exfiltration of sensitive document text outside the secure perimeter.
  - **Print Prohibition**: Blocks physical and virtual printing actions (`DisablePrint`) within the office suite interface.

- **Administrative Security Control Panel**:
  - Integrated into Nextcloud Settings under **Settings > Administration > Security**.
  - Displays a real-time ENS compliance dashboard evaluating all active security controls.
  - Allows security officers to adjust classification labels, watermark tokens, and DLP switches with immediate effect.
  - Provides a searchable audit log with direct **CSV export** capabilities.

- **CLI Diagnostic Auditor (`occ`)**:
  - Run instant compliance evaluations from the terminal:
    ```bash
    php occ secure_office:ens-audit
    ```

---

## Architecture & Directory Structure

```text
custom_apps/secure_office/
├── appinfo/
│   ├── info.xml            # App metadata, settings, commands, and dependencies
│   └── routes.php          # REST and UI routing definitions
├── lib/
│   ├── AppInfo/
│   │   └── Application.php # Application bootstrap and service registration
│   ├── Command/
│   │   └── EnsAuditCommand.php # CLI auditor (occ secure_office:ens-audit)
│   ├── Controller/
│   │   ├── PageController.php         # Public status and overview endpoints
│   │   └── SettingsApiController.php  # Admin REST API for policies and CSV export
│   ├── Listener/
│   │   └── DocumentOpenedListener.php # Nextcloud Office audit event listener
│   ├── Middleware/
│   │   └── WopiSecurityMiddleware.php # WOPI CheckFileInfo security interceptor
│   ├── Migration/
│   │   └── Version020000Date...php    # Database schema for oc_secure_office_audit
│   ├── Service/
│   │   ├── AuditService.php           # Audit persistence and CSV export service
│   │   ├── EnsDiagnosticService.php   # Real-time ENS compliance engine
│   │   └── SecurityConfigService.php  # Policy and configuration manager
│   └── Settings/
│       └── AdminSettings.php          # Nextcloud admin settings integration
├── templates/
│   ├── admin.php           # Security administration dashboard template
│   └── main.php            # Standalone view template
├── LICENSE                 # GNU AGPL-3.0 with attribution notice
└── README.md               # Documentation
```

---

## Requirements

- **Nextcloud**: `>= 33.0.0`
- **PHP**: `>= 8.2`
- **Nextcloud Office (`richdocuments`)**: Enabled
- **Collabora Online (CODE)**: Running and configured as the document processing engine
- **Server-Side Encryption (`encryption`)**: Enabled with Master Key for `[mp.info.3]` compliance

---

## Installation & Setup

1. Place this repository into your Nextcloud `custom_apps` directory:
   ```bash
   cd /var/www/html/custom_apps/
   git clone https://github.com/nmarafo/nextcloud-secure-office.git secure_office
   ```

2. Enable the application:
   ```bash
   php occ app:enable secure_office
   ```

3. Enable At-Rest Encryption with Master Key (ENS `[mp.info.3]`):
   ```bash
   php occ app:enable encryption
   php occ encryption:enable-master-key
   php occ encryption:enable
   php occ encryption:encrypt-all
   ```

4. Run the ENS compliance audit:
   ```bash
   php occ secure_office:ens-audit
   ```

---

## Author & Attribution

- **Author**: Norberto Martín Afonso ([normaafo@gmail.com](mailto:normaafo@gmail.com))
- **Repository**: [https://github.com/nmarafo/nextcloud-secure-office](https://github.com/nmarafo/nextcloud-secure-office)

---

## License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0-or-later)** with author attribution preserved under Section 7(b).

See the [LICENSE](LICENSE) file for complete licensing terms and attribution requirements.