#!/bin/bash
# Move to the current directory (optional)
cd "$(dirname "$0")"

# Stage all changes
git add .

# Commit with a timestamp so you know when the backup occurred
git commit -m "Backup: $(date +'%Y-%m-%d %H:%M:%S')"

# Push specifically to the master branch
git push origin master
