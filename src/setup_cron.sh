#!/bin/bash

# Get the absolute path of cron.php
CRON_FILE_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/cron.php"
CRON_CMD="*/5 * * * * php $CRON_FILE_PATH"

# Check if CRON is already registered
crontab -l 2>/dev/null | grep -F "$CRON_FILE_PATH" > /dev/null
if [ $? -eq 0 ]; then
    echo "CRON job already exists."
else
    (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
    echo "CRON job added to run every 5 minutes."
fi
