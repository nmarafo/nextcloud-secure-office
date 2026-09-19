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
import xml.etree.ElementTree as ET
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.primitives.serialization import load_pem_private_key

def main():
    print("=== Nextcloud Secure Office - Release Publisher ===")
    
    # 1. Resolve version from info.xml
    info_tree = ET.parse("appinfo/info.xml")
    version = info_tree.find("version").text.strip()
    tag = f"v{version}"
    repo = os.environ.get("GITHUB_REPOSITORY", "nmarafo/nextcloud-secure-office")
    print(f"Target app version: {version} (tag: {tag}) in repo: {repo}")

    # 2. Resolve private key
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

    # 3. Resolve certificate
    cert_text = None
    for candidate in ["certificates/secure_office.crt", "../../certificates/secure_office.crt", "../certificates/secure_office.crt"]:
        if os.path.exists(candidate):
            with open(candidate, "r") as f:
                cert_text = f.read().strip()
            break

    if not cert_text and os.path.exists("appinfo/signature.json"):
        with open("appinfo/signature.json", "r") as f:
            cert_text = json.load(f).get("certificate")

    # 4. Hash files and create code integrity signature.json
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

    # Nextcloud's Checker.php uses phpseclib RSA with setMGFHash('sha512') and setSaltLength(0),
    # but does not set setHash(), leaving phpseclib's default message hash as SHA-1.
    code_sig = pkey.sign(
        php_json.encode("utf-8"),
        padding.PSS(mgf=padding.MGF1(hashes.SHA512()), salt_length=0),
        hashes.SHA1()
    )

    sig_data = {
        "hashes": sorted_hashes,
        "signature": base64.b64encode(code_sig).decode("ascii"),
        "certificate": cert_text
    }

    os.makedirs("appinfo", exist_ok=True)
    with open("appinfo/signature.json", "w") as f:
        json.dump(sig_data, f, indent=4)
    print(f"appinfo/signature.json updated with {len(sorted_hashes)} files.")

    # 5. Create release tarball
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

    # 6. Sign tar.gz (RSA PKCS#1 v1.5 with SHA512)
    with open(tar_path, "rb") as f:
        tar_bytes = f.read()

    tar_sig = pkey.sign(tar_bytes, padding.PKCS1v15(), hashes.SHA512())
    tar_sig_b64 = base64.b64encode(tar_sig).decode("ascii").strip()
    print("Archive signature generated successfully.")

    # 7. Upload to GitHub Release if GITHUB_TOKEN is set
    gh_token = os.environ.get("GITHUB_TOKEN")
    download_url = f"https://github.com/{repo}/releases/download/{tag}/secure_office.tar.gz"

    if gh_token:
        print(f"Managing GitHub release for {tag}...")
        gh_headers = {
            "Authorization": f"Bearer {gh_token}",
            "Accept": "application/vnd.github+json",
            "X-GitHub-Api-Version": "2022-11-28"
        }
        rel_res = requests.get(f"https://api.github.com/repos/{repo}/releases/tags/{tag}", headers=gh_headers)
        if rel_res.status_code == 200:
            rel_data = rel_res.json()
        else:
            create_payload = {
                "tag_name": tag,
                "name": f"{tag} - Secure Office",
                "body": f"Release of Nextcloud Secure Office version {version}.",
                "draft": False,
                "prerelease": False
            }
            create_r = requests.post(f"https://api.github.com/repos/{repo}/releases", headers=gh_headers, json=create_payload)
            rel_data = create_r.json()

        # Delete previous asset if exists, then upload new asset
        upload_url = rel_data.get("upload_url", "").replace("{?name,label}", "?name=secure_office.tar.gz")
        for asset in rel_data.get("assets", []):
            if asset.get("name") == "secure_office.tar.gz":
                requests.delete(f"https://api.github.com/repos/{repo}/releases/assets/{asset['id']}", headers=gh_headers)
                break

        if upload_url:
            with open(tar_path, "rb") as tf:
                up_headers = {
                    "Authorization": f"Bearer {gh_token}",
                    "Content-Type": "application/gzip"
                }
                up_res = requests.post(upload_url, headers=up_headers, data=tf.read())
                print(f"GitHub Release asset upload status: HTTP {up_res.status_code}")

    # 8. Publish to Nextcloud App Store
    token = os.environ.get("APPSTORE_TOKEN")
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
                print("Release is already up-to-date in Nextcloud App Store.")
            else:
                sys.exit(1)
    else:
        print("Notice: APPSTORE_TOKEN not set, skipping App Store publication.")

if __name__ == "__main__":
    main()
