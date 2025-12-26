#!/bin/bash
# Archive ALL projects except inbred
# Keeps only /home/kylewee/code/inbred as working directory

set -e

ARCHIVE_DIR="/home/kylewee/code/ARCHIVE"
DATE_STAMP=$(date +%Y%m%d-%H%M%S)

echo "═══════════════════════════════════════════════════"
echo "  ARCHIVING ALL PROJECTS EXCEPT INBRED"
echo "═══════════════════════════════════════════════════"
echo ""

# Create archive directory if it doesn't exist
mkdir -p "$ARCHIVE_DIR"

# 1. Archive idk
if [ -d "/home/kylewee/code/idk" ]; then
    echo "Archiving idk..."
    mv "/home/kylewee/code/idk" "$ARCHIVE_DIR/idk-$DATE_STAMP"
    echo "✓ Archived idk → ARCHIVE/idk-$DATE_STAMP"
fi

# 2. Archive shopmonkey-sync
if [ -d "/home/kylewee/code/shopmonkey-sync" ]; then
    echo "Archiving shopmonkey-sync..."
    mv "/home/kylewee/code/shopmonkey-sync" "$ARCHIVE_DIR/shopmonkey-sync-$DATE_STAMP"
    echo "✓ Archived shopmonkey-sync → ARCHIVE/shopmonkey-sync-$DATE_STAMP"
fi

# 3. Archive mechanicstaugustine
if [ -d "/home/kylewee/code/mechanicstaugustine" ]; then
    echo "Archiving mechanicstaugustine..."
    mv "/home/kylewee/code/mechanicstaugustine" "$ARCHIVE_DIR/mechanicstaugustine-$DATE_STAMP"
    echo "✓ Archived mechanicstaugustine → ARCHIVE/mechanicstaugustine-$DATE_STAMP"
fi

# 4. Archive inbred-backup-1431
if [ -d "/home/kylewee/code/inbred-backup-1431" ]; then
    echo "Archiving inbred-backup-1431..."
    mv "/home/kylewee/code/inbred-backup-1431" "$ARCHIVE_DIR/inbred-backup-1431-$DATE_STAMP"
    echo "✓ Archived inbred-backup-1431 → ARCHIVE/inbred-backup-1431-$DATE_STAMP"
fi

echo ""
echo "═══════════════════════════════════════════════════"
echo "  CONSOLIDATION COMPLETE!"
echo "═══════════════════════════════════════════════════"
echo ""
echo "Working directory:"
echo "  /home/kylewee/code/inbred  ← ONLY THIS ONE"
echo ""
echo "Archived to:"
echo "  /home/kylewee/code/ARCHIVE/"
echo ""
echo "Verifying clean state..."
find /home/kylewee/code -maxdepth 1 -type d ! -path "*/.*" ! -name "code" ! -name "ARCHIVE" ! -name "inbred"
echo ""
echo "✅ Done! Only 'inbred' and 'ARCHIVE' remain."
