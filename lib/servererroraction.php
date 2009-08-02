<?php

/**
 * Server error action.
 *
 * PHP version 5
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Zach Copley <zach@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 *
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

require_once INSTALLDIR.'/lib/error.php';

/**
 * Class for displaying HTTP server errors
 *
 * Note: The older util.php class simply printed a string, but the spec
 * says that 500 errors should be treated similarly to 400 errors, and
 * it's easier to give an HTML response.  Maybe we can customize these
 * to display some funny animal cartoons.  If not, we can probably role
 * these classes up into a single class.
 *
 * See: http://tools.ietf.org/html/rfc2616#section-10
 *
 * @category Action
 * @package  Laconica
 * @author   Zach Copley <zach@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class ServerErrorAction extends ErrorAction
{
    function __construct($message='Error', $code=500)
    {
        parent::__construct($message, $code);

        $this->status  = array(500 => 'Internal Server Error',
                               501 => 'Not Implemented',
                               502 => 'Bad Gateway',
                               503 => 'Service Unavailable',
                               504 => 'Gateway Timeout',
                               505 => 'HTTP Version Not Supported');

        $this->default = 500;
    }

    // XXX: Should these error actions even be invokable via URI?

    function handle($args)
    {
        parent::handle($args);

        $this->code = $this->trimmed('code');

        if (!$this->code || $code < 500 || $code > 599) {
            $this->code = $this->default;
        }

        $this->message = $this->trimmed('message');

        if (!$this->message) {
            $this->message = "Server Error $this->code";
        }

        $this->showPage();
    }

    function title()
    {
        return $this->status[$this->code];
    }
}
