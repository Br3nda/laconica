#!/bin/bash

# Laconica - a distributed open-source microblogging tool

# Copyright (C) 2008, 2009, Control Yourself, Inc.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# This program tries to stop the daemons for Laconica that were
# previously started by startdaemons.sh

SDIR=`dirname $0`
DIR=`php $SDIR/getpiddir.php`

for f in jabberhandler ombhandler publichandler smshandler pinghandler \
	 xmppconfirmhandler xmppdaemon twitterhandler facebookhandler \
	 twitterstatusfetcher; do

	FILES="$DIR/$f.*.pid"
	for ff in "$FILES" ; do

	 	PID=`cat $ff 2>/dev/null`
		if [ -n "$PID" ] ; then
		 	echo -n "Stopping $f ($PID)..."
			if kill -3 $PID 2>/dev/null ; then
				count=0
				while kill -0 $PID 2>/dev/null ;  do
					sleep 1
					count=$(($count + 1))
					if [ $count -gt 5 ]; then break; fi
				done
				if kill -9 $PID 2>/dev/null ; then
					echo "FORCIBLY TERMINATED"
				else
					echo "STOPPED CLEANLY"
				fi
			else
				echo "NOT FOUND"
			fi
		fi
		rm -f $ff
	done
done

