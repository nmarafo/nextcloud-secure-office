# Nextcloud Secure Office

[![Nextcloud](https://img.shields.io/badge/Nextcloud-33%2B-0082c9.svg)](https://nextcloud.com/)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](LICENSE)
[![Compliance](https://img.shields.io/badge/Compliance-ENS%20(RD%20311%2F2022)-green.svg)](https://www.ccn-cert.cni.es/ens.html)

**Nextcloud Secure Office** is an enterprise-grade security extension for Nextcloud 33+ designed to enforce data confidentiality, document traceability, and Data Loss Prevention (DLP) across collaborative office sessions powered by Nextcloud Office (Collabora Online / CODE).

The application is architected to address the rigorous compliance demands of public administrations, critical infrastructure, and highly regulated entities operating under security frameworks such as the Spanish **Esquema Nacional de Seguridad (ENS - Real Decreto 311/2022)**.

---

## Key Features & Security Capabilities

- **Dynamic Forensic Watermarking**:
  - Automatically embeds dynamic, non-removable background watermarks in real-time during Collabora Online editing and viewing sessions.
  - Configurable watermark payloads include authenticated user identifier (`{userId}`), client IP address (`{userIp}`), access timestamp, and document confidentiality classification labels (e.g., `CONFIDENTIAL - ENS`).
  - Deterrent against data leakage through screen captures, photos, or unauthorized redistribution.

- **Data Loss Prevention (DLP) Controls**:
  - **Export Restriction**: Prohibits users from exporting or downloading unencrypted document copies (`DisableExport`) while preserving in-browser collaborative editing.
  - **Clipboard Isolation**: Enforces copy/paste restrictions (`DisableCopy`) to prevent exfiltration of sensitive document text outside the secure perimeter.
  - **Print Prohibition**: Blocks physical and virtual printing actions (`DisablePrint`) within the office suite interface.

- **WOPI Protocol Security Interception**:
  - Leverages Nextcloud Office's WOPI event pipeline (`OCA\Richdocuments\Events\BeforeCheckFileInfoEvent`) to inject policy constraints directly into the Collabora `CheckFileInfo` manifest.
  - Granular policy enforcement based on document metadata, classification tags, or user permissions.

- **Traceability & Audit Logging**:
  - Transparent logging of document access, session grants, and security policy triggers to ensure end-to-end accountability and non-repudiation.

---

## Architecture & Directory Structure

```text
custom_apps/secure_office/
├── appinfo/
│   ├── info.xml            # App metadata, dependencies, and navigation
│   └── routes.php          # REST and UI routing definitions
├── lib/
│   ├── AppInfo/
│   │   └── Application.php # Application bootstrap and service registration
│   ├── Controller/         # Controller endpoints for administration and UI
│   ├── Listener/           # WOPI CheckFileInfo and Nextcloud event listeners
│   └── Service/            # Security policy, watermarking, and audit services
├── templates/              # Administration and user interface templates
├── img/                    # App icons and graphic assets
├── LICENSE                 # GNU AGPL-3.0 with attribution
└── README.md               # Documentation
```

---

## Requirements

- **Nextcloud**: `>= 33.0.0`
- **PHP**: `>= 8.2`
- **Nextcloud Office (`richdocuments`)**: Enabled
- **Collabora Online (CODE)**: Running and configured as the document processing engine

---

## Installation & Activation

1. Clone or place this repository into your Nextcloud `custom_apps` directory:
   ```bash
   cd /var/www/html/custom_apps/
   git clone https://github.com/nmarafo/nextcloud-secure-office.git secure_office
   ```

2. Enable the application using the `occ` command-line tool:
   ```bash
   php occ app:enable secure_office
   ```

3. Verify the app status:
   ```bash
   php occ app:list | grep secure_office
   ```

---

## Author & Attribution

- **Author**: Norberto Martín Afonso ([normaafo@gmail.com](mailto:normaafo@gmail.com))
- **Repository**: [https://github.com/nmarafo/nextcloud-secure-office](https://github.com/nmarafo/nextcloud-secure-office)

---

## License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0-or-later)** with author attribution preserved under Section 7(b).

See the [LICENSE](LICENSE) file for complete licensing terms and attribution requirements.
