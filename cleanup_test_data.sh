#!/bin/bash

# Test Data Cleanup Script
# Removes test data created by add_test_data.sh script
# Uses the REST API with test user credentials

set -e

# Configuration
API_BASE_URL="http://localhost:8000/api"
TEST_EMAIL="johndoe@example.com"
TEST_PASSWORD="Vyhd7Y#PjTb7!TA"
OUTPUT_DIR="test_data_output"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
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

# Function to make API calls with proper error handling
api_call() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local headers="$4"
    
    local response
    local status_code
    
    if [ -n "$data" ]; then
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" \
            -X "$method" \
            -H "Content-Type: application/json" \
            ${headers:+-H "$headers"} \
            -d "$data" \
            "$API_BASE_URL$endpoint")
    else
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" \
            -X "$method" \
            ${headers:+-H "$headers"} \
            "$API_BASE_URL$endpoint")
    fi
    
    status_code=$(echo "$response" | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
    body=$(echo "$response" | sed -e 's/HTTPSTATUS\:.*//g')
    
    echo "$status_code|$body"
}

# Function to authenticate and get JWT token
authenticate() {
    log_info "Authenticating with test user: $TEST_EMAIL"
    
    local auth_data=$(cat <<EOF
{
    "email": "$TEST_EMAIL",
    "password": "$TEST_PASSWORD"
}
EOF
)
    
    local result=$(api_call "POST" "/auth/login" "$auth_data")
    local status_code=$(echo "$result" | cut -d'|' -f1)
    local response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "201" ]; then
        # Extract token from response
        JWT_TOKEN=$(echo "$response_body" | jq -r '.token // .data.token // .access_token')
        
        if [ "$JWT_TOKEN" = "null" ] || [ -z "$JWT_TOKEN" ]; then
            log_error "Could not extract JWT token from response"
            echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
            exit 1
        fi
        
        log_success "Authentication successful"
        return 0
    else
        log_error "Authentication failed with status: $status_code"
        echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
        exit 1
    fi
}

# Function to delete a design
delete_design() {
    local design_id="$1"
    
    log_info "Deleting design ID: $design_id"
    
    local result=$(api_call "DELETE" "/designs/$design_id" "" "Authorization: Bearer $JWT_TOKEN")
    local status_code=$(echo "$result" | cut -d'|' -f1)
    local response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "204" ]; then
        log_success "Design $design_id deleted successfully"
        return 0
    else
        log_warning "Failed to delete design $design_id (Status: $status_code)"
        if [ "$status_code" = "404" ]; then
            log_info "Design might have been already deleted"
        else
            echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
        fi
        return 1
    fi
}

# Function to delete a template
delete_template() {
    local template_id="$1"
    
    log_info "Deleting template ID: $template_id"
    
    local result=$(api_call "DELETE" "/templates/$template_id" "" "Authorization: Bearer $JWT_TOKEN")
    local status_code=$(echo "$result" | cut -d'|' -f1)
    local response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "204" ]; then
        log_success "Template $template_id deleted successfully"
        return 0
    else
        log_warning "Failed to delete template $template_id (Status: $status_code)"
        if [ "$status_code" = "404" ]; then
            log_info "Template might have been already deleted"
        else
            echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
        fi
        return 1
    fi
}

# Function to cancel/delete an export job
delete_export_job() {
    local job_id="$1"
    
    log_info "Cancelling/deleting export job ID: $job_id"
    
    # First try to cancel the job
    local result=$(api_call "POST" "/export-jobs/$job_id/cancel" "" "Authorization: Bearer $JWT_TOKEN")
    local status_code=$(echo "$result" | cut -d'|' -f1)
    local response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "204" ]; then
        log_success "Export job $job_id cancelled successfully"
        return 0
    elif [ "$status_code" = "404" ]; then
        log_info "Export job $job_id not found (might have been already deleted)"
        return 0
    else
        # If cancel fails, try delete if endpoint exists
        local delete_result=$(api_call "DELETE" "/export-jobs/$job_id" "" "Authorization: Bearer $JWT_TOKEN")
        local delete_status=$(echo "$delete_result" | cut -d'|' -f1)
        
        if [ "$delete_status" = "200" ] || [ "$delete_status" = "204" ]; then
            log_success "Export job $job_id deleted successfully"
            return 0
        else
            log_warning "Failed to cancel/delete export job $job_id"
            echo "Cancel response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
            return 1
        fi
    fi
}

# Function to cleanup all user data (alternative approach)
cleanup_all_user_data() {
    log_info "Fetching all user data for cleanup..."
    
    # Get all designs
    local designs_result=$(api_call "GET" "/designs?limit=100" "" "Authorization: Bearer $JWT_TOKEN")
    local designs_status=$(echo "$designs_result" | cut -d'|' -f1)
    local designs_body=$(echo "$designs_result" | cut -d'|' -f2)
    
    if [ "$designs_status" = "200" ]; then
        local design_ids=$(echo "$designs_body" | jq -r '.data[]?.id // .designs[]?.id // empty' 2>/dev/null)
        if [ -n "$design_ids" ]; then
            log_info "Found $(echo "$design_ids" | wc -l) designs to delete"
            while IFS= read -r design_id; do
                [ -n "$design_id" ] && delete_design "$design_id"
            done <<< "$design_ids"
        else
            log_info "No designs found to delete"
        fi
    fi
    
    # Get all templates (user-created ones)
    local templates_result=$(api_call "GET" "/templates?limit=100" "" "Authorization: Bearer $JWT_TOKEN")
    local templates_status=$(echo "$templates_result" | cut -d'|' -f1)
    local templates_body=$(echo "$templates_result" | cut -d'|' -f2)
    
    if [ "$templates_status" = "200" ]; then
        local template_ids=$(echo "$templates_body" | jq -r '.data[]?.id // .templates[]?.id // empty' 2>/dev/null)
        if [ -n "$template_ids" ]; then
            log_info "Found $(echo "$template_ids" | wc -l) templates to delete"
            while IFS= read -r template_id; do
                [ -n "$template_id" ] && delete_template "$template_id"
            done <<< "$template_ids"
        else
            log_info "No templates found to delete"
        fi
    fi
    
    # Get all export jobs
    local jobs_result=$(api_call "GET" "/export-jobs?limit=100" "" "Authorization: Bearer $JWT_TOKEN")
    local jobs_status=$(echo "$jobs_result" | cut -d'|' -f1)
    local jobs_body=$(echo "$jobs_result" | cut -d'|' -f2)
    
    if [ "$jobs_status" = "200" ]; then
        local job_ids=$(echo "$jobs_body" | jq -r '.data[]?.id // .jobs[]?.id // empty' 2>/dev/null)
        if [ -n "$job_ids" ]; then
            log_info "Found $(echo "$job_ids" | wc -l) export jobs to cancel/delete"
            while IFS= read -r job_id; do
                [ -n "$job_id" ] && delete_export_job "$job_id"
            done <<< "$job_ids"
        else
            log_info "No export jobs found to delete"
        fi
    fi
}

# Main execution
main() {
    log_info "Starting test data cleanup for Design Platform API"
    echo "=========================================================="
    
    # Check dependencies
    if ! command -v curl &> /dev/null; then
        log_error "curl is required but not installed"
        exit 1
    fi
    
    if ! command -v jq &> /dev/null; then
        log_error "jq is required but not installed"
        exit 1
    fi
    
    # Authenticate
    authenticate
    
    echo ""
    
    # Check if we have the test data IDs file
    if [ -f "$OUTPUT_DIR/test_data_ids.json" ]; then
        log_info "Found test data IDs file, cleaning up specific items..."
        
        # Read the test data IDs
        local test_data=$(cat "$OUTPUT_DIR/test_data_ids.json")
        
        # Delete export jobs first (they depend on designs)
        log_info "Cleaning up export jobs..."
        echo "----------------------------------------"
        local export_job_ids=$(echo "$test_data" | jq -r '.exportJobs[]? // empty')
        if [ -n "$export_job_ids" ]; then
            while IFS= read -r job_id; do
                [ -n "$job_id" ] && delete_export_job "$job_id"
            done <<< "$export_job_ids"
        else
            log_info "No export jobs found in test data"
        fi
        
        # Delete templates
        echo ""
        log_info "Cleaning up templates..."
        echo "----------------------------------------"
        local template_ids=$(echo "$test_data" | jq -r '.templates[]? // empty')
        if [ -n "$template_ids" ]; then
            while IFS= read -r template_id; do
                [ -n "$template_id" ] && delete_template "$template_id"
            done <<< "$template_ids"
        else
            log_info "No templates found in test data"
        fi
        
        # Delete designs
        echo ""
        log_info "Cleaning up designs..."
        echo "----------------------------------------"
        local design_ids=$(echo "$test_data" | jq -r '.designs[]? // empty')
        if [ -n "$design_ids" ]; then
            while IFS= read -r design_id; do
                [ -n "$design_id" ] && delete_design "$design_id"
            done <<< "$design_ids"
        else
            log_info "No designs found in test data"
        fi
        
        # Remove the test data files
        log_info "Removing test data files..."
        rm -f "$OUTPUT_DIR/test_data_ids.json"
        rm -f "$OUTPUT_DIR/test_data_summary.md"
        [ -d "$OUTPUT_DIR" ] && rmdir "$OUTPUT_DIR" 2>/dev/null || true
        
    else
        log_warning "Test data IDs file not found, performing full cleanup of user data..."
        echo ""
        read -p "This will delete ALL designs, templates, and export jobs for the test user. Continue? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            cleanup_all_user_data
        else
            log_info "Cleanup cancelled by user"
            exit 0
        fi
    fi
    
    echo ""
    echo "=========================================================="
    log_success "Test data cleanup completed!"
}

# Run main function
main "$@"
