#!/usr/bin/env python3
"""
Nextcloud Secure Office - Build, Sign, and Publish to Nextcloud App Store
"""

import os
import sys
import json
import base64
import hashlib
import tarfile
import requests
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.primitives.serialization import load_pem_private_key

def main():
    print("=== Nextcloud Secure Office - Release Publisher ===")
    
    # 1. Resolve private key
    private_key_pem = os.environ.get("APP_PRIVATE_KEY")
    if not private_key_pem:
        for candidate in ["certificates/secure_office.key", "../../certificates/secure_office.key", "../certificates/secure_office.key"]:
            if os.path.exists(candidate):
                with open(candidate, "r") as f:
                    private_key_pem = f.read()
                break

    if not private_key_pem:
        print("ERROR: APP_PRIVATE_KEY not provided.")
        sys.exit(1)

    pkey = load_pem_private_key(private_key_pem.encode("utf-8"), password=None)

    # 2. Resolve certificate
    cert_text = None
    for candidate in ["certificates/secure_office.crt", "../../certificates/secure_office.crt", "../certificates/secure_office.crt"]:
        if os.path.exists(candidate):
            with open(candidate, "r") as f:
                cert_text = f.read().strip()
            break

    if not cert_text and os.path.exists("appinfo/signature.json"):
        with open("appinfo/signature.json", "r") as f:
            cert_text = json.load(f).get("certificate")

    # 3. Hash files and create code integrity signature.json
    file_hashes = {}
    for root, dirs, files in os.walk("."):
        if any(x in root for x in [".git", ".github", "screenshots", "scripts", "certificates", "video_assets"]):
            continue
        for f in files:
            if f.startswith("."):
                continue
            rel = os.path.relpath(os.path.join(root, f), ".").replace("\\", "/")
            if rel in ["appinfo/signature.json", "secure_office.tar.gz"]:
                continue
            with open(os.path.join(root, f), "rb") as fp:
                file_hashes[rel] = hashlib.sha512(fp.read()).hexdigest()

    sorted_hashes = dict(sorted(file_hashes.items()))
    php_json = json.dumps(sorted_hashes, separators=(",", ":")).replace("/", r"\/")

    code_sig = pkey.sign(
        php_json.encode("utf-8"),
        padding.PSS(mgf=padding.MGF1(hashes.SHA512()), salt_length=0),
        hashes.SHA512()
    )

    sig_data = {
        "hashes": sorted_hashes,
        "signature": base64.b64encode(code_sig).decode("ascii"),
        "certificate": cert_text
    }

    os.makedirs("appinfo", exist_ok=True)
    with open("appinfo/signature.json", "w") as f:
        json.dump(sig_data, f, indent=4)
    print(f"appinfo/signature.json generated with {len(sorted_hashes)} files.")

    # 4. Create release tarball
    tar_path = "secure_office.tar.gz"
    if os.path.exists(tar_path):
        os.remove(tar_path)

    with tarfile.open(tar_path, "w:gz") as tar:
        for root, dirs, files in os.walk("."):
            if any(x in root for x in [".git", ".github", "screenshots", "scripts", "certificates", "video_assets"]):
                continue
            for f in files:
                if f.startswith("."):
                    continue
                full_p = os.path.join(root, f)
                rel_p = os.path.relpath(full_p, ".").replace("\\", "/")
                if rel_p == "secure_office.tar.gz":
                    continue
                arc_p = "secure_office/" + rel_p
                tar.add(full_p, arcname=arc_p)

    print(f"Release archive built: {tar_path} ({os.path.getsize(tar_path)} bytes)")

    # 5. Sign tar.gz
    with open(tar_path, "rb") as f:
        tar_bytes = f.read()

    tar_sig = pkey.sign(tar_bytes, padding.PKCS1v15(), hashes.SHA512())
    tar_sig_b64 = base64.b64encode(tar_sig).decode("ascii").strip()
    print("Archive signature generated successfully.")

    # 6. Publish to Nextcloud App Store
    token = os.environ.get("APPSTORE_TOKEN")
    tag = os.environ.get("GITHUB_REF_NAME", "v0.2.3")
    repo = os.environ.get("GITHUB_REPOSITORY", "nmarafo/nextcloud-secure-office")
    download_url = f"https://github.com/{repo}/releases/download/{tag}/secure_office.tar.gz"

    if token:
        print(f"Publishing release to Nextcloud App Store (Download: {download_url})...")
        headers = {
            "Authorization": f"Token {token}",
            "Content-Type": "application/json"
        }
        payload = {
            "download": download_url,
            "signature": tar_sig_b64,
            "nightly": False
        }
        res = requests.post("https://apps.nextcloud.com/api/v1/apps/releases", headers=headers, json=payload)
        print(f"App Store Response: HTTP {res.status_code}")
        if res.status_code in [200, 201]:
            print("SUCCESS: Release published on Nextcloud App Store!")
        else:
            print(f"Response body: {res.text}")
            if "already exists" in res.text.lower():
                print("Notice: Release already registered in App Store.")
            else:
                sys.exit(1)
    else:
        print("Notice: APPSTORE_TOKEN not set, skipping App Store publication.")

if __name__ == "__main__":
    main()
