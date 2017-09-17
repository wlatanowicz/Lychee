FROM alpine:3.6

RUN apk update \
 && apk add \
    apache2 \
    apache2-utils \
    php7-apache2 \
    php7-session \
    php7-exif \
    php7-mbstring \
    php7-gd \
    php7-mysqli \
    php7-json \
    php7-zip

RUN mkdir /run/apache2

EXPOSE 80

COPY . /lychee

RUN echo "<?php" > /lychee/data/config.php \
 && echo "\$dbHost = 'mysql';" >> /lychee/data/config.php \
 && echo "\$dbUser = 'root';" >> /lychee/data/config.php \
 && echo "\$dbPassword = 'password';" >> /lychee/data/config.php \
 && echo "\$dbName = 'lychee';" >> /lychee/data/config.php \
 && echo "\$dbTablePrefix = '';" >> /lychee/data/config.php

RUN chmod a+rw /lychee/uploads

RUN rm -rf /var/www/localhost/htdocs \
 && ln -s /lychee /var/www/localhost/htdocs

CMD ["/usr/sbin/apachectl", "-D", "FOREGROUND"]
