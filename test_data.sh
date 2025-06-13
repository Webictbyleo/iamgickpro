#!/bin/bash

# Test Data Management Helper
# Provides easy commands to manage test data for the Design Platform

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

show_help() {
    echo "Test Data Management Helper"
    echo "=========================="
    echo ""
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  add       Add test data (designs, templates, export jobs)"
    echo "  cleanup   Remove test data"
    echo "  status    Check current test data status"
    echo "  help      Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 add      # Add test data"
    echo "  $0 cleanup  # Remove test data"
    echo "  $0 status   # Check what test data exists"
    echo ""
    echo "Test User Credentials:"
    echo "  Email: johndoe@example.com"
    echo "  Password: Vyhd7Y#PjTb7!TA"
    echo ""
    echo "API Base URL: http://localhost:8000/api"
}

check_dependencies() {
    local missing_deps=()
    
    if ! command -v curl &> /dev/null; then
        missing_deps+=("curl")
    fi
    
    if ! command -v jq &> /dev/null; then
        missing_deps+=("jq")
    fi
    
    if [ ${#missing_deps[@]} -gt 0 ]; then
        log_error "Missing required dependencies: ${missing_deps[*]}"
        echo ""
        echo "Install instructions:"
        echo "  Ubuntu/Debian: sudo apt-get install ${missing_deps[*]}"
        echo "  CentOS/RHEL: sudo yum install ${missing_deps[*]}"
        echo "  macOS: brew install ${missing_deps[*]}"
        return 1
    fi
    
    return 0
}

check_server_status() {
    log_info "Checking backend server status..."
    
    # Try the auth login endpoint with invalid data to check if server is responding
    local response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X POST \
        -H "Content-Type: application/json" \
        -d '{"email":"test","password":"test"}' \
        "http://localhost:8000/api/auth/login" 2>/dev/null || echo "HTTPSTATUS:000")
    local status_code=$(echo "$response" | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
    
    # We expect 400 (bad request) or 401 (unauthorized) - these mean server is running
    if [ "$status_code" = "400" ] || [ "$status_code" = "401" ] || [ "$status_code" = "422" ] || [ "$status_code" = "200" ]; then
        log_success "Backend server is running (Status: $status_code)"
        return 0
    else
        log_error "Backend server is not responding (Status: $status_code)"
        echo ""
        echo "Please make sure the backend server is running:"
        echo "  cd backend && php -S localhost:8000 -t public/"
        echo ""
        echo "Or use the VS Code task: 'Start Backend Dev Server'"
        return 1
    fi
}

show_status() {
    log_info "Checking test data status..."
    echo "==============================="
    
    if [ -f "test_data_output/test_data_ids.json" ]; then
        local test_data=$(cat "test_data_output/test_data_ids.json")
        local timestamp=$(echo "$test_data" | jq -r '.timestamp')
        local design_count=$(echo "$test_data" | jq '.designs | length')
        local template_count=$(echo "$test_data" | jq '.templates | length')
        local job_count=$(echo "$test_data" | jq '.exportJobs | length')
        
        echo ""
        log_success "Test data found!"
        echo "  Created: $timestamp"
        echo "  Designs: $design_count"
        echo "  Templates: $template_count"
        echo "  Export Jobs: $job_count"
        echo ""
        echo "Files:"
        echo "  - test_data_output/test_data_ids.json"
        echo "  - test_data_output/test_data_summary.md"
        
    else
        log_warning "No test data found"
        echo ""
        echo "Run '$0 add' to create test data"
    fi
}

add_test_data() {
    log_info "Adding test data..."
    echo "==================="
    
    # Check if backend server is running
    if ! check_server_status; then
        return 1
    fi
    
    # Check if test data already exists
    if [ -f "test_data_output/test_data_ids.json" ]; then
        log_warning "Test data already exists!"
        echo ""
        read -p "Do you want to add more test data or replace existing? (add/replace/cancel): " -r
        echo
        case $REPLY in
            replace|r)
                log_info "Cleaning up existing test data first..."
                ./cleanup_test_data.sh
                echo ""
                ;;
            add|a)
                log_info "Adding to existing test data..."
                ;;
            *)
                log_info "Operation cancelled"
                return 0
                ;;
        esac
    fi
    
    # Run the add test data script
    ./add_test_data.sh
}

cleanup_test_data() {
    log_info "Cleaning up test data..."
    echo "========================"
    
    # Check if backend server is running
    if ! check_server_status; then
        return 1
    fi
    
    # Run the cleanup script
    ./cleanup_test_data.sh
}

main() {
    # Check dependencies first
    if ! check_dependencies; then
        exit 1
    fi
    
    # Parse command
    case "${1:-help}" in
        add)
            add_test_data
            ;;
        cleanup|clean|remove)
            cleanup_test_data
            ;;
        status|stat|check)
            show_status
            ;;
        help|--help|-h|"")
            show_help
            ;;
        *)
            log_error "Unknown command: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

main "$@"
