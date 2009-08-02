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

if (!defined('LACONICA')) { exit(1); }

require_once INSTALLDIR.'/lib/command.php';

class CommandInterpreter
{
    function handle_command($user, $text)
    {
        # XXX: localise

        $text = preg_replace('/\s+/', ' ', trim($text));
        list($cmd, $arg) = explode(' ', $text, 2);

        # We try to support all the same commands as Twitter, see
        # http://getsatisfaction.com/twitter/topics/what_are_the_twitter_commands
        # There are a few compatibility commands from earlier versions of
        # Laconica

        switch(strtolower($cmd)) {
         case 'help':
            if ($arg) {
                return null;
            }
            return new HelpCommand($user);
         case 'on':
            if ($arg) {
                list($other, $extra) = explode(' ', $arg, 2);
                if ($extra) {
                    return null;
                } else {
                    return new OnCommand($user, $other);
                }
            } else {
                return new OnCommand($user);
            }
         case 'off':
            if ($arg) {
                list($other, $extra) = explode(' ', $arg, 2);
                if ($extra) {
                    return null;
                } else {
                    return new OffCommand($user, $other);
                }
            } else {
                return new OffCommand($user);
            }
         case 'stop':
         case 'quit':
            if ($arg) {
                return null;
            } else {
                return new OffCommand($user);
            }
         case 'follow':
         case 'sub':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else {
                return new SubCommand($user, $other);
            }
         case 'leave':
         case 'unsub':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else {
                return new UnsubCommand($user, $other);
            }
         case 'get':
         case 'last':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else {
                return new GetCommand($user, $other);
            }
         case 'd':
         case 'dm':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if (!$extra) {
                return null;
            } else {
                return new MessageCommand($user, $other, $extra);
            }
         case 'whois':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else {
                return new WhoisCommand($user, $other);
            }
         case 'fav':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else {
                return new FavCommand($user, $other);
            }
         case 'nudge':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else {
                return new NudgeCommand($user, $other);
            }
         case 'stats':
            if ($arg) {
                return null;
            }
            return new StatsCommand($user);
         case 'invite':
            if (!$arg) {
                return null;
            }
            list($other, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else {
                return new InviteCommand($user, $other);
            }
         case 'track':
            if (!$arg) {
                return null;
            }
            list($word, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else if ($word == 'off') {
                return new TrackOffCommand($user);
            } else {
                return new TrackCommand($user, $word);
            }
         case 'untrack':
            if (!$arg) {
                return null;
            }
            list($word, $extra) = explode(' ', $arg, 2);
            if ($extra) {
                return null;
            } else if ($word == 'all') {
                return new TrackOffCommand($user);
            } else {
                return new UntrackCommand($user, $word);
            }
         case 'tracks':
         case 'tracking':
            if ($arg) {
                return null;
            }
            return new TrackingCommand($user);
         default:
            return false;
        }
    }
}

