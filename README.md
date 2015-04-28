# Crawler Helper
--------------

This is a small PHP file with useful functions to use in a crawler.

# Example

> $crawlerHelper = new CrawlerHelper();
>
> $post = "username=speeddragon&password=********";
> $httpResponse = $crawlerHelper->httpRequest('https://www.google.com', $post, 'http://www.github.com/');
> 
> echo $httpResponse->getHtml();
