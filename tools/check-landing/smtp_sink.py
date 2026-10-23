import asyncio, json, sys, time
from aiosmtpd.controller import Controller
from email import message_from_bytes, policy
OUT = sys.argv[1]
class H:
    async def handle_DATA(self, server, session, envelope):
        m = message_from_bytes(envelope.content, policy=policy.default)
        body = m.get_body(preferencelist=('plain',))
        rec = {"to": envelope.rcpt_tos, "from": envelope.mail_from, "subject": str(m["subject"]), "reply_to": str(m.get("reply-to","")),
               "text": body.get_content() if body else "", "from_hdr": str(m["from"]),
               "html": (lambda h: h.get_content() if h else "")(m.get_body(preferencelist=('html',)))}
        with open(OUT, "a") as f: f.write(json.dumps(rec) + "\n")
        return "250 OK"
c = Controller(H(), hostname="127.0.0.1", port=8025); c.start()
while True: time.sleep(3600)
