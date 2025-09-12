#!/bin/bash

# Script to fix permissions for existing isolated users
# This will change home directory permissions from 755 to 750
# preventing other users from accessing each other's directories

echo "Starting to fix isolated user permissions..."
echo "==========================================="

# Get the main server user (usually 'vito')
SERVER_USER="vito"

# Counter for processed users
FIXED_COUNT=0
SKIPPED_COUNT=0

# Get all home directories except root and the server user
for USER_DIR in /home/*; do
    # Skip if not a directory
    if [ ! -d "$USER_DIR" ]; then
        continue
    fi
    
    # Get the username from the directory path
    USERNAME=$(basename "$USER_DIR")
    
    # Skip system users (vito, ubuntu, etc.)
    if [ "$USERNAME" = "$SERVER_USER" ] || [ "$USERNAME" = "ubuntu" ] || [ "$USERNAME" = "root" ]; then
        echo "Skipping system user: $USERNAME"
        SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
        continue
    fi
    
    echo "Processing user: $USERNAME"
    
    # Check if this is an isolated user (has the expected directory structure)
    if [ -d "$USER_DIR/.logs" ] && [ -d "$USER_DIR/tmp" ] && [ -d "$USER_DIR/bin" ]; then
        echo "  → Identified as isolated user"
        
        # Ensure the server user is in the isolated user's group
        sudo usermod -a -G "$USERNAME" "$SERVER_USER" 2>/dev/null
        if [ $? -eq 0 ]; then
            echo "  → Added $SERVER_USER to $USERNAME group"
        else
            echo "  → $SERVER_USER already in $USERNAME group or group doesn't exist"
        fi
        
        # Fix ownership (should already be correct, but just in case)
        sudo chown -R "$USERNAME:$USERNAME" "$USER_DIR"
        echo "  → Fixed ownership to $USERNAME:$USERNAME"
        
        # Fix permissions: 750 for home directory
        sudo chmod 750 "$USER_DIR"
        echo "  → Set home directory permissions to 750"
        
        # Ensure .ssh directory has proper permissions
        if [ -d "$USER_DIR/.ssh" ]; then
            sudo chmod 700 "$USER_DIR/.ssh"
            echo "  → Set .ssh directory permissions to 700"
        fi
        
        # Set proper permissions for subdirectories
        for SUBDIR in ".logs" "tmp" "bin"; do
            if [ -d "$USER_DIR/$SUBDIR" ]; then
                sudo chmod 750 "$USER_DIR/$SUBDIR"
                echo "  → Set $SUBDIR directory permissions to 750"
            fi
        done
        
        FIXED_COUNT=$((FIXED_COUNT + 1))
        echo "  ✓ User $USERNAME fixed successfully"
    else
        echo "  → Not an isolated user, skipping"
        SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
    fi
    
    echo ""
done

echo "==========================================="
echo "Permission fix completed!"
echo "Fixed: $FIXED_COUNT isolated users"
echo "Skipped: $SKIPPED_COUNT users"
echo ""
echo "You may need to restart PHP-FPM and Nginx for changes to take full effect:"
echo "  sudo systemctl restart php*-fpm"
echo "  sudo systemctl restart nginx"