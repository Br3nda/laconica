#!/usr/bin/env php
<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, Controlez-Vous, Inc.
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
require_once(INSTALLDIR . '/lib/jabber.php');
require_once(INSTALLDIR . '/lib/daemon.php');

set_error_handler('common_error_handler');

# This is kind of clunky; we create a class to call the global functions
# in jabber.php, which create a new XMPP class. A more elegant (?) solution
# might be to use make this a subclass of XMPP.

class XMPPDaemon extends Daemon
{

    function XMPPDaemon($resource=null)
    {
        static $attrs = array('server', 'port', 'user', 'password', 'host');

        foreach ($attrs as $attr)
        {
            $this->$attr = common_config('xmpp', $attr);
        }

        if ($resource) {
            $this->resource = $resource;
        } else {
            $this->resource = common_config('xmpp', 'resource') . 'daemon';
        }

        $this->log(LOG_INFO, "INITIALIZE XMPPDaemon {$this->user}@{$this->server}/{$this->resource}");
    }

    function connect()
    {

        $connect_to = ($this->host) ? $this->host : $this->server;

        $this->log(LOG_INFO, "Connecting to $connect_to on port $this->port");

        $this->conn = jabber_connect($this->resource);

        if (!$this->conn) {
            return false;
        }

        $this->conn->setReconnectTimeout(600);

        jabber_send_presence("Send me a message to post a notice", 'available',
                             null, 'available', 100);
        return !$this->conn->isDisconnected();
    }

    function name()
    {
        return strtolower('xmppdaemon.'.$this->resource);
    }

    function run()
    {
        if ($this->connect()) {

            $this->conn->addEventHandler('message', 'handle_message', $this);
            $this->conn->addEventHandler('presence', 'handle_presence', $this);
            $this->conn->addEventHandler('reconnect', 'handle_reconnect', $this);

            $this->conn->process();
        }
    }

    function handle_reconnect(&$pl)
    {
        $this->conn->processUntil('session_start');
        $this->conn->presence('Send me a message to post a notice', 'available', null, 'available', 100);
    }

    function get_user($from)
    {
        $user = User::staticGet('jabber', jabber_normalize_jid($from));
        return $user;
    }

    function handle_message(&$pl)
    {
        if ($pl['type'] != 'chat') {
            return;
        }
        if (mb_strlen($pl['body']) == 0) {
            return;
        }

        $from = jabber_normalize_jid($pl['from']);

        # Forwarded from another daemon (probably a broadcaster) for
        # us to handle

        if ($this->is_self($from)) {
            $from = $this->get_ofrom($pl);
            if (is_null($from) || $this->is_self($from)) {
                return;
            }
        }

        $user = $this->get_user($from);

        if (!$user) {
            $this->from_site($from, 'Unknown user; go to ' .
                             common_local_url('imsettings') .
                             ' to add your address to your account');
            $this->log(LOG_WARNING, 'Message from unknown user ' . $from);
            return;
        }
        if ($this->handle_command($user, $pl['body'])) {
            return;
        } else if ($this->is_autoreply($pl['body'])) {
            $this->log(LOG_INFO, 'Ignoring auto reply from ' . $from);
            return;
        } else if ($this->is_otr($pl['body'])) {
            $this->log(LOG_INFO, 'Ignoring OTR from ' . $from);
            return;
        } else if ($this->is_direct($pl['body'])) {
            preg_match_all('/d[\ ]*([a-z0-9]{1,64})/', $pl['body'], $to);

            $to = preg_replace('/^d([\ ])*/', '', $to[0][0]);
            $body = preg_replace('/d[\ ]*('. $to .')[\ ]*/', '', $pl['body']);
            $this->add_direct($user, $body, $to, $from);
        } else {
            $this->add_notice($user, $pl);
        }

        $user->free();
        unset($user);
    }

    function is_self($from)
    {
        return preg_match('/^'.strtolower(jabber_daemon_address()).'/', strtolower($from));
    }

    function get_ofrom($pl)
    {
        $xml = $pl['xml'];
        $addresses = $xml->sub('addresses');
        if (!$addresses) {
            $this->log(LOG_WARNING, 'Forwarded message without addresses');
            return null;
        }
        $address = $addresses->sub('address');
        if (!$address) {
            $this->log(LOG_WARNING, 'Forwarded message without address');
            return null;
        }
        if (!array_key_exists('type', $address->attrs)) {
            $this->log(LOG_WARNING, 'No type for forwarded message');
            return null;
        }
        $type = $address->attrs['type'];
        if ($type != 'ofrom') {
            $this->log(LOG_WARNING, 'Type of forwarded message is not ofrom');
            return null;
        }
        if (!array_key_exists('jid', $address->attrs)) {
            $this->log(LOG_WARNING, 'No jid for forwarded message');
            return null;
        }
        $jid = $address->attrs['jid'];
        if (!$jid) {
            $this->log(LOG_WARNING, 'Could not get jid from address');
            return null;
        }
        $this->log(LOG_DEBUG, 'Got message forwarded from jid ' . $jid);
        return $jid;
    }

    function is_autoreply($txt)
    {
        if (preg_match('/[\[\(]?[Aa]uto[-\s]?[Rr]e(ply|sponse)[\]\)]/', $txt)) {
            return true;
        } else if (preg_match('/^System: Message wasn\'t delivered. Offline storage size was exceeded.$/', $txt)) {
            return true;
        } else {
            return false;
        }
    }

    function is_otr($txt)
    {
        if (preg_match('/^\?OTR/', $txt)) {
            return true;
        } else {
            return false;
        }
    }

    function is_direct($txt)
    {
        if (strtolower(substr($txt, 0, 2))=='d ') {
            return true;
        } else {
            return false;
        }
    }

    function from_site($address, $msg)
    {
        $text = '['.common_config('site', 'name') . '] ' . $msg;
        jabber_send_message($address, $text);
    }

    function handle_command($user, $body)
    {
        $inter = new CommandInterpreter();
        $cmd = $inter->handle_command($user, $body);
        if ($cmd) {
            $chan = new XMPPChannel($this->conn);
            $cmd->execute($chan);
            return true;
        } else {
            return false;
        }
    }

    function add_notice(&$user, &$pl)
    {
        $body = trim($pl['body']);
        $content_shortened = common_shorten_links($body);
        if (mb_strlen($content_shortened) > 140) {
          $from = jabber_normalize_jid($pl['from']);
          $this->from_site($from, "Message too long - maximum is 140 characters, you sent ".mb_strlen($content_shortened));
          return;
        }
        $notice = Notice::saveNew($user->id, $content_shortened, 'xmpp');
        if (is_string($notice)) {
            $this->log(LOG_ERR, $notice);
            return;
        }
        common_broadcast_notice($notice);
        $this->log(LOG_INFO,
                   'Added notice ' . $notice->id . ' from user ' . $user->nickname);
        $notice->free();
        unset($notice);
    }

    function handle_presence(&$pl)
    {
        $from = jabber_normalize_jid($pl['from']);
        switch ($pl['type']) {
         case 'subscribe':
            # We let anyone subscribe
            $this->subscribed($from);
            $this->log(LOG_INFO,
                       'Accepted subscription from ' . $from);
            break;
         case 'subscribed':
         case 'unsubscribed':
         case 'unsubscribe':
            $this->log(LOG_INFO,
                       'Ignoring  "' . $pl['type'] . '" from ' . $from);
            break;
         default:
            if (!$pl['type']) {
                $user = User::staticGet('jabber', $from);
                if (!$user) {
                    $this->log(LOG_WARNING, 'Presence from unknown user ' . $from);
                    return;
                }
                if ($user->updatefrompresence) {
                    $this->log(LOG_INFO, 'Updating ' . $user->nickname .
                               ' status from presence.');
                    $this->add_notice($user, $pl);
                }
                $user->free();
                unset($user);
            }
            break;
        }
    }

    function log($level, $msg)
    {
        common_log($level, 'XMPPDaemon('.$this->resource.'): '.$msg);
    }

    function subscribed($to)
    {
        jabber_special_presence('subscribed', $to);
    }
}

// Abort immediately if xmpp is not enabled, otherwise the daemon chews up
// lots of CPU trying to connect to unconfigured servers
if (common_config('xmpp','enabled')==false) {
    print "Aborting daemon - xmpp is disabled\n";
    exit();
}

ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);
mb_internal_encoding('UTF-8');

$resource = ($argc > 1) ? $argv[1] : (common_config('xmpp','resource') . '-listen');

$daemon = new XMPPDaemon($resource);

$daemon->runOnce();
