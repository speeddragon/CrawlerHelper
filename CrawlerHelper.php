<?php

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
			$_cookie = $path;

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
			return $_cookie;
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
			
			if ($referer) {
				curl_setopt($ch, CURLOPT_REFERER, $referer);
			}
		

            if ($this->getCookie()) {
			    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookie());
			    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookie());
            }
				      		
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());

			// If response come with GZIP will convert to normal text
			curl_setopt($ch, CURLOPT_ENCODING, '');

			$result = curl_exec ($ch);
		
			curl_close ($ch); 
		
			return $result;
		}

		/**
		 * Download file
		 *
		 * @param $url URL path of the file
		 * @param $filename Filename 
		 * @param $override Flag to override filename
		 */
		public function downloadFile($url, $filename, $override) {
			$ch = curl_init ($url);

	        curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

        	if($this->getCookie()) {
				curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookie());
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookie());	    	
			}
			
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
	}

?>