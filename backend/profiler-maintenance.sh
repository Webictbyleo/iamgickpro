# Profiler Maintenance Script
#
# This script should be run periodically (e.g., via cron) to prevent
# profiler storage issues like null byte errors in filenames.
#
# Add to crontab with:
# 0 2 * * * /path/to/this/script.sh
#
# Or run manually when issues arise.

#!/bin/bash

# Change to the backend directory
cd "$(dirname "$0")"

echo "Starting profiler maintenance..."

# Clean the profiler cache safely
php bin/console app:profiler:cleanup

# Clear the entire cache if needed (uncomment if problems persist)
# php bin/console cache:clear --env=dev --no-warmup

echo "Profiler maintenance completed."
