#!/bin/bash

# IAMGickPro Production Installer
# Simple wrapper script to launch the main installer

set -euo pipefail

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly WHITE='\033[1;37m'
readonly NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_SCRIPT="$SCRIPT_DIR/scripts/installer/install.sh"

print_header() {
    clear
    echo -e "${PURPLE}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║                    IAMGickPro Installer                     ║"
    echo "║              Professional Design Platform                   ║"
    echo "║                        v1.0.0                               ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo
}

print_error() {
    echo -e "${RED}✗ ${WHITE}$1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ ${WHITE}$1${NC}"
}

print_info() {
    echo -e "${CYAN}ℹ ${WHITE}$1${NC}"
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This installer must be run as root (use sudo)"
        echo
        echo "Usage: sudo ./install.sh"
        exit 1
    fi
}

# Check if installer script exists
check_installer() {
    if [[ ! -f "$INSTALLER_SCRIPT" ]]; then
        print_error "Installer script not found at: $INSTALLER_SCRIPT"
        echo
        echo "Please ensure you're running this script from the project root directory."
        exit 1
    fi
}

# Main function
main() {
    print_header
    
    echo -e "${WHITE}Welcome to the IAMGickPro Production Installer!${NC}"
    echo
    echo "This installer will set up a complete production environment for"
    echo "the IAMGickPro design platform."
    echo
    
    check_root
    check_installer
    
    print_info "Launching main installer..."
    echo
    
    # Make installer executable
    chmod +x "$INSTALLER_SCRIPT"
    
    # Execute the main installer
    exec "$INSTALLER_SCRIPT" "$@"
}

# Run main function
main "$@"
