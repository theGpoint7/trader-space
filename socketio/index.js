const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');

const PORT = process.env.PORT || 4000;
const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors: {
        origin: "*", // Allow all origins
        methods: ["GET", "POST"]
    }
});

// Enable CORS for the Express app
app.use(cors());

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

// Emit BTC price updates every 5 seconds to all connected clients
setInterval(() => {
    const btcPrice = (Math.random() * 10000 + 50000).toFixed(2); // Simulated BTC price
    console.log(`Emitting BTC price: ${btcPrice}`);
    io.emit('btcPrice', { price: btcPrice });
}, 5000);

server.listen(PORT, () => {
    console.log(`WebSocket server started on port ${PORT}`);
});