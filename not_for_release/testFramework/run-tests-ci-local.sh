#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

if [ -z "${ZC_TEST_DB_BASE_NAME:-}" ]; then
    export ZC_TEST_DB_BASE_NAME="db"
fi

if [ -z "${ZC_TEST_DB_WORKERS:-}" ]; then
    export ZC_TEST_DB_WORKERS="2"
fi

export ZC_TEST_DB_INCLUDE_BASE="0"

bash "$ROOT_DIR/not_for_release/testFramework/run-tests-ci.sh" "$@"
