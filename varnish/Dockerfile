FROM ubuntu:16.04

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update
RUN apt-get install -y varnish

ADD start.sh /start.sh

RUN chmod 0755 /start.sh

CMD ["/start.sh"]
