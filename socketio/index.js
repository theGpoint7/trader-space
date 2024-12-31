const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const bodyParser = require('body-parser');

const PORT = process.env.PORT || 4000;
const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors: {
        origin: "*", // Allow all origins
        methods: ["GET", "POST"]
    }
});

// Enable CORS and body parser for the Express app
app.use(cors());
app.use(bodyParser.json());

// Serve a basic HTML page at the root URL
app.get('/', (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Socket.io Server</title>
        </head>
        <body>
            <h1>Socket.io Server is running</h1>
            <p>WebSocket server started on port ${PORT}</p>
        </body>
        </html>
    `);
});

io.on('connection', (socket) => {
    console.log('A user connected:', socket.id);

    // Handle disconnection
    socket.on('disconnect', () => {
        console.log('A user disconnected');
    });
});

// Endpoint to receive BTC price updates
app.post('/update-btc-price', (req, res) => {
    const btcPrice = req.body.price;
    console.log(`Received BTC price update: ${btcPrice}`);
    io.emit('btcPrice', { price: btcPrice });
    res.sendStatus(200);
});

server.listen(PORT, () => {
    console.log(`WebSocket server started on port ${PORT}`);
});