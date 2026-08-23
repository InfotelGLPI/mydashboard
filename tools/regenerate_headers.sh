#!/usr/bin/env bash

#
# -------------------------------------------------------------------------
# mydashboard plugin for GLPI
# Copyright (C) 2016-2026 by the mydashboard Development Team.
#
# https://github.com/InfotelGLPI/mydashboard
# -------------------------------------------------------------------------
#
# LICENSE
#
# This file is part of mydashboard.
#
# mydashboard is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# mydashboard is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
# --------------------------------------------------------------------------
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
HEADER_FILE="$SCRIPT_DIR/HEADER"

if [[ ! -f "$HEADER_FILE" ]]; then
    echo "Error: header file not found: $HEADER_FILE"
    exit 1
fi

# Single raw header file for every language (PHP + Twig), mirroring glpi/tools.
php "$SCRIPT_DIR/regenerate_headers.php" "$PLUGIN_DIR" "$HEADER_FILE" "$@"
