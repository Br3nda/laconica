#!/usr/bin/env php
<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2009, Control Yourself, Inc.
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

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));

$shortoptions = 'u::';
$longoptions = array('start-user-id::');

$helptext = <<<END_OF_TRIM_HELP
Batch script for trimming notice inboxes to a reasonable size.

    -u <id>
    --start-user-id=<id>   User ID to start after. Default is all.

END_OF_TRIM_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$id = null;

if (have_option('u')) {
    $id = get_option_value('u');
} else if (have_option('--start-user-id')) {
    $id = get_option_value('--start-user-id');
} else {
    $id = null;
}

$user = new User();

if (!empty($id)) {
    $user->whereAdd('id > ' . $id);
}

$cnt = $user->find();

while ($user->fetch()) {

    $inbox_entry = new Notice_inbox();
    $inbox_entry->user_id = $user->id;
    $inbox_entry->orderBy('created DESC');
    $inbox_entry->limit(1000, 1);

    $id = null;

    if ($inbox_entry->find(true)) {
        $id = $inbox_entry->notice_id;
    }

    $inbox_entry->free();
    unset($inbox_entry);

    if (is_null($id)) {
        continue;
    }

    $start = microtime(true);

    $old_inbox = new Notice_inbox();
    $cnt = $old_inbox->query('DELETE from notice_inbox WHERE user_id = ' . $user->id . ' AND notice_id < ' . $id);
    $old_inbox->free();
    unset($old_inbox);

    print "Deleted $cnt notices for $user->nickname ($user->id).\n";

    $finish = microtime(true);

    $delay = 3.0 * ($finish - $start);

    print "Delaying $delay seconds...";

    // Wait to let slaves catch up

    usleep($delay * 1000000);

    print "DONE.\n";
}
