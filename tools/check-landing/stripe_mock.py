"""A stand-in for the few Stripe API calls lib/stripe.php makes, on 127.0.0.1:8798.

    python3 -I stripe_mock.py <log.jsonl>

  POST /v1/checkout/sessions        records the request, returns {id, url}; url is /pay/<id> here
  GET  /v1/checkout/sessions/<id>   the session as Stripe would return it
  GET  /v1/balance                  200 for the test key, 401 for any other
  GET  /pay/<id>                    "Stripe Checkout": a Pay link and a Cancel link
  GET  /pay/<id>/ok                 pays the session and sends the buyer to success_url
  GET  /pay/<id>/paid-only          pays it without sending the buyer back (the closed-tab case)

The test key is sk_test_qa123. Nothing here talks to the real Stripe.
"""
import json, secrets, sys
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import parse_qsl, urlparse

LOG = sys.argv[1]
KEY = "sk_test_qa123"
SESS = {}


def form(body):
    out = {}
    for k, v in parse_qsl(body, keep_blank_values=True):
        out[k] = v
    return out


class H(BaseHTTPRequestHandler):
    def send(self, code, obj=None, html=None, loc=None):
        self.send_response(code)
        if loc:
            self.send_header("Location", loc)
        self.send_header("Content-Type", "text/html" if html is not None else "application/json")
        self.end_headers()
        if html is not None:
            self.wfile.write(html.encode())
        elif obj is not None:
            self.wfile.write(json.dumps(obj).encode())

    def authed(self):
        if self.headers.get("Authorization") != "Bearer " + KEY:
            self.send(401, {"error": {"message": "Invalid API Key provided"}})
            return False
        return True

    def do_POST(self):
        n = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(n).decode()
        if not self.authed():
            return
        if self.path == "/v1/checkout/sessions":
            f = form(body)
            with open(LOG, "a") as fh:
                fh.write(json.dumps(f) + "\n")
            sid = "cs_test_" + secrets.token_hex(12)
            sub = f.get("mode") == "subscription"
            SESS[sid] = {
                "id": sid, "object": "checkout.session", "mode": f.get("mode"), "status": "open", "payment_status": "unpaid",
                "amount_total": int(f.get("line_items[0][price_data][unit_amount]", "0")), "currency": f.get("line_items[0][price_data][currency]"),
                "client_reference_id": f.get("client_reference_id"), "customer_email": f.get("customer_email"),
                "metadata": {"page": f.get("metadata[page]"), "lead": f.get("metadata[lead]")},
                "success_url": f.get("success_url"), "cancel_url": f.get("cancel_url"), "livemode": False,
                "payment_intent": None, "subscription": None, "customer": None,
                "url": "http://127.0.0.1:8798/pay/" + sid, "_sub": sub,
            }
            return self.send(200, {k: v for k, v in SESS[sid].items() if not k.startswith("_")})
        self.send(404, {"error": {"message": "no such route"}})

    def pay(self, sid):
        s = SESS[sid]
        s["status"], s["payment_status"] = "complete", "paid"
        s["customer"] = "cus_" + secrets.token_hex(6)
        if s["_sub"]:
            s["subscription"] = "sub_" + secrets.token_hex(8)
        else:
            s["payment_intent"] = "pi_" + secrets.token_hex(8)

    def do_GET(self):
        p = urlparse(self.path).path
        parts = p.strip("/").split("/")
        if p == "/v1/balance":
            if self.authed():
                self.send(200, {"object": "balance", "available": []})
            return
        if p.startswith("/v1/checkout/sessions/"):
            if not self.authed():
                return
            s = SESS.get(parts[-1])
            if not s:
                return self.send(404, {"error": {"message": "No such checkout.session"}})
            return self.send(200, {k: v for k, v in s.items() if not k.startswith("_")})
        if parts[0] == "pay" and len(parts) >= 2 and parts[1] in SESS:
            sid, s = parts[1], SESS[parts[1]]
            if len(parts) == 2:
                return self.send(200, html=f'<title>Stripe Checkout (QA)</title><h1>Pay ${s["amount_total"]/100:.2f}</h1>'
                                           f'<a id="pay" href="/pay/{sid}/ok">Pay</a> <a id="cancel" href="{s["cancel_url"]}">Back</a>')
            if parts[2] == "ok":
                self.pay(sid)
                return self.send(303, loc=s["success_url"].replace("{CHECKOUT_SESSION_ID}", sid))
            if parts[2] == "paid-only":
                self.pay(sid)
                return self.send(200, {k: v for k, v in s.items() if not k.startswith("_")})
        self.send(404, {"error": {"message": "no such route"}})

    def log_message(self, *a):
        pass


HTTPServer(("127.0.0.1", 8798), H).serve_forever()
