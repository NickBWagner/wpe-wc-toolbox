#!/bin/bash
#####
# This script tests the wpe-wc-toolbox repo with a linter and style checker.
#####

# Exit if any command fails
set -euo pipefail

# Test
composer install
npm install
grunt
