"""Plain-HTTP front door to the live site, for browser tests only.

    python3 tools/live-mirror.py 8731
    # then point Playwright at http://127.0.0.1:8731/blog

Chromium's own certificate store doesn't hold this environment's egress-proxy CA,
so a headless browser can't open https://erikakpage.com directly. This forwards
each request over real HTTPS with full certificate verification against the proxy
CA bundle, and hands the browser the result over loopback HTTP. Nothing about the
outbound TLS is relaxed.

Two things it has to do for the live site to behave:
  * pass cookies both ways - the host runs an anti-bot holding page that sets a
    cookie and reloads, and without the cookie every request gets the holder;
  * rewrite absolute https://erikakpage.com URLs in HTML/CSS/XML to the mirror,
    or the browser leaves the mirror and hits the certificate problem again.

The holding page can still appear on any request (with status 200), so browser
tests must retry until the page title is the one they expect.
"""
import http.server, os, socketserver, ssl, sys, urllib.request

ORIGIN = os.environ.get("MIRROR_ORIGIN", "https://erikakpage.com")
CA = os.environ.get("CA_BUNDLE", "/root/.ccr/ca-bundle.crt")
CTX = ssl.create_default_context(cafile=CA if os.path.exists(CA) else None)
PROXY = os.environ.get("HTTPS_PROXY") or os.environ.get("https_proxy")
opener = urllib.request.build_opener(
    urllib.request.ProxyHandler({"https": PROXY} if PROXY else {}),
    urllib.request.HTTPSHandler(context=CTX),
)


class Handler(http.server.BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"
    base = ""

    def log_message(self, *a):
        pass

    def _send(self, body, status, headers):
        ctype = next((v for k, v in headers if k.lower() == "content-type"), "")
        if "html" in ctype or "css" in ctype or "xml" in ctype:
            body = body.replace(ORIGIN.encode(), self.base.encode())
        self.send_response(status)
        for k, v in headers:
            if k.lower() in ("content-type", "location", "cache-control", "set-cookie"):
                self.send_header(k, v)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(body)

    def _fetch(self, method, data=None):
        req = urllib.request.Request(ORIGIN + self.path, data=data, method=method)
        req.add_header("User-Agent", self.headers.get("User-Agent", "live-mirror"))
        for h in ("Content-Type", "Cookie"):
            if self.headers.get(h):
                req.add_header(h, self.headers[h])
        if self.headers.get("Referer"):
            req.add_header("Referer", self.headers["Referer"].replace(self.base, ORIGIN))
        for attempt in range(4):
            try:
                with opener.open(req, timeout=120) as r:
                    return r.read(), r.status, r.getheaders()
            except urllib.error.HTTPError as e:
                return e.read(), e.code, e.getheaders()
            except Exception as e:
                if attempt == 3:
                    return str(e).encode(), 502, [("Content-Type", "text/plain")]
        return b"", 502, []

    def do_GET(self):
        self._send(*self._fetch("GET"))

    def do_HEAD(self):
        self._send(*self._fetch("GET"))

    def do_POST(self):
        n = int(self.headers.get("Content-Length") or 0)
        self._send(*self._fetch("POST", self.rfile.read(n)))


class Server(socketserver.ThreadingMixIn, http.server.HTTPServer):
    daemon_threads = True
    allow_reuse_address = True


if __name__ == "__main__":
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8731
    Handler.base = f"http://127.0.0.1:{port}"
    print(f"mirroring {ORIGIN} at {Handler.base}", flush=True)
    Server(("127.0.0.1", port), Handler).serve_forever()
