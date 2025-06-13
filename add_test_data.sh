#!/bin/bash

# Test Data Generator Script
# Creates temporary design templates and export jobs for testing
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

# Function to create a design
create_design() {
    local name="$1"
    local description="$2"
    local width="$3"
    local height="$4"
    
    log_info "Creating design: $name"
    
    local design_data=$(cat <<EOF
{
    "name": "$name",
    "description": "$description",
    "width": $width,
    "height": $height,
    "data": {
        "version": "1.0",
        "canvas": {
            "width": $width,
            "height": $height,
            "backgroundColor": "#ffffff"
        },
        "layers": [
            {
                "id": "text-layer-1",
                "type": "text",
                "name": "Sample Text",
                "visible": true,
                "locked": false,
                "opacity": 1,
                "x": 50,
                "y": 50,
                "width": 200,
                "height": 50,
                "rotation": 0,
                "scaleX": 1,
                "scaleY": 1,
                "zIndex": 1,
                "properties": {
                    "text": "Sample Design Text",
                    "fontFamily": "Arial",
                    "fontSize": 24,
                    "fontWeight": "normal",
                    "fontStyle": "normal",
                    "textAlign": "left",
                    "color": "#000000",
                    "lineHeight": 1.2,
                    "letterSpacing": 0,
                    "textDecoration": "none",
                    "autoResize": {
                        "enabled": true,
                        "mode": "width"
                    }
                }
            },
            {
                "id": "shape-layer-1",
                "type": "shape",
                "name": "Background Shape",
                "visible": true,
                "locked": false,
                "opacity": 0.8,
                "x": 0,
                "y": 0,
                "width": $width,
                "height": $height,
                "rotation": 0,
                "scaleX": 1,
                "scaleY": 1,
                "zIndex": 0,
                "properties": {
                    "shapeType": "rectangle",
                    "fill": {
                        "type": "solid",
                        "color": "#f0f9ff",
                        "opacity": 1
                    },
                    "stroke": "#e0e7ff",
                    "strokeWidth": 2,
                    "strokeOpacity": 1,
                    "cornerRadius": 8,
                    "sides": 4,
                    "points": 5,
                    "innerRadius": 0.5,
                    "x1": 0,
                    "y1": 0,
                    "x2": $width,
                    "y2": $height
                }
            }
        ]
    }
}
EOF
)
    
    local result=$(api_call "POST" "/designs" "$design_data" "Authorization: Bearer $JWT_TOKEN")
    local status_code=$(echo "$result" | cut -d'|' -f1)
    local response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "201" ]; then
        local design_id=$(echo "$response_body" | jq -r '.data.design.id // .design.id // .id')
        log_success "Design created successfully with ID: $design_id"
        echo "$design_id"
    else
        log_error "Failed to create design '$name' with status: $status_code"
        echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
        echo ""
    fi
}

# Function to create a template
create_template() {
    local name="$1"
    local description="$2"
    local category="$3"
    local tags="$4"
    local width="$5"
    local height="$6"
    
    log_info "Creating template: $name"
    
    local template_data=$(cat <<EOF
{
    "name": "$name",
    "description": "$description",
    "category": "$category",
    "tags": $tags,
    "width": $width,
    "height": $height,
    "designData": {
        "version": "1.0",
        "canvas": {
            "width": $width,
            "height": $height,
            "backgroundColor": "#ffffff"
        },
        "layers": [
            {
                "id": "template-text-1",
                "type": "text",
                "name": "Title Text",
                "visible": true,
                "locked": false,
                "opacity": 1,
                "x": 50,
                "y": 50,
                "width": $(($width - 100)),
                "height": 60,
                "rotation": 0,
                "scaleX": 1,
                "scaleY": 1,
                "zIndex": 2,
                "properties": {
                    "text": "$name Template",
                    "fontFamily": "Arial",
                    "fontSize": 32,
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#1f2937",
                    "lineHeight": 1.2,
                    "letterSpacing": 0,
                    "textDecoration": "none",
                    "autoResize": {
                        "enabled": true,
                        "mode": "height"
                    }
                }
            },
            {
                "id": "template-shape-1",
                "type": "shape",
                "name": "Accent Shape",
                "visible": true,
                "locked": false,
                "opacity": 0.9,
                "x": 20,
                "y": 20,
                "width": $(($width - 40)),
                "height": $(($height - 40)),
                "rotation": 0,
                "scaleX": 1,
                "scaleY": 1,
                "zIndex": 1,
                "properties": {
                    "shapeType": "rectangle",
                    "fill": {
                        "type": "linear",
                        "colors": [
                            {"color": "#3b82f6", "stop": 0, "opacity": 0.1},
                            {"color": "#8b5cf6", "stop": 1, "opacity": 0.3}
                        ],
                        "angle": 45
                    },
                    "stroke": "#6366f1",
                    "strokeWidth": 2,
                    "strokeOpacity": 0.5,
                    "cornerRadius": 12,
                    "sides": 4,
                    "points": 5,
                    "innerRadius": 0.5,
                    "x1": 0,
                    "y1": 0,
                    "x2": $(($width - 40)),
                    "y2": $(($height - 40))
                }
            }
        ]
    },
    "isPremium": false,
    "isActive": true
}
EOF
)
    
    local result=$(api_call "POST" "/templates" "$template_data" "Authorization: Bearer $JWT_TOKEN")
    local status_code=$(echo "$result" | cut -d'|' -f1)
    local response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "201" ]; then
        local template_id=$(echo "$response_body" | jq -r '.data.template.id // .template.id // .id')
        log_success "Template created successfully with ID: $template_id"
        echo "$template_id"
    else
        log_error "Failed to create template '$name' with status: $status_code"
        echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
        echo ""
    fi
}

# Function to create an export job
create_export_job() {
    local design_id="$1"
    local format="$2"
    local quality="$3"
    
    log_info "Creating export job for design ID: $design_id (Format: $format, Quality: $quality)"
    
    local export_data=$(cat <<EOF
{
    "designId": $design_id,
    "format": "$format",
    "quality": "$quality",
    "options": {
        "scale": 1,
        "transparent": false
    }
}
EOF
)
    
    local result=$(api_call "POST" "/export-jobs" "$export_data" "Authorization: Bearer $JWT_TOKEN")
    local status_code=$(echo "$result" | cut -d'|' -f1)
    local response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "201" ]; then
        local job_id=$(echo "$response_body" | jq -r '.data.job.id // .job.id // .id')
        log_success "Export job created successfully with ID: $job_id"
        echo "$job_id"
    else
        log_error "Failed to create export job for design $design_id with status: $status_code"
        echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
        echo ""
    fi
}

# Main execution
main() {
    log_info "Starting test data generation for Design Platform API"
    echo "=========================================================="
    
    # Check dependencies
    if ! command -v curl &> /dev/null; then
        log_error "curl is required but not installed"
        exit 1
    fi
    
    if ! command -v jq &> /dev/null; then
        log_error "jq is required but not installed. Please install it:"
        echo "  Ubuntu/Debian: sudo apt-get install jq"
        echo "  CentOS/RHEL: sudo yum install jq"
        echo "  macOS: brew install jq"
        exit 1
    fi
    
    # Create output directory
    mkdir -p "$OUTPUT_DIR"
    
    # Authenticate
    authenticate
    
    echo ""
    log_info "Creating test designs..."
    echo "----------------------------------------"
    
    # Create test designs
    declare -a DESIGN_IDS
    
    # YouTube Thumbnail designs
    DESIGN_IDS+=($(create_design "Gaming Thumbnail 1" "Epic gaming moment thumbnail" 1280 720))
    DESIGN_IDS+=($(create_design "Tech Review Thumbnail" "Product review video thumbnail" 1280 720))
    DESIGN_IDS+=($(create_design "Tutorial Thumbnail" "How-to video thumbnail design" 1280 720))
    
    # Social Media designs
    DESIGN_IDS+=($(create_design "Instagram Post" "Social media post design" 1080 1080))
    DESIGN_IDS+=($(create_design "Facebook Cover" "Facebook page cover design" 1200 630))
    DESIGN_IDS+=($(create_design "Twitter Header" "Twitter profile header" 1500 500))
    
    # Presentation designs
    DESIGN_IDS+=($(create_design "Business Presentation" "Corporate presentation slide" 1920 1080))
    DESIGN_IDS+=($(create_design "Marketing Pitch" "Sales pitch presentation" 1920 1080))
    
    # Print designs
    DESIGN_IDS+=($(create_design "Business Card" "Professional business card" 1050 600))
    DESIGN_IDS+=($(create_design "Flyer Design" "Event promotion flyer" 2480 3508))
    
    echo ""
    log_info "Creating test templates..."
    echo "----------------------------------------"
    
    # Create test templates
    declare -a TEMPLATE_IDS
    
    # YouTube Thumbnail templates
    TEMPLATE_IDS+=($(create_template "Gaming Thumbnail Template" "Perfect for gaming videos and streams" "video" '["gaming", "youtube", "thumbnail", "red", "bold"]' 1280 720))
    TEMPLATE_IDS+=($(create_template "Tech Review Template" "Clean template for technology reviews" "video" '["tech", "review", "youtube", "minimal", "blue"]' 1280 720))
    TEMPLATE_IDS+=($(create_template "Tutorial Template" "Educational content thumbnail" "video" '["tutorial", "education", "youtube", "learning"]' 1280 720))
    
    # Social Media templates
    TEMPLATE_IDS+=($(create_template "Instagram Quote Post" "Inspirational quote template" "social-media" '["instagram", "quote", "motivational", "square"]' 1080 1080))
    TEMPLATE_IDS+=($(create_template "Product Showcase" "Product promotion template" "marketing" '["product", "showcase", "sale", "ecommerce"]' 1080 1350))
    TEMPLATE_IDS+=($(create_template "Event Announcement" "Event promotion template" "marketing" '["event", "announcement", "party", "celebration"]' 1080 1080))
    
    # Business templates
    TEMPLATE_IDS+=($(create_template "Corporate Presentation" "Professional business presentation" "presentation" '["business", "corporate", "professional", "clean"]' 1920 1080))
    TEMPLATE_IDS+=($(create_template "Marketing Report" "Monthly marketing report template" "presentation" '["marketing", "report", "analytics", "data"]' 1920 1080))
    
    # Print templates
    TEMPLATE_IDS+=($(create_template "Modern Business Card" "Sleek business card design" "print" '["business-card", "professional", "modern", "contact"]' 1050 600))
    TEMPLATE_IDS+=($(create_template "Event Flyer" "Eye-catching event promotion" "print" '["flyer", "event", "promotion", "colorful"]' 2480 3508))
    
    echo ""
    log_info "Creating test export jobs..."
    echo "----------------------------------------"
    
    # Create export jobs for various designs
    declare -a EXPORT_JOB_IDS
    
    # Only create export jobs for designs that were successfully created
    local valid_designs=()
    for design_id in "${DESIGN_IDS[@]}"; do
        if [[ "$design_id" =~ ^[0-9]+$ ]]; then
            valid_designs+=("$design_id")
        fi
    done
    
    if [ ${#valid_designs[@]} -gt 0 ]; then
        # PNG exports
        EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[0]}" "png" "high"))
        [ ${#valid_designs[@]} -gt 1 ] && EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[1]}" "png" "medium"))
        [ ${#valid_designs[@]} -gt 2 ] && EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[2]}" "png" "ultra"))
        
        # JPEG exports
        [ ${#valid_designs[@]} -gt 3 ] && EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[3]}" "jpeg" "high"))
        [ ${#valid_designs[@]} -gt 4 ] && EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[4]}" "jpeg" "medium"))
        
        # PDF exports
        [ ${#valid_designs[@]} -gt 5 ] && EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[5]}" "pdf" "high"))
        [ ${#valid_designs[@]} -gt 6 ] && EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[6]}" "pdf" "ultra"))
        
        # SVG exports
        [ ${#valid_designs[@]} -gt 7 ] && EXPORT_JOB_IDS+=($(create_export_job "${valid_designs[7]}" "svg" "high"))
    else
        log_warning "No valid designs created, skipping export job creation"
    fi
    
    echo ""
    log_info "Saving results to output files..."
    echo "----------------------------------------"
    
    # Save results to files
    {
        echo "# Test Data Generation Results"
        echo "Generated on: $(date)"
        echo ""
        echo "## Created Designs"
        for i in "${!DESIGN_IDS[@]}"; do
            echo "Design $((i+1)): ID ${DESIGN_IDS[$i]}"
        done
        echo ""
        echo "## Created Templates"
        for i in "${!TEMPLATE_IDS[@]}"; do
            echo "Template $((i+1)): ID ${TEMPLATE_IDS[$i]}"
        done
        echo ""
        echo "## Created Export Jobs"
        for i in "${!EXPORT_JOB_IDS[@]}"; do
            echo "Export Job $((i+1)): ID ${EXPORT_JOB_IDS[$i]}"
        done
    } > "$OUTPUT_DIR/test_data_summary.md"
    
    # Save IDs as JSON for programmatic access
    {
        echo "{"
        echo "  \"timestamp\": \"$(date -Iseconds)\","
        echo "  \"designs\": ["
        for i in "${!DESIGN_IDS[@]}"; do
            echo -n "    ${DESIGN_IDS[$i]}"
            [ $i -lt $((${#DESIGN_IDS[@]} - 1)) ] && echo "," || echo ""
        done
        echo "  ],"
        echo "  \"templates\": ["
        for i in "${!TEMPLATE_IDS[@]}"; do
            echo -n "    ${TEMPLATE_IDS[$i]}"
            [ $i -lt $((${#TEMPLATE_IDS[@]} - 1)) ] && echo "," || echo ""
        done
        echo "  ],"
        echo "  \"exportJobs\": ["
        for i in "${!EXPORT_JOB_IDS[@]}"; do
            echo -n "    ${EXPORT_JOB_IDS[$i]}"
            [ $i -lt $((${#EXPORT_JOB_IDS[@]} - 1)) ] && echo "," || echo ""
        done
        echo "  ]"
        echo "}"
    } > "$OUTPUT_DIR/test_data_ids.json"
    
    echo ""
    echo "=========================================================="
    log_success "Test data generation completed!"
    echo ""
    echo "Summary:"
    echo "  - Designs created: ${#DESIGN_IDS[@]}"
    echo "  - Templates created: ${#TEMPLATE_IDS[@]}"
    echo "  - Export jobs created: ${#EXPORT_JOB_IDS[@]}"
    echo ""
    echo "Output files:"
    echo "  - Summary: $OUTPUT_DIR/test_data_summary.md"
    echo "  - IDs (JSON): $OUTPUT_DIR/test_data_ids.json"
    echo ""
    log_info "You can now test the frontend with this data!"
}

# Run main function
main "$@"
