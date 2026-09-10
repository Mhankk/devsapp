#!/usr/bin/env bash
# +------------------------------------------------------------------+
# |  devsapp — build.sh                                               |
# |  Build & packaging script:                                        |
# |  1. Zip folder src/ menjadi app.zip                               |
# |  2. Generate checksums.txt (SHA256)                               |
# |  3. Tampilkan summary hasil build                                  |
# |                                                                    |
# |  Usage: bash build.sh [version]                                   |
# |  Example: bash build.sh v1.0.0                                    |
# +------------------------------------------------------------------+

set -e  # Exit immediately on error

# ---- Config ----
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$SCRIPT_DIR/src"
DIST_DIR="$SCRIPT_DIR/dist"
OUTPUT_ZIP="$DIST_DIR/app.zip"
OUTPUT_CHECKSUM="$DIST_DIR/checksums.txt"
VERSION="${1:-v1.0.0}"

# ---- Colors ----
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}  devsapp Build Script — ${VERSION}${NC}"
echo -e "${CYAN}========================================${NC}"

# ---- Validasi: pastikan folder src ada ----
if [ ! -d "$SRC_DIR" ]; then
    echo -e "${RED}[ERROR] Folder 'src/' tidak ditemukan di $SCRIPT_DIR${NC}"
    exit 1
fi

# ---- Buat folder dist kalau belum ada ----
mkdir -p "$DIST_DIR"

# ---- Hapus artefak build sebelumnya ----
echo -e "\n${YELLOW}[1/3] Cleaning previous build...${NC}"
rm -f "$OUTPUT_ZIP" "$OUTPUT_CHECKSUM"
echo "      Cleaned: $DIST_DIR"

# ---- Zip src/ ----
echo -e "\n${YELLOW}[2/3] Creating app.zip...${NC}"
cd "$SCRIPT_DIR"
zip -r "$OUTPUT_ZIP" src/ index.php \
    --exclude "*.git*" \
    --exclude "*.DS_Store*" \
    --exclude "*/node_modules/*" \
    --exclude "*/__pycache__/*" \
    --exclude "*.log"

echo "      Created: $OUTPUT_ZIP"
echo "      Size:    $(du -sh "$OUTPUT_ZIP" | cut -f1)"

# ---- Generate checksum ----
echo -e "\n${YELLOW}[3/3] Generating checksums.txt...${NC}"
cd "$DIST_DIR"
sha256sum app.zip > checksums.txt
echo "      SHA256: $(cat checksums.txt)"

# ---- Summary ----
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}  Build SUCCESS — ${VERSION}${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "  Output ZIP    : $OUTPUT_ZIP"
echo -e "  Checksums     : $OUTPUT_CHECKSUM"
echo -e ""
echo -e "${CYAN}  Next steps:${NC}"
echo -e "  1. git tag ${VERSION} && git push origin ${VERSION}"
echo -e "  2. Create GitHub Release, attach dist/app.zip + dist/checksums.txt"
echo -e ""
