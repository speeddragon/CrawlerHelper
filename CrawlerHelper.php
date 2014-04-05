<?php
	require_once('HttpResponse.php');

	class CrawlerHelper {
		protected $_cookie;
		protected $_timeout = 30;

		# ======================= Normal Helpers =======================

		public static function getTimestamp() {
	        echo date("Y-m-d H:i:s", time()) . ' ';
	    }

	    /**
	     * Convert chars from Russian to UTF8
	     *
	     * In order to parse russian characters we need to convert the encoding.
	     * 
	     * @param $html HTML code
	     */
	    public static function convertFromRussianToUtf8($html) {
	        return iconv('windows-1251', 'utf-8', $html);
	    }


		# ======================= cURL Helpers =======================

		/**
		 * Set Cookie Path
		 *
		 * @param $path Cookie filename path
		 */
		public function setCookie($path) {
			$this->_cookie = $path;

			if (!file_exists($path)) {
				// Trying to create the cookie file
				$cookieHandle = fopen($path, 'w');
				
				if (!$cookieHandle) {
					throw new FileNotFound("Can't create file: " . $path);
				} else {
					fclose($cookieHandle);
				}
			}
		}

		public function getCookie() {
			return $this->_cookie;
		}

		public function setTimeout($timeout) {
			$this->_timeout = $timeout;
		}

		/**
		 * HTTP Simple Request
		 *
		 * @param $url URL
		 * @return string HTML Code
		 */
		public static function httpSimpleRequest($url) {
			if (!extension_loaded('curl')) {
			    echo "You need to load/activate the curl extension.";
			}
			
			// create curl resource 
	        $ch = curl_init(); 

	        // set url 
	        curl_setopt($ch, CURLOPT_URL, $url); 

	        // Return the transfer as a string 
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

	        // $output contains the output string 
	        $output = curl_exec($ch); 

	        // close curl resource to free up system resources 
	        curl_close($ch); 

	        return $output;
		}

		/**
		 * HTTP Request
		 *
		 * @param $url URL
		 * @param $post POST data (default is false)
		 * @param $referer Referer
		 * @return string HTML Code
		 */
		public function httpRequest($url, $post = false, $referer = false) {
			if (!extension_loaded('curl')) {
			    echo "You need to load/activate the curl extension.";
			}

			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url);
		
			if ($post)
			{
				curl_setopt($ch, CURLOPT_POST, 1); 
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
			}
			
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)");
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			// Ignore SSL validation
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			if ($referer) {
				curl_setopt($ch, CURLOPT_REFERER, $referer);
			}

            $this->checkCookie($ch);
				      		
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());

			// If response come with GZIP will convert to normal text
			curl_setopt($ch, CURLOPT_ENCODING, '');

			$httpResponse = new HttpResponse();
			$httpResponse->setHtml( curl_exec ($ch) );
			$httpResponse->setHttpCode( curl_getinfo($ch, CURLINFO_HTTP_CODE) );
		
			curl_close ($ch);
		
			return $httpResponse;
		}

		/**
		 * Download file
		 *
		 * @param $url URL path of the file
		 * @param $filename Filename 
		 * @param $override Flag to override filename
		 */
		public function downloadFile($url, $filename, $override = false, $referer = false) {
			if (!extension_loaded('curl')) {
			    echo "You need to load/activate the curl extension.";
			    die;
			}

			$ch = curl_init ($url);

			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)");

	        curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

			// Ignore SSL validation
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			if ($referer) {
				curl_setopt($ch, CURLOPT_REFERER, $referer);
			}

        	$this->checkCookie($ch);
			
			$data = curl_exec($ch);

			// Get information from the request
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

			curl_close ($ch);
			
			// Write download data to file
			if (file_exists($filename) && $override) {
				unlink($filename);
			}

			if (!file_exists($filename)) {
				$fileHandler = fopen($filename, 'w');
				fwrite($fileHandler, $data);
				fclose($fileHandler);
			}

			return $contentType;
		}

		public function getTimeout() {
			return $this->_timeout;
		}

		/**
		 * Get HTTP Code
		 *
		 * @param $url URL
		 * @return HTTP Code
		 */
		public function checkHttpCode($url) {
	        $ch = curl_init($url);

	        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);

	        $this->checkCookie($ch);

	        $response = curl_exec($ch);
	        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	        curl_close($ch);

	        return $httpCode;
	    }		

	    /**
	     * Check and setup cookies
	     * @param $ch 
	     */
	    private function checkCookie(&$ch) {
	    	if($this->getCookie()) {
        		if (!file_exists($this->getCookie())) {
				    echo 'Cookie file missing: ' . $this->getCookie() . "\n"; 
				    exit;
				}

				if (!is_writable($this->getCookie())) {
					echo 'Cookie file not writable: ' . $this->getCookie() . "\n";
				    exit;	
				}

				curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookie());
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookie());	    	
			}

			return true;
	    }
	}

?>