<?php
	// Example for Checkers created by Josh (AKA gravypod) Katz (7/26/14 US)
	
	/*
	 * Function with the same name as the file.
	 */
	function starmade() { // Returns false if service online, a string if we want to send an email.
		$q = new StarMadeQuery(); // Code to check uptime of SM.
		try {
			$q->Connect("sm.elwyneternity.com");
			return false;
		} catch (StarMadeQueryException $e) { // Starmade is offline?
			global $date; // Constant provided for message
			return "$date downtime. Shits on fire!"; // Message will be sent
		}
	}
	class StarMadeQueryException extends Exception {
		// Exception thrown by StarMadeQuery class
	}
	class StarMadeQuery {
		/*
		* Class written by rhaamo
		* Website: http://sigpipe.me
		* GitHub: https://github.com/rhaamo/
		*/
		private $Socket;
		private $Players;
		private $Info;
		public function Connect($Ip, $Port = 4242, $Timeout = 3) {
			if(!is_int($Timeout) || $Timeout < 0)	{
				throw new InvalidArgumentException('Timeout must be an integer.');
			}
			$this->Socket = @FSockOpen('tcp://' . $Ip, (int)$Port, $ErrNo, $ErrStr, $Timeout);
			if($ErrNo || $this->Socket === false)	{
				throw new StarMadeQueryException('Could not create socket: ' . $ErrStr);
			}
			Stream_Set_Timeout($this->Socket, $Timeout);
			Stream_Set_Blocking($this->Socket, true);
			try	{
				$this->GetInfos();
			} catch(StarMadeQueryException $e) { // We catch this because we want to close the socket, not very elegant
				FClose($this->Socket);
				throw new StarMadeQueryException($e->getMessage());
			}
			FClose($this->Socket);
		}
		public function GetInfo() {
			return isset($this->Info) ? $this->Info : false;
		}
		private function GetInfos() {
			$Data = $this->GetSocketStuff();
			if(!$Data){
				throw new StarMadeQueryException("Failed to receive status.");
			}
			if (count($Data) < 4) {
				throw new StarMadeQueryException("$Data doesn't contain three elements.");
			}
			$Info = Array();
			$Info['Players'] = IntVal($Data['nbplayers']);
			$Info['MaxPlayers'] = IntVal($Data['maxplayers']);
			$this->Info = $Info;
		}
		private function GetSocketStuff( ) {
			// Send magic thing
			//$Command = hex2bin(MAGIC); // PHP >= 5.4.0
			$Magic = "000000092affff016f00000000";
			$magic_pack = "h1firstpos/N1nbplayers/h1secondpos/N1maxplayers";
			$Command = pack("H*", $Magic);
			$Length = strlen($Command);
			if( $Length !== FWrite( $this->Socket, $Command, $Length ) ) {
				throw new MinecraftQueryException( "Failed to write on socket." );
			}
			$waste = FRead($this->Socket, 72);
			if( $waste === false ) {
				throw new StarMadeQueryException( "Failed to read from socket." );
			}
			// Get Real Datas
			$data = FRead($this->Socket, 82);
			if( $data === false ) {
				throw new StarMadeQueryException( "Failed to read from socket." );
			}
			return unpack($magic_pack, $data);
		}
	}
?>