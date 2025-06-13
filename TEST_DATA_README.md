# Test Data Generation Scripts

This directory contains scripts to generate and manage test data for the Design Platform API, including designs, templates, and export jobs.

## 📋 Prerequisites

Before using these scripts, ensure you have:

1. **Backend server running** on `http://localhost:8000`
2. **Required tools installed**:
   - `curl` - for API calls
   - `jq` - for JSON processing
3. **Test user account** (already configured):
   - Email: `johndoe@example.com`
   - Password: `Vyhd7Y#PjTb7!TA`

## 🚀 Quick Start

### Option 1: Use the Helper Script (Recommended)

```bash
# Check dependencies and server status
./test_data.sh status

# Add test data
./test_data.sh add

# Clean up test data
./test_data.sh cleanup

# Show help
./test_data.sh help
```

### Option 2: Use Individual Scripts

```bash
# Add test data
./add_test_data.sh

# Clean up test data
./cleanup_test_data.sh
```

## 📊 What Gets Created

### Designs (10 items)
- **YouTube Thumbnails**: Gaming, tech review, tutorial thumbnails (1280×720)
- **Social Media**: Instagram posts, Facebook covers, Twitter headers
- **Presentations**: Business presentations, marketing pitches (1920×1080)
- **Print**: Business cards, flyers

### Templates (10 items)
- **Video Templates**: Gaming, tech review, tutorial templates
- **Social Media Templates**: Quote posts, product showcases, event announcements
- **Business Templates**: Corporate presentations, marketing reports
- **Print Templates**: Business cards, event flyers

### Export Jobs (8 items)
- **PNG exports**: High, medium, ultra quality
- **JPEG exports**: High, medium quality
- **PDF exports**: High, ultra quality
- **SVG exports**: High quality

## 📁 Output Files

After running the scripts, you'll find:

```
test_data_output/
├── test_data_ids.json      # JSON with all created IDs
└── test_data_summary.md    # Human-readable summary
```

### Sample test_data_ids.json
```json
{
  "timestamp": "2025-06-13T10:30:00Z",
  "designs": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
  "templates": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
  "exportJobs": [1, 2, 3, 4, 5, 6, 7, 8]
}
```

## 🛠️ Script Details

### `add_test_data.sh`
- **Purpose**: Creates test designs, templates, and export jobs
- **Authentication**: Uses JWT token from login API
- **Error Handling**: Continues on individual failures, reports summary
- **Output**: Creates JSON and markdown summary files

### `cleanup_test_data.sh`
- **Purpose**: Removes test data created by add_test_data.sh
- **Smart Cleanup**: Uses test_data_ids.json to remove specific items
- **Fallback**: Can remove all user data if ID file is missing
- **Safety**: Prompts for confirmation before full cleanup

### `test_data.sh`
- **Purpose**: Helper script with easy commands
- **Features**: Dependency checking, server status verification
- **Commands**: `add`, `cleanup`, `status`, `help`

## 🔧 Troubleshooting

### Common Issues

1. **"curl is required but not installed"**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install curl
   
   # CentOS/RHEL
   sudo yum install curl
   
   # macOS
   brew install curl
   ```

2. **"jq is required but not installed"**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install jq
   
   # CentOS/RHEL
   sudo yum install jq
   
   # macOS
   brew install jq
   ```

3. **"Backend server is not responding"**
   ```bash
   # Start the backend server
   cd backend && php -S localhost:8000 -t public/
   
   # Or use VS Code task
   # Run: "Start Backend Dev Server"
   ```

4. **"Authentication failed"**
   - Ensure the test user exists in the database
   - Check if the backend is properly configured
   - Verify the test credentials are correct

### Debug Mode

For detailed output, you can run scripts with bash debugging:

```bash
bash -x ./add_test_data.sh
bash -x ./cleanup_test_data.sh
```

## 📚 API Endpoints Used

The scripts interact with these API endpoints:

- `POST /api/auth/login` - Authentication
- `POST /api/designs` - Create designs
- `DELETE /api/designs/{id}` - Delete designs
- `POST /api/templates` - Create templates
- `DELETE /api/templates/{id}` - Delete templates
- `POST /api/export-jobs` - Create export jobs
- `POST /api/export-jobs/{id}/cancel` - Cancel export jobs
- `GET /api/designs` - List designs (for cleanup)
- `GET /api/templates` - List templates (for cleanup)
- `GET /api/export-jobs` - List export jobs (for cleanup)

## 🎯 Use Cases

### Development Testing
```bash
# Add test data for frontend development
./test_data.sh add

# Test your changes...

# Clean up when done
./test_data.sh cleanup
```

### Demo Preparation
```bash
# Create rich demo data
./test_data.sh add

# Check what was created
./test_data.sh status
```

### Automated Testing
```bash
# In CI/CD pipeline
./add_test_data.sh
# Run tests...
./cleanup_test_data.sh
```

## 🔒 Security Notes

- Scripts use test credentials only
- Only affects the test user's data
- No production data is modified
- All API calls are properly authenticated

## 📝 Customization

### Adding More Test Data

Edit `add_test_data.sh` to add more designs/templates:

```bash
# Add more designs
DESIGN_IDS+=($(create_design "Custom Design" "Description" 800 600))

# Add more templates
TEMPLATE_IDS+=($(create_template "Custom Template" "Description" "category" '["tag1", "tag2"]' 800 600))
```

### Modifying Design Data

Update the `designData` section in the create functions to change:
- Layer configurations
- Canvas settings
- Design properties
- Color schemes

## 🚀 Integration with Frontend

After adding test data, you can:

1. **Visit the frontend**: `http://localhost:3000`
2. **Login with test user**: `johndoe@example.com`
3. **Navigate to**:
   - My Designs page to see created designs
   - Templates page to see created templates
   - Export Jobs page to see export status

The test data provides a realistic environment for testing the frontend components, especially the DesignCard and search functionality.

---

**Happy Testing! 🎉**
