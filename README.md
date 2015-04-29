# Crawler Helper
--------------

This is a small PHP file with useful functions to use in a crawler.

# Functionality 

* Submit GET and POST requests
* Ser user and password for HTTP Basic authentication
* Ignore SSL certificates
* Define HTTP Headers
* Define User Agent
* Setup proxy configuration
* Define timeout
* Set cookie path and values
* Get HTTP response code
* Download binary files

# Example

> $crawlerHelper = new CrawlerHelper();
>
> $post = "username=speeddragon&password=********";
> $httpResponse = $crawlerHelper->httpRequest('https://www.google.com', $post, 'http://www.github.com/');
> 
> echo $httpResponse->getHtml();
