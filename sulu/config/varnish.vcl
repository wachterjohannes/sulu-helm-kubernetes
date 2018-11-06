vcl 4.0;

C{
    #include <stdlib.h>
}C

acl invalidators {
  "10.0.0.0"/8; # TODO make this configurable
}

backend default {
    .host = "{{ include "sulu.fullname" . }}";
    .port = "{{ .Values.sulu.service.port }}";
}

sub vcl_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }

        return (purge);
    }

    if (req.method == "BAN") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }

        if (req.http.x-cache-tags) {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
                + " && obj.http.x-cache-tags ~ " + req.http.x-cache-tags
            );
        } else {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
            );
        }

        return (synth(200, "Banned"));
    }
}

sub vcl_backend_response {
    # Set ban-lurker friendly custom headers
    set beresp.http.x-url = bereq.url;
    set beresp.http.x-host = bereq.http.host;

    // Check for ESI acknowledgement and remove Surrogate-Control header
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    if (beresp.http.X-Reverse-Proxy-TTL) {
        /*
         * Note that there is a ``beresp.ttl`` field in VCL but unfortunately
         * it can only be set to absolute values and not dynamically. Thus we
         * have to resort to an inline C code fragment.
         *
         * As of Varnish 4.0, inline C is disabled by default. To use this
         * feature, you need to add `-p vcc_allow_inline_c=on` to your Varnish
         * startup command.
         */
        C{
            const char *ttl;
            const struct gethdr_s hdr = { HDR_BERESP, "\024X-Reverse-Proxy-TTL:" };
            ttl = VRT_GetHdr(ctx, &hdr);
            VRT_l_beresp_ttl(ctx, atoi(ttl));
        }C

        unset beresp.http.X-Reverse-Proxy-TTL;
    }
}

sub vcl_deliver {
    if (!resp.http.x-cache-debug) {
        unset resp.http.x-url;
        unset resp.http.x-host;
    }

    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }
}
