#!/bin/bash
# Test SQL Syntax Validation Script
# This script validates the SQL syntax without executing the statements

echo "╔════════════════════════════════════════════════════════════╗"
echo "║         SQL Syntax Validation Test                        ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to validate SQL syntax
validate_sql() {
    local file=$1
    local db_type=$2
    
    echo "Testing: $file ($db_type)"
    echo "----------------------------------------"
    
    # Check if file exists
    if [ ! -f "$file" ]; then
        echo -e "${RED}✗ File not found: $file${NC}"
        return 1
    fi
    
    # Count SQL statements
    local stmt_count=$(grep -c ";" "$file")
    echo "  Statements found: $stmt_count"
    
    # Check for common SQL syntax issues
    local issues=0
    
    # Check for unterminated statements
    if grep -q "[^;]$" "$file"; then
        echo -e "${YELLOW}  ⚠ Warning: File may have unterminated statements${NC}"
    fi
    
    # Check for balanced parentheses
    local open_parens=$(grep -o "(" "$file" | wc -l)
    local close_parens=$(grep -o ")" "$file" | wc -l)
    if [ "$open_parens" -ne "$close_parens" ]; then
        echo -e "${RED}  ✗ Error: Unbalanced parentheses (open: $open_parens, close: $close_parens)${NC}"
        issues=$((issues + 1))
    fi
    
    # Check for key SQL keywords
    if grep -qi "ALTER TABLE\|CREATE TABLE\|MODIFY COLUMN\|ADD COLUMN" "$file"; then
        echo -e "${GREEN}  ✓ Contains DDL statements${NC}"
    else
        echo -e "${YELLOW}  ⚠ Warning: No DDL statements found${NC}"
    fi
    
    # Check for IF NOT EXISTS clauses
    if grep -qi "IF NOT EXISTS" "$file"; then
        echo -e "${GREEN}  ✓ Uses IF NOT EXISTS (idempotent)${NC}"
    fi
    
    # Check for proper comments
    if grep -q "^--" "$file"; then
        echo -e "${GREEN}  ✓ Contains comments${NC}"
    fi
    
    if [ $issues -eq 0 ]; then
        echo -e "${GREEN}  ✓ SQL syntax appears valid${NC}"
        return 0
    else
        echo -e "${RED}  ✗ SQL validation failed with $issues issue(s)${NC}"
        return 1
    fi
}

echo ""
echo "Step 1: Validating User Database SQL"
echo "════════════════════════════════════════════════════════════"
validate_sql "sql/dbs15253086.sql" "User Database"
user_db_result=$?

echo ""
echo ""
echo "Step 2: Validating Content Database Legacy SQL"
echo "════════════════════════════════════════════════════════════"
validate_sql "sql/dbs15161271.sql" "Content Database Legacy"
content_db_legacy_result=$?

echo ""
echo ""
echo "Step 3: Validating Content Database New SQL"
echo "════════════════════════════════════════════════════════════"
validate_sql "sql/dbs15251284.sql" "Content Database New"
content_db_new_result=$?

echo ""
echo ""
echo "════════════════════════════════════════════════════════════"
echo "Summary"
echo "════════════════════════════════════════════════════════════"

if [ $user_db_result -eq 0 ] && [ $content_db_legacy_result -eq 0 ] && [ $content_db_new_result -eq 0 ]; then
    echo -e "${GREEN}✓ All SQL files validated successfully${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Backup production databases"
    echo "  2. Run: php sql/apply_all_migrations_to_production.php"
    echo "  3. Verify with: php verify_db_schema.php"
    exit 0
else
    echo -e "${RED}✗ SQL validation failed${NC}"
    echo ""
    echo "Please fix the issues above before deploying."
    exit 1
fi
