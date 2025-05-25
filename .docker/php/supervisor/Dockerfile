FROM lara-app:local

USER root

RUN apk update && apk add --no-cache supervisor

COPY supervisord.conf /etc/supervisord.conf
COPY app.ini /etc/supervisor.d/app.ini
COPY --chmod=755 entrypoint.sh /usr/local/bin/entrypoint.sh

RUN unset HTTP_PROXY HTTPS_PROXY NO_PROXY

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
