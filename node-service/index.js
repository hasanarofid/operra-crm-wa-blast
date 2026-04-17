require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const Redis = require('ioredis');
const crypto = require('crypto');
const webpush = require('web-push');
const axios = require('axios');
const cors = require('cors');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

// Middleware
app.use(express.json());
app.use(cors());

// Redis Client
const redis = new Redis({
    host: process.env.REDIS_HOST || '127.0.0.1',
    port: process.env.REDIS_PORT || 6379,
    password: process.env.REDIS_PASSWORD || null,
});

const redisSub = new Redis({
    host: process.env.REDIS_HOST || '127.0.0.1',
    port: process.env.REDIS_PORT || 6379,
    password: process.env.REDIS_PASSWORD || null,
});

// Web Push Setup
if (process.env.VAPID_PUBLIC_KEY && process.env.VAPID_PRIVATE_KEY) {
    webpush.setVapidDetails(
        'mailto:' + process.env.VAPID_EMAIL,
        process.env.VAPID_PUBLIC_KEY,
        process.env.VAPID_PRIVATE_KEY
    );
}

// --- META WEBHOOK HANDLING (High Concurrency) ---

// Verify Webhook (GET)
app.get('/webhook', (req, res) => {
    const mode = req.query['hub.mode'];
    const token = req.query['hub.verify_token'];
    const challenge = req.query['hub.challenge'];

    if (mode && token) {
        if (mode === 'subscribe' && token === process.env.WHATSAPP_VERIFY_TOKEN) {
            console.log('WEBHOOK_VERIFIED');
            return res.status(200).send(challenge);
        }
    }
    res.sendStatus(403);
});

// Receive Webhook (POST)
app.post('/webhook', (req, res) => {
    // 1. Verify Signature (Security)
    const signature = req.headers['x-hub-signature-256'];
    if (signature && process.env.META_APP_SECRET) {
        const hash = crypto.createHmac('sha256', process.env.META_APP_SECRET)
            .update(JSON.stringify(req.body))
            .digest('hex');
        
        if (`sha256=${hash}` !== signature) {
            return res.sendStatus(401);
        }
    }

    // 2. Queue to Redis (Immediate Response to Meta)
    const payload = req.body;
    redis.lpush('whatsapp_webhooks', JSON.stringify(payload));
    
    // Respond quickly to Meta
    res.status(200).json({ status: 'queued' });
});

// --- SOCKET.IO HANDLING ---

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    socket.on('join_inbox', (userId) => {
        socket.join(`inbox_user_${userId}`);
        console.log(`Socket ${socket.id} joined inbox_user_${userId}`);
    });

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });
});

// --- REDIS PUB/SUB (Triggering Real-time & Push from Laravel) ---

redisSub.subscribe('crm-events', (err, count) => {
    if (err) console.error('Failed to subscribe:', err.message);
    else console.log(`Subscribed to ${count} channels.`);
});

redisSub.on('message', (channel, message) => {
    if (channel === 'crm-events') {
        const event = JSON.parse(message);
        console.log('Received event from Laravel:', event.type);

        // 1. Emit to Socket.io (Real-time update)
        if (event.receiver_id && event.type) {
            io.to(`inbox_user_${event.receiver_id}`).emit(event.type, event.data);
            console.log(`Emitted ${event.type} to user ${event.receiver_id}`);
            
            // Juga kirim ke room global jika perlu (misal: untuk Admin Dashboard)
            io.to('global_admin').emit(event.type + '_global', event.data);
        }

        // 2. Send Push Notification (Web Push)
        if (event.push_subscription && event.notification) {
            const pushPayload = JSON.stringify({
                title: event.notification.title || 'Pesan Baru',
                body: event.notification.body || 'Anda mendapatkan pesan baru.',
                icon: '/icon.png',
                data: event.notification.data || {}
            });

            webpush.sendNotification(event.push_subscription, pushPayload)
                .catch(err => console.error('Push error:', err));
        }
    }
});

// --- SERVER START ---
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Node Service running on port ${PORT}`);
});
