#!/usr/bin/env bash

set -Eeuo pipefail

export PATH='/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'

readonly CHROME_PACKAGE_URL='https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb'
readonly CHROME_BINARY='/usr/bin/google-chrome'

fail()
{
    echo "Error: $*" >&2
    exit 1
}

if [[ "$(uname -s)" != 'Linux' ]]; then
    fail 'This installer only supports Ubuntu or Debian Linux.'
fi

if [[ ! -r /etc/os-release ]]; then
    fail 'Cannot determine the Linux distribution (/etc/os-release is missing).'
fi

# shellcheck disable=SC1091
source /etc/os-release

if [[ "${ID:-}" != 'ubuntu' && "${ID:-}" != 'debian' ]]; then
    fail "This installer only supports Ubuntu or Debian (detected: ${ID:-unknown})."
fi

if ! command -v dpkg >/dev/null 2>&1; then
    fail 'The dpkg command is required.'
fi

if [[ "$(dpkg --print-architecture)" != 'amd64' ]]; then
    fail 'Google Chrome for Linux requires the amd64 architecture.'
fi

if [[ -x "$CHROME_BINARY" ]]; then
    echo "Google Chrome is already installed:"
    "$CHROME_BINARY" --version
    exit 0
fi

if [[ "$EUID" -ne 0 ]]; then
    if ! command -v sudo >/dev/null 2>&1; then
        fail 'Run this installer as root or install sudo.'
    fi

    echo 'Administrator privileges are required. Restarting with sudo...'
    exec sudo -- "$0" "$@"
fi

export DEBIAN_FRONTEND=noninteractive

echo 'Installing download prerequisites...'
apt-get update
apt-get install --yes ca-certificates curl

temporary_directory="$(mktemp -d -t quiqqer-google-chrome.XXXXXXXX)"
package_file="$temporary_directory/google-chrome-stable_current_amd64.deb"

cleanup()
{
    rm -f -- "$package_file"
    rmdir -- "$temporary_directory" 2>/dev/null || true
}

trap cleanup EXIT

echo 'Downloading Google Chrome from dl.google.com...'
curl \
    --fail \
    --location \
    --proto '=https' \
    --silent \
    --show-error \
    --tlsv1.2 \
    --output "$package_file" \
    "$CHROME_PACKAGE_URL"

package_name="$(dpkg-deb --field "$package_file" Package)"
package_architecture="$(dpkg-deb --field "$package_file" Architecture)"

if [[ "$package_name" != 'google-chrome-stable' ]]; then
    fail "Unexpected Debian package name: $package_name"
fi

if [[ "$package_architecture" != 'amd64' ]]; then
    fail "Unexpected Debian package architecture: $package_architecture"
fi

echo 'Installing Google Chrome and its dependencies...'
apt-get install --yes "$package_file"

if [[ ! -x "$CHROME_BINARY" ]]; then
    fail "Installation completed without creating $CHROME_BINARY."
fi

echo 'Google Chrome was installed successfully:'
"$CHROME_BINARY" --version
echo
echo "Configure QUIQQER's Chrome Executable setting with:"
echo "$CHROME_BINARY"
