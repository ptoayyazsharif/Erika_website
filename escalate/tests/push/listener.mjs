/*
 * A stand-in push service.
 *
 * Records what the app actually puts on the wire and answers as told: the path
 * decides the status, so one listener plays healthy, gone and broken at once —
 * which is what the pruning rules are about. Nothing here understands push; it
 * is a socket that writes down what arrived.
 *
 * Started and stopped by real-send.sh. Not run on its own.
 */
import http from 'http';
import fs from 'fs';

const PORT = Number(process.argv[2] ?? 9411);
const OUT = process.argv[3];

const requests = [];

http.createServer((req, res) => {
  const chunks = [];
  req.on('data', c => chunks.push(c));
  req.on('end', () => {
    const body = Buffer.concat(chunks);
    const status = req.url.includes('/gone') ? 410 : req.url.includes('/broken') ? 500 : 201;

    requests.push({
      url: req.url,
      method: req.method,
      headers: req.headers,
      bodyLength: body.length,
      bodyBase64: body.toString('base64'),
      answered: status,
    });

    fs.writeFileSync(OUT, JSON.stringify(requests, null, 2));
    res.writeHead(status);
    res.end();
  });
}).listen(PORT, '127.0.0.1', () => console.log(`listening on ${PORT}`));
