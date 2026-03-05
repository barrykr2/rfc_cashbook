#!/bin/bash

# Check if a token was provided
TOKEN=$1

if [ -z "$TOKEN" ]; then
    echo "Error: No GitHub token provided."
    echo "Usage: ./backup.sh YOUR_GITHUB_TOKEN"
    exit 1
fi

# Define your repo details
USERNAME="barrykr2"
REPO="rfc_cashbook"

# Stage and Commit
git add .
git commit -m "Backup: $(date +'%Y-%m-%d %H:%M:%S')"

# Push using the token in the URL
# This format handles the authentication inline
git push https://$TOKEN@github.com/$USERNAME/$REPO.git master
