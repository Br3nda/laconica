<?php
/**
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

if (!defined('LACONICA')) {
    exit(1);
}

class Daemon
{
    var $daemonize = true;

    function __construct($daemonize = true)
    {
        $this->daemonize = $daemonize;
    }

    function name()
    {
        return null;
    }

    function background()
    {
        $pid = pcntl_fork();
        if ($pid < 0) { // error
            common_log(LOG_ERR, "Could not fork.");
            return false;
        } else if ($pid > 0) { // parent
            common_log(LOG_INFO, "Successfully forked.");
            exit(0);
        } else { // child
            return true;
        }
    }

    function alreadyRunning()
    {
        $pidfilename = $this->pidFilename();

        if (!$pidfilename) {
            return false;
        }

        if (!file_exists($pidfilename)) {
            return false;
        }
        $contents = file_get_contents($pidfilename);
        if (posix_kill(trim($contents), 0)) {
            return true;
        } else {
            return false;
        }
    }

    function writePidFile()
    {
        $pidfilename = $this->pidFilename();

        if (!$pidfilename) {
            return false;
        }

        return file_put_contents($pidfilename, posix_getpid() . "\n");
    }

    function clearPidFile()
    {
        $pidfilename = $this->pidFilename();
        if (!$pidfilename) {
            return false;
        }
        return unlink($pidfilename);
    }

    function pidFilename()
    {
        $piddir = common_config('daemon', 'piddir');
        if (!$piddir) {
            return null;
        }
        $name = $this->name();
        if (!$name) {
            return null;
        }
        return $piddir . '/' . $name . '.pid';
    }

    function changeUser()
    {
        $username = common_config('daemon', 'user');

        if ($username) {
            $user_info = posix_getpwnam($username);
            if (!$user_info) {
                common_log(LOG_WARNING,
                           'Ignoring unknown user for daemon: ' . $username);
            } else {
                common_log(LOG_INFO, "Setting user to " . $username);
                posix_setuid($user_info['uid']);
            }
        }

        $groupname = common_config('daemon', 'group');

        if ($groupname) {
            $group_info = posix_getgrnam($groupname);
            if (!$group_info) {
                common_log(LOG_WARNING,
                           'Ignoring unknown group for daemon: ' . $groupname);
            } else {
                common_log(LOG_INFO, "Setting group to " . $groupname);
                posix_setgid($group_info['gid']);
            }
        }
    }

    function runOnce()
    {
        if ($this->alreadyRunning()) {
            common_log(LOG_INFO, $this->name() . ' already running. Exiting.');
            exit(0);
        }

        if ($this->daemonize) {
            common_log(LOG_INFO, 'Backgrounding daemon "'.$this->name().'"');
            $this->background();
        }

        $this->writePidFile();
        $this->changeUser();
        $this->run();
        $this->clearPidFile();
    }

    function run()
    {
        return true;
    }
}
