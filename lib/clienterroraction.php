<?php

/**
 * Client error action.
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
 * Class for displaying HTTP client errors
 *
 * @category Action
 * @package  Laconica
 * @author   Zach Copley <zach@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class ClientErrorAction extends ErrorAction
{
    function __construct($message='Error', $code=400)
    {
        parent::__construct($message, $code);

        $this->status  = array(400 => 'Bad Request',
                               401 => 'Unauthorized',
                               402 => 'Payment Required',
                               403 => 'Forbidden',
                               404 => 'Not Found',
                               405 => 'Method Not Allowed',
                               406 => 'Not Acceptable',
                               407 => 'Proxy Authentication Required',
                               408 => 'Request Timeout',
                               409 => 'Conflict',
                               410 => 'Gone',
                               411 => 'Length Required',
                               412 => 'Precondition Failed',
                               413 => 'Request Entity Too Large',
                               414 => 'Request-URI Too Long',
                               415 => 'Unsupported Media Type',
                               416 => 'Requested Range Not Satisfiable',
                               417 => 'Expectation Failed');
        $this->default = 400;
    }

    // XXX: Should these error actions even be invokable via URI?

    function handle($args)
    {
        parent::handle($args);

        $this->code = $this->trimmed('code');

        if (!$this->code || $code < 400 || $code > 499) {
            $this->code = $this->default;
        }

        $this->message = $this->trimmed('message');

        if (!$this->message) {
            $this->message = "Client Error $this->code";
        }

        $this->showPage();
    }

    function title()
    {
        return $this->status[$this->code];
    }
}
