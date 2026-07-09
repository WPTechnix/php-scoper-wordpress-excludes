# Local verification image: PHP + Composer, nothing else.
# Lets `bin/generate.sh`, the PHPUnit suite, and ad-hoc `composer`/`php`
# commands run reproducibly without requiring PHP/Composer on the host.
FROM composer:2

RUN apk add --no-cache bash git

WORKDIR /app
