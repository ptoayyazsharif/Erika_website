import json, sys
from http.server import BaseHTTPRequestHandler, HTTPServer
OUT = sys.argv[1]
class H(BaseHTTPRequestHandler):
    def do_POST(self):
        n = int(self.headers.get("Content-Length", 0)); body = self.rfile.read(n).decode()
        with open(OUT, "a") as f: f.write(json.dumps({"path": self.path, "auth": self.headers.get("Authorization"), "body": json.loads(body)}) + "\n")
        fail = open(OUT + ".fail").read().strip() == "1" if __import__("os").path.exists(OUT + ".fail") else False
        self.send_response(500 if fail else 200); self.send_header("Content-Type", "application/json"); self.end_headers()
        self.wfile.write(b'{"error":"mock down"}' if fail else b'{"data":{"leadId":4242}}')
    def log_message(self, *a): pass
HTTPServer(("127.0.0.1", 8799), H).serve_forever()
