<?php
	
	/**
	 * Simple method to run an ICMP ping against a host.
	 * Created by http://stackoverflow.com/a/20467492/1127064
	 * $host = the host to connect to
	 * $timeout (optional) = Defaults to 1.
	 */
	function icmp_ping($host, $timeout = 1) {
		/* ICMP ping packet with a pre-calculated checksum */
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
		$socket  = socket_create(AF_INET, SOCK_RAW, 1);
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
		socket_connect($socket, $host, null);
		$ts = microtime(true);
		socket_send($socket, $package, strLen($package), 0);
		if (socket_read($socket, 255)) {
				$result = microtime(true) - $ts;
		} else  {
			$result = false;
		}
		socket_close($socket);
		return $result;
	}