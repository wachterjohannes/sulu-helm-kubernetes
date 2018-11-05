#!/usr/bin/env bash

varnishd -a 0.0.0.0:80 -T localhost:6082 -f /etc/varnish/default.vcl -p "vcc_allow_inline_c=on"
varnishlog
