<p align="center">
  <img src="screenshots/00_secure_office_logo.png" alt="Nextcloud Secure Office Logo" width="180"/>
</p>

# Nextcloud Secure Office

[![Nextcloud App Store](https://img.shields.io/badge/App_Store-secure__office-0082c9.svg)](https://apps.nextcloud.com/apps/secure_office)
[![Version](https://img.shields.io/badge/version-0.4.0-blue.svg)](https://github.com/nmarafo/nextcloud-secure-office/releases)
[![Nextcloud](https://img.shields.io/badge/Nextcloud-33%2B-0082c9.svg)](https://nextcloud.com/)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](LICENSE)
[![Compliance](https://img.shields.io/badge/Compliance-ENS%20(RD%20311%2F2022)%20Alto-green.svg)](https://www.ccn-cert.cni.es/ens.html)
[![CCN-STIC Guide](https://img.shields.io/badge/CCN--STIC-826%20Nextcloud-0082c9.svg)](https://www.ccn-cert.cni.es/es/800-guia-esquema-nacional-de-seguridad/4229-ccn-stic-826-implementacion-de-seguridad-nextcloud/file.html)

**Nextcloud Secure Office** is an enterprise-grade security extension for Nextcloud 33+ designed to enforce data confidentiality, document traceability, cryptographic protection at rest, granular role-based Data Loss Prevention (DLP), and delegated security governance across collaborative office documents (Collabora Online / CODE) and native Nextcloud files (PDFs, images, archives).

The application is specifically architected to address the rigorous compliance demands of public administrations, educational institutions, critical infrastructure, and regulated entities operating under security frameworks such as Spain's **Esquema Nacional de Seguridad (ENS - Real Decreto 311/2022)** and the technical baseline established in **[CCN-STIC-826: Implementación de Seguridad Nextcloud](https://www.ccn-cert.cni.es/es/800-guia-esquema-nacional-de-seguridad/4229-ccn-stic-826-implementacion-de-seguridad-nextcloud/file.html)**.

---

## Visual Overview & Screenshots

<table align="center">
  <tr>
    <td align="center" width="50%">
      <strong>Dynamic Forensic Watermarking</strong><br>
      <img src="screenshots/01_collabora_forensic_watermark.png" alt="Forensic Watermark in Collabora Online" width="100%"/>
      <br><em>Indelible real-time watermark in Collabora with user, IP, date, and ENS classification.</em>
    </td>
    <td align="center" width="50%">
      <strong>ENS Real-Time Compliance Dashboard</strong><br>
      <img src="screenshots/02_admin_ens_cards.png" alt="ENS Compliance Diagnostic Cards" width="100%"/>
      <br><em>Continuous compliance assessment evaluating measures [mp.info.2], [mp.info.3], [mp.info.4], and [mp.info.6].</em>
    </td>
  </tr>
  <tr>
    <td align="center" width="50%">
      <strong>Role-Based DLP & Delegated Governance</strong><br>
      <img src="screenshots/03_admin_dlp_settings.png" alt="Role-Based DLP & Delegated Administration Settings" width="100%"/>
      <br><em>Granular group selectors for export, print, copy, and native downloads, plus Delegated Admin for Direction.</em>
    </td>
    <td align="center" width="50%">
      <strong>Activity Audit Trail & Policy Traceability</strong><br>
      <img src="screenshots/04_admin_audit_table.png" alt="Immutable Activity Audit Trail" width="100%"/>
      <br><em>Immutable log registering document access, downloads, blocked exfiltration, and POLICY_CHANGE events.</em>
    </td>
  </tr>
  <tr>
    <td align="center" width="50%">
      <strong>Delegated Governance Panel (Dirección del Centro)</strong><br>
      <img src="screenshots/06_delegated_director_dashboard.png" alt="Delegated Administration View for Institutional Direction" width="100%"/>
      <br><em>Dedicated panel allowing the Responsable de la Información (Director) to govern DLP policies directly.</em>
    </td>
    <td align="center" width="50%">
      <strong>User Transparency Status View</strong><br>
      <img src="screenshots/07_user_transparency_view.png" alt="User Transparency Status View" width="100%"/>
      <br><em>Transparent overview for everyday users (teachers, staff, students) displaying active session DLP rules.</em>
    </td>
  </tr>
  <tr>
    <td align="center" colspan="2">
      <strong>Terminal Compliance Auditor (CLI)</strong><br>
      <img src="screenshots/05_terminal_ens_audit.png" alt="Terminal Compliance Auditor CLI" width="65%"/>
      <br><em>Audit compliance status directly from terminal with <code>php occ secure_office:ens-audit</code>.</em>
    </td>
  </tr>
</table>

---

## ENS & CCN-STIC-826 Compliance Mapping (RD 311/2022)

| ENS Measure / STIC Ref | Security Dimension | Gaps Resolved by Secure Office Implementation |
| :--- | :--- | :--- |
| **`[org.1]`**<br>**`[org.2]`**<br>**`[mp.ac.3]`** | **Segregation of Duties & Delegated Governance** | Implements the role of **Responsable de la Información** (e.g. School Director, Department Head). Allows institutional managers to govern download, export, and print permissions per user group without requiring global IT superadmin privileges. |
| **`[mp.info.2]`**<br>CCN-STIC-826 | **Activity Logging & Traceability** | Automated, immutable audit trail recording every document access, native file download (PDF, images, ZIPs, binaries), and security policy change (`POLICY_CHANGE`) in `oc_secure_office_audit` with user ID, client IP, document metadata, classification, and DLP state. Includes CSV export for security audits. |
| **`[mp.info.3]`**<br>CCN-STIC-826 | **Storage Encryption (At Rest)** | Full compatibility with Nextcloud Server-Side Encryption (SSE) using Master Key mode (AES-256-CTR). Protects raw document and native files on disk while maintaining seamless, secure collaborative editing in Collabora. |
| **`[mp.info.4]`**<br>CCN-STIC-826 | **Channel Protection (In Transit)** | Enforces and audits secure TLS/HTTPS transit across browser sessions, WebDAV transfers, and Collabora WOPI endpoints. |
| **`[mp.info.6]`**<br>CCN-STIC-826 | **Information Leak Prevention (DLP)** | **Collabora Module:** Dynamic, indelible forensic watermarking (`WatermarkText`) displaying user identity, client IP, and timestamps. Granular role-based controls for `DisableExport`, `DisableCopy`, and `DisablePrint` in Collabora Online.<br>**Native Files Module:** Role-based policy restricting direct file downloads to authorized groups (e.g. Direction, Teachers) while preventing download to unauthorized users (e.g. Students). |

---

## How It Works

Nextcloud Secure Office operates as a transparent security middleware and policy engine between Nextcloud core, the Nextcloud Office application (`richdocuments`), and the Collabora Online (CODE) rendering server:

```text
┌─────────────────────────────────────────────────────────────────────────┐
│                          USER BROWSER / CLIENT                          │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ 1. User opens collaborative doc
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                             NEXTCLOUD CORE                              │
│                                                                         │
│  • Storage at Rest: AES-256-CTR Encrypted File on Disk [mp.info.3]     │
│  • Master Key Decryption in RAM for Authenticated Stream                │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ 2. Collabora requests WOPI CheckFileInfo
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      SECURE OFFICE INTERCEPTOR                          │
│                                                                         │
│  • WopiSecurityMiddleware: Intercepts checkFileInfo manifest           │
│  • Resolves authenticated user & evaluates assigned groups / roles     │
│  • Injects Dynamic Forensic Watermark Text [mp.info.6]                 │
│  • Injects Granular DLP Flags: DisableExport, DisableCopy, DisablePrint│
│  • DocumentOpenedListener: Records event to oc_secure_office_audit     │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ 3. Delivers enriched security manifest
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      COLLABORA ONLINE ENGINE (CODE)                     │
│                                                                         │
│  • Renders Diagonal Forensic Watermark across all pages/slides/sheets   │
│  • Disables Download/Export buttons dynamically per role / group        │
│  • Disables Clipboard Copy/Paste exfiltration from document canvas      │
│  • Disables Physical & Virtual Printing capabilities per role / group   │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1. WOPI Protocol Interception (`WopiSecurityMiddleware`)
- Collabora Online connects to Nextcloud using the Microsoft-standard **WOPI** (Web Application Open Platform Interface) protocol.
- When a document is opened, Collabora calls the `checkFileInfo` endpoint (`GET /wopi/files/{fileId}`) to query file metadata, permissions, and editor capabilities.
- `WopiSecurityMiddleware` hooks into Nextcloud's controller lifecycle (`afterController`). It intercepts the response before it reaches Collabora, identifies the authenticated user and their groups, and dynamically injects:
  - `WatermarkText`: An indelible forensic stamp constructed from dynamic tokens (`{classification}`, `{userDisplayName}`, `{userId}`, `{userIp}`, `{date}`).
  - Granular DLP flags: `DisableExport`, `DisableCopy`, `DisablePrint`, `HideExportOption`, `HidePrintOption` based on whether the user's role is in the allowed group list.
- Collabora's rendering engine natively interprets these parameters, superimposing the forensic watermark directly into the rendered canvas and removing download/print/copy actions for restricted roles.

### 2. Role-Based Native File Access Control (`NativeFileAccessListener`)
- Monitors native file access and direct downloads via Nextcloud Files or WebDAV (PDFs, images, ZIP archives, binaries).
- Enforces strict mode DLP: users can only download files if they belong to an authorized group configured by the Responsable de la Información (e.g. `direccion`, `profesores`) or are system administrators.
- Unauthorized download attempts are blocked (HTTP 403 / `HintException`) and recorded in the audit database as `BLOCKED_DOWNLOAD`.

### 3. Delegated Governance for Institutional Direction (`[org.1]`, `[org.2]`)
- Under the ENS, the **Responsable de la Información** (in educational centers, the Direction or Leadership Team) has the exclusive authority to determine access and dissemination rights over institutional information.
- Members of configured managerial groups (e.g. `direccion`) can access the Secure Office control panel directly from the top navigation bar, configure allowed groups for each DLP directive, and export compliance reports without requiring global server superadmin privileges.
- Every policy modification is logged in the audit trail as `POLICY_CHANGE`.

### 4. At-Rest Cryptographic Protection (`[mp.info.3]`)
- By leveraging Nextcloud's Server-Side Encryption (SSE) in **Master Key** mode (`OC_DEFAULT_MODULE`), document files stored on server storage (`/var/www/html/data/`) are stored as **AES-256-CTR** ciphertext (`HBEGIN:oc_encryption_module:OC_DEFAULT_MODULE...`).
- When an authorized user collaborates on a file, Nextcloud decodes the stream in memory using the system Master Key, delivers the decrypted stream to Collabora over a secure internal channel, and immediately re-encrypts changes before saving back to physical storage.

### 5. Immutable Database Audit Trail (`[mp.info.2]`)
- Every collaborative session, native file download, blocked exfiltration attempt, and policy change creates an immutable audit record in the `oc_secure_office_audit` database table.
- Each record registers: timestamp (UTC), user ID, origin IP address, document ID, file name, file path, classification level, action type (`COLLABORA_VIEW`, `NATIVE_DOWNLOAD`, `BLOCKED_DOWNLOAD`, `POLICY_CHANGE`), and applied DLP controls.
- Authorized managers and administrators can review access events in the web UI and download the complete audit trail as a standard CSV file for formal audits.

---

## Key Features & Security Capabilities

- **Granular Role-Based Data Loss Prevention (DLP)**:
  - **Export & Download Restriction**: Prohibit unencrypted document downloads in the viewer while permitting in-browser viewing or editing for specified roles.
  - **Printing Restriction**: Block physical and virtual printing actions within the office suite interface per user role.
  - **Clipboard Isolation**: Prevent copying sensitive text out of documents for non-authorized groups.
  - **Native File Download Control**: Restrict direct file downloads in Nextcloud Files to authorized profiles.

- **Dynamic Forensic Watermarking**:
  - Automatically embeds dynamic, non-removable background watermarks in real-time during Collabora Online editing and viewing sessions.
  - Configurable tokens: `{classification}`, `{userDisplayName}`, `{userId}`, `{userIp}`, `{date}`.
  - Acts as a forensic deterrent against data leakage through screen captures, photographs, or unauthorized redistribution.

- **Delegated Administration (ENS Responsable de la Información)**:
  - Empowers institutional leadership (School Directors, Department Heads) to manage security policies for their organization.
  - Independent access through the Nextcloud app navigation menu without global IT superadmin privileges.
  - Full traceability: policy modifications are logged with user identity and timestamp.

- **User Transparency View**:
  - Regular users (teachers, staff, students) can access the app view to see clearly and transparently what security classification and DLP policies apply to their profile.

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
│   ├── routes.php          # REST and UI routing definitions
│   └── signature.json      # Code integrity signature
├── lib/
│   ├── AppInfo/
│   │   └── Application.php # Application bootstrap and service registration
│   ├── Command/
│   │   └── EnsAuditCommand.php # CLI auditor (occ secure_office:ens-audit)
│   ├── Controller/
│   │   ├── PageController.php         # App navigation, delegated view, and user status
│   │   └── SettingsApiController.php  # Admin & delegated REST API for policies and CSV export
│   ├── Listener/
│   │   ├── DocumentOpenedListener.php # Nextcloud Office audit event listener
│   │   └── NativeFileAccessListener.php # Native file access and download DLP listener
│   ├── Middleware/
│   │   └── WopiSecurityMiddleware.php # WOPI CheckFileInfo granular security interceptor
│   ├── Migration/
│   │   ├── Version020000Date...php    # Database schema for oc_secure_office_audit
│   │   └── Version030000Date...php    # Audit enhancements
│   ├── Service/
│   │   ├── AuditService.php           # Audit persistence and CSV export service
│   │   ├── EnsDiagnosticService.php   # Real-time ENS compliance engine
│   │   └── SecurityConfigService.php  # Role-based policy and configuration manager
│   └── Settings/
│       └── AdminSettings.php          # Nextcloud admin settings integration
├── templates/
│   ├── admin.php           # Security administration dashboard template with group selectors
│   └── main.php            # User transparency status view template
├── screenshots/            # Visual documentation and store screenshot assets
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

## Production Deployment Considerations (ENS [mp.info.4] Compliance)

To achieve full compliance in production environments, particularly for communication channel protection (ENS Measure **`[mp.info.4]`** - Tránsito Seguro):

1. **Mandatory HTTPS & TLS Hardening:**
   - Deploy Nextcloud and Collabora Online behind a hardened reverse proxy (e.g. Nginx, Traefik, Apache, or Caddy) equipped with trusted TLS/SSL certificates.
   - Enforce TLS 1.2 or TLS 1.3 with forward secrecy cipher suites.

2. **Nextcloud Protocol Enforcement (`config/config.php`):**
   Ensure Nextcloud enforces HTTPS across all generated URLs, webhooks, and CLI operations:
   ```php
   'overwriteprotocol' => 'https',
   'overwrite.cli.url' => 'https://nextcloud.yourdomain.com',
   ```

3. **HTTP Strict Transport Security (HSTS):**
   Configure your reverse proxy to send the HSTS header:
   ```nginx
   add_header Strict-Transport-Security "max-age=15552000; includeSubDomains; preload" always;
   ```

4. **Secure Collabora WOPI Communication:**
   Configure Nextcloud Office (`richdocuments`) to communicate with Collabora exclusively over HTTPS:
   ```bash
   php occ config:app:set richdocuments wopi_url --value="https://collabora.yourdomain.com"
   ```

---

## Disclaimer / Descargo de Responsabilidad

> [!IMPORTANT]
> **Legal, Regulatory & Security Disclaimer:**
>
> 1. **"AS IS" Software**: This software is provided by the author and contributors "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose, and non-infringement. In no event shall the author or copyright holders be liable for any claim, damages, data loss, security incident, or other liability arising from the use of or inability to use this software.
>
> 2. **ENS & CCN-STIC Accreditation & Certification**: While **Nextcloud Secure Office** implements technical security controls, DLP restrictions, cryptographic compatibility, and audit logging designed to support and facilitate compliance with Spain's **Esquema Nacional de Seguridad (ENS - Real Decreto 311/2022)** and the **CCN-STIC-826** guideline, the mere installation or activation of this plugin does **not** constitute an official accreditation, CPSTIC qualification, or guarantee formal certification.
>
> 3. **Administrative & Organizational Responsibility**: ENS compliance encompasses broad organizational, physical, technical, and operational dimensions. The deploying organization and its designated Security Officer (CISO / Responsable de Seguridad) are solely responsible for conducting comprehensive risk assessments, server hardening, cryptographic lifecycle management, and formal security governance.
>
> **Descargo de Responsabilidad (Español):**  
> El presente software se distribuye "tal cual", sin garantías de ningún tipo. La utilización de este complemento facilita la implementación técnica de medidas de seguridad recogidas en el **Esquema Nacional de Seguridad (Real Decreto 311/2022)** y la guía **CCN-STIC-826**, pero **no sustituye ni otorga por sí misma la certificación formal de conformidad con el ENS ni la cualificación CPSTIC**. La responsabilidad sobre la adecuación, bastionado del servidor, gestión de claves criptográficas, aseguramiento de canales TLS, políticas de seguridad y auditorías preceptivas recae exclusivamente en la entidad u organismo implantador.

---

## Author & Attribution

- **Author**: Norberto Martín Afonso ([normaafo@gmail.com](mailto:normaafo@gmail.com))
- **Repository**: [https://github.com/nmarafo/nextcloud-secure-office](https://github.com/nmarafo/nextcloud-secure-office)

---

## License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0-or-later)** with author attribution preserved under Section 7(b).

See the [LICENSE](LICENSE) file for complete licensing terms and attribution requirements.