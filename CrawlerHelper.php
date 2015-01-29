<?php
	require_once('HttpResponse.php');

	class CrawlerHelper {
        protected $_cookieValues;
        protected $_cookiePath;
		protected $_timeout = 30;

		protected $_proxyHost;
		protected $_proxyPort;

		protected $_user;
		protected $_pass;

		protected $_httpHeaders = array();

		# ======================= Normal Helpers =======================

		public static function getTimestamp() {
	        echo date("Y-m-d H:i:s", time()) . ' ';
	    }

        /**
         * Convert chars from Russian to UTF8
         *
         * In order to parse russian characters we need to convert the encoding.
         *
         * @param $html string HTML code
         * @return string
         */
	    public static function convertFromRussianToUtf8($html) {
	        return iconv('windows-1251', 'utf-8', $html);
	    }


		# ======================= cURL Helpers =======================

        /**
         * @return array
         */
        public function getCookieValues()
        {
            return $this->_cookieValues;
        }

        /**
         * @param array $cookieValues
         */
        public function setCookieValues($cookieValues)
        {
            $this->_cookieValues = $cookieValues;
        }

        public function addCookieValue($key, $value) {
            if (!isset($this->_cookieValues)) {
                $this->_cookieValues = array();
            }

            $this->_cookieValues[$key] = $value;
        }

        /**
         * Set Cookie Path
         *
         * @param $path string Cookie filename path
         * @throws Exception
         */
		public function setCookiePath($path) {
			$this->_cookiePath = $path;

			if (!file_exists($path)) {
				// Trying to create the cookie file
				$cookieHandle = fopen($path, 'w');
				
				if (!$cookieHandle) {
					throw new Exception("Can't create file: " . $path);
				} else {
					fclose($cookieHandle);
				}
			}
		}

		public function getCookiePath() {
			return $this->_cookiePath;
		}

		public function setTimeout($timeout) {
			$this->_timeout = $timeout;
		}

		public function getUser() {
			return $this->_user;
		}

		public function setUser($user) {
			$this->_user = $user;
		}

		public function getPass() {
			return $this->_pass;
		}

		public function setPass($pass) {
			$this->_pass = $pass;
		}

		public function getHttpHeaders() {
			return $this->_httpHeaders;
		}

		public function setHttpHeaders($httpHeaders) {
			$this->_httpHeaders = $httpHeaders;
		}

		/**
		 * HTTP Simple Request
		 *
		 * @param $url string URL
		 * @return string HTML Code
		 */
		public function httpSimpleRequest($url) {
			if (!extension_loaded('curl')) {
			    echo "You need to load/activate the curl extension.";
			}
			
			// create curl resource 
	        $ch = curl_init(); 

	        // set url 
	        curl_setopt($ch, CURLOPT_URL, $url); 

	        // Return the transfer as a string 
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

	        // Proxy
	        if (isset($this->_proxyPort) && isset($this->_proxyHost)) {
				curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
				curl_setopt($ch, CURLOPT_PROXY, $this->_proxyHost . ':' . $this->_proxyPort);
	        }

	        $httpResponse = new HttpResponse();
			$httpResponse->setHtml( curl_exec ($ch) );
			$httpResponse->setHttpCode( curl_getinfo($ch, CURLINFO_HTTP_CODE) );
		
			curl_close ($ch);
		
			return $httpResponse;
		}

        /**
         * HTTP Request
         *
         * @param $url string URL
         * @param bool|string $post string POST data (default is false)
         * @param bool|string $referer string Referer
         * @return HttpResponse HTML Code
         * @throws Exception
         */
		public function httpRequest($url, $post = false, $referer = false) {
			if (!extension_loaded('curl')) {
			    echo "You need to load/activate the curl extension.";
			}

			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url);
		
			// Proxy
			if (isset($this->_proxyPort) && isset($this->_proxyHost)) {
				curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
				curl_setopt($ch, CURLOPT_PROXY, $this->_proxyHost . ':' . $this->_proxyPort);
	        }

			if ($post)
			{
				curl_setopt($ch, CURLOPT_POST, 1); 
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
			}
			
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)");
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

			// Authentication on requests
			if ($this->getUser() && $this->getPass()) {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM );
				curl_setopt($ch, CURLOPT_USERPWD, $this->getUser() . ":" . $this->getPass());
			}

			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeaders());
			
			// Ignore SSL validation
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			if ($referer) {
				curl_setopt($ch, CURLOPT_REFERER, $referer);
			}

            $this->checkCookie($ch);
				      		
			if ($this->getTimeout() !== false) {
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());
			}

			// If response come with GZIP will convert to normal text
			curl_setopt($ch, CURLOPT_ENCODING, '');

            // Add cookie values
            if (is_array($this->_cookieValues)) {
                $cookieValuesString = "";

                foreach($this->_cookieValues as $cookieKey => $cookieValue) {
                    $cookieValuesString .= $cookieValuesString . $cookieKey . '=' . $cookieValue . '; ';
                }

                curl_setopt($ch, CURLOPT_COOKIE, $cookieValuesString);
            }


            $httpResponse = new HttpResponse();
			$httpResponse->setHtml( curl_exec ($ch) );
			$httpResponse->setHttpCode( curl_getinfo($ch, CURLINFO_HTTP_CODE) );
		
			curl_close ($ch);
		
			return $httpResponse;
		}

        /**
         * Download file
         *
         * @param $url string URL path of the file
         * @param $filename string Filename
         * @param $override bool|string Flag to override filename
         * @param $referer bool|string Referer
         * @return string
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

			// Authentication on requests
			if ($this->getUser() && $this->getPass()) {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM );
				curl_setopt($ch, CURLOPT_USERPWD, $this->getUser() . ":" . $this->getPass());
			}

			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeaders());

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
		 * @param string $url URL
		 * @return int HTTP Code
		 */
		public function checkHttpCode($url) {
	        $ch = curl_init($url);

	        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);

	        $this->checkCookie($ch);

	        curl_exec($ch);
	        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	        curl_close($ch);

	        return $httpCode;
	    }

        /**
         * Check and setup cookies
         * @param resource $ch
         * @return bool
         * @throws Exception
         */
	    private function checkCookie(&$ch) {
	    	if($this->getCookiePath()) {
        		if (!file_exists($this->getCookiePath())) {
				    throw new Exception('Cookie file missing: ' . $this->getCookiePath() );
				}

				if (!is_writable($this->getCookiePath())) {
					throw new Exception('Cookie file not writable: ' . $this->getCookiePath() );
				}

				curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookiePath());
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookiePath());
			}

			return true;
	    }

	    public function setProxy($host, $port) {
	    	$this->_proxyHost = $host;
	    	$this->_proxyPort = $port;
	    }
	}

?>