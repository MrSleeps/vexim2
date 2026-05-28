#!/bin/bash

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Error handling function
error_exit() {
    echo -e "${RED}ERROR: $1${NC}" >&2
    exit 1
}

# Function for fresh setup (install vexim database tables)
install_vexim_tables() {
    echo -e "${YELLOW}Installing Vexim database tables...${NC}"
    php artisan migrate --path=database/vexim-migrations --force
}

# Function for main setup
main_setup() {
    echo -e "${GREEN}Running main setup...${NC}"
    echo -e "${GREEN}Generating app key${NC}"
    php artisan key:generate
    echo -e "${GREEN}Creating web database tables${NC}"
    php artisan migrate
    echo -e "${GREEN}Seeding new tables${NC}"
    php artisan db:seed --class=RolePermissionSeeder
    php artisan db:seed --class=SettingsSeeder
    # Your main setup commands here
    # php artisan fin-mail:install --no-interaction
    # etc.
    
    echo -e "${GREEN}Main setup completed${NC}"
}

# Validation function (from previous answer)
validate_env() {
    # Check if .env exists
    if [ ! -f ".env" ]; then
        error_exit ".env file does not exist in current directory"
    fi

    # Source the .env file
    set -a
    source .env
    set +a

    # List of required variables
    required_vars=(
        "DB_CONNECTION"
        "DB_HOST"
        "DB_PORT"
        "DB_DATABASE"
        "DB_USERNAME"
        "DB_PASSWORD"
        "REDIS_CLIENT"
        "REDIS_HOST"
        "REDIS_PASSWORD"
        "REDIS_PORT"
        "MAIL_MAILER"
        "MAIL_HOST"
        "MAIL_PORT"
        "MAIL_USERNAME"
        "MAIL_PASSWORD"
        "MAIL_FROM_ADDRESS"
        "MAIL_FROM_NAME"
        "MAIL_FROM_SUPPORT_ADDRESS"
        "MAIL_FROM_SUPPORT_NAME"
        "VEXIM_UID"
        "VEXIM_GID"
    )

    # Track missing variables
    missing_vars=()

    # Check each variable
    for var in "${required_vars[@]}"; do
        value="${!var}"
        if [ -z "${value}" ]; then
            missing_vars+=("$var")
        fi
    done

    # If any variables are missing, show error and exit
    if [ ${#missing_vars[@]} -ne 0 ]; then
        echo -e "${RED}ERROR: The following required variables are missing or empty in .env:${NC}" >&2
        for var in "${missing_vars[@]}"; do
            echo -e "${RED}  - $var${NC}" >&2
        done
        exit 1
    fi

    # Set MAIL_MAILER to smtp if needed
    if [ "$MAIL_MAILER" != "smtp" ]; then
        echo -e "${GREEN}Setting MAIL_MAILER to smtp...${NC}"
        if grep -q "^MAIL_MAILER=" .env; then
            sed -i 's/^MAIL_MAILER=.*/MAIL_MAILER=smtp/' .env
        else
            echo "MAIL_MAILER=smtp" >> .env
        fi
        # Re-source to get the updated value
        source .env
    fi

    echo -e "${GREEN}Environment validation passed${NC}"
    return 0
}

# Ask the fresh setup question
ask_fresh_setup() {
    echo
    echo -e "${YELLOW}Is this a fresh setup? Do you want to install the main Vexim database tables?${NC}"
    while true; do
        read -p "Enter y or n: " -n 1 -r
        echo
        case $REPLY in
            [Yy]*)
                echo -e "${GREEN}Installing Vexim database tables...${NC}"
                install_vexim_tables
                break
                ;;
            [Nn]*)
                echo -e "${GREEN}Skipping Vexim database tables installation.${NC}"
                break
                ;;
            *)
                echo -e "${RED}Please answer y or n${NC}"
                ;;
        esac
    done
    echo
}

# Main execution flow
main() {
    # Step 1: Validate environment
    validate_env
    
    # Step 2: Composer Install
    composer install
 
    # Step 3: Ask about fresh setup / Vexim tables
    ask_fresh_setup
    
    # Step 4: Run main setup
    main_setup
    
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Setup completed successfully!${NC}"
    echo -e "${GREEN}========================================${NC}"
}

# Run the main function
main
