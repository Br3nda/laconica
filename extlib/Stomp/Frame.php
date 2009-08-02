<?php
/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Stomp Frames are messages that are sent and received on a StompConnection.
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @version $Revision: 36 $
 */
class Stomp_Frame
{
    public $command;
    public $headers = array();
    public $body;
    
    /**
     * Constructor
     *
     * @param string $command
     * @param array $headers
     * @param string $body
     */
    public function __construct ($command = null, $headers = null, $body = null)
    {
        $this->_init($command, $headers, $body);
    }
    
    protected function _init ($command = null, $headers = null, $body = null)
    {
        $this->command = $command;
        if ($headers != null) {
            $this->headers = $headers;
        }
        $this->body = $body;
        
        if ($this->command == 'ERROR') {
            require_once 'Stomp/Exception.php';
            throw new Stomp_Exception($this->headers['message'], 0, $this->body);
        }
    }
    
    /**
     * Convert frame to transportable string
     *
     * @return string
     */
    public function __toString()
    {
        $data = $this->command . "\n";
        
        foreach ($this->headers as $name => $value) {
            $data .= $name . ": " . $value . "\n";
        }
        
        $data .= "\n";
        $data .= $this->body;
        return $data .= "\x00\n";
    }
}
?>