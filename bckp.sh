#!/bin/bash
# Navigate to project root (optional if running from there)
git add .
git commit -m "Backup: $(date +'%Y-%m-%d %H:%M:%S')"
git push origin main
