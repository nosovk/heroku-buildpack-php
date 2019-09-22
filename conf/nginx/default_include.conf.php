location / {
  if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'access-control-expose-headers' 'Content-Disposition';
        add_header 'Access-Control-Allow-Methods' 'POST, PUT, GET, DELETE, OPTIONS';
        add_header 'Access-Control-Allow-Headers' '*';
     
        add_header 'Access-Control-Max-Age' 1728000;
        add_header 'Content-Type' 'text/plain; charset=utf-8';
        add_header 'Content-Length' 0;
        return 204;
  }
	index  index.php index.html index.htm;
}

# for people with app root as doc root, restrict access to a few things
location ~ ^/(composer\.(json|lock|phar)$|Procfile$|<?=getenv('COMPOSER_VENDOR_DIR')?>/|<?=getenv('COMPOSER_BIN_DIR')?>/) {
	deny all;
}
