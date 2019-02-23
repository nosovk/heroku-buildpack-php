http {
        limit_req_zone $http_cf_connecting_ip zone=phpapi:16m rate=5r/s;
        limit_req_status 429;

	include       mime.types;
	default_type  application/octet-stream;

	log_format  main  '$remote_addr - $remote_user [$time_local] "$request" '
	                  '$status $body_bytes_sent "$http_referer" '
	                  '"$http_user_agent" "$http_cf_connecting_ip"';

	#access_log  logs/access.log  main;

	sendfile        on;
	#tcp_nopush     on;

	#keepalive_timeout  0;
	keepalive_timeout  65;

	#gzip  on;

	server_tokens off;

	fastcgi_buffers 256 4k;

	real_ip_header CF-Connecting-IP;
	# Depending on your set-up you set more restrictive trusted proxies:
	set_real_ip_from 0.0.0.0/0;

	add_header X-debug-enabled "true" always;
	add_header X-debug-message $http_x_forwarded_for always;
	add_header X-debug-message-cloudflare $http_cf_connecting_ip always;

	# define an easy to reference name that can be used in fastgi_pass
	upstream heroku-fcgi {
		#server 127.0.0.1:4999 max_fails=3 fail_timeout=3s;
		server unix:/tmp/heroku.fcgi.<?=getenv('PORT')?:'8080'?>.sock max_fails=3 fail_timeout=3s;
		keepalive 16;
	}

	server {
		# define an easy to reference name that can be used in try_files
		location @heroku-fcgi {
			include fastcgi_params;

			fastcgi_split_path_info ^(.+\.php)(/.*)$;
			fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
			# try_files resets $fastcgi_path_info, see http://trac.nginx.org/nginx/ticket/321, so we use the if instead
			fastcgi_param PATH_INFO $fastcgi_path_info if_not_empty;
			# pass actual request host instead of localhost
			fastcgi_param SERVER_NAME $host;

			if (!-f $document_root$fastcgi_script_name) {
				# check if the script exists
				# otherwise, /foo.jpg/bar.php would get passed to FPM, which wouldn't run it as it's not in the list of allowed extensions, but this check is a good idea anyway, just in case
				return 404;
			}

			fastcgi_pass heroku-fcgi;
		}

		server_name localhost;
		listen <?=getenv('PORT')?:'8080'?>;
		# FIXME: breaks redirects with foreman
		port_in_redirect off;

		root "<?=getenv('DOCUMENT_ROOT')?:getenv('HEROKU_APP_DIR')?:getcwd()?>";

		error_log stderr;
		access_log /tmp/heroku.nginx_access.<?=getenv('PORT')?:'8080'?>.log;

		include "<?=getenv('HEROKU_PHP_NGINX_CONFIG_INCLUDE')?>";

		# restrict access to hidden files, just in case
		location ~ /\. {
			deny all;
		}

		# default handling of .php
		location ~ \.php {
                        limit_req       zone=phpapi  burst=3 nodelay;
			try_files @heroku-fcgi @heroku-fcgi;
		}
	}
}
