#!/usr/bin/env php
<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, Control Yourself, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

# Abort if called from a web server
if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
    print "This script must be run from the command line\n";
    exit();
}

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));
define('LACONICA', true);

require_once(INSTALLDIR . '/lib/common.php');

common_log(LOG_INFO, 'Starting to render old notices.');

$start_at = ($argc > 1) ? $argv[1] : null;

$notice = new Notice();
if ($start_at) {
    $notice->whereAdd('id >= ' . $start_at);
}
$cnt = $notice->find();

while ($notice->fetch()) {
    common_log(LOG_INFO, 'Pre-rendering notice #' . $notice->id);
    $original = clone($notice);
    $notice->rendered = common_render_content($notice->content, $notice);
    $result = $notice->update($original);
    if (!$result) {
        common_log_db_error($notice, 'UPDATE', __FILE__);
    }
}
