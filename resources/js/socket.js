import { io } from "socket.io-client";

const getSocketURL = () => {
    const envURL = import.meta.env.VITE_NODE_SERVICE_URL;
    if (envURL && envURL !== 'http://localhost:3000') return envURL;
    
    // Fallback cerdas: Jika user buka 127.0.0.1, gunakan 127.0.0.1 untuk socket
    const protocol = window.location.protocol;
    const hostname = window.location.hostname;
    return `${protocol}//${hostname}:3000`;
};

export const socket = io(getSocketURL(), {
    autoConnect: false,
    transports: ['websocket', 'polling'] // Force both for better compatibility
});

// Helper untuk join room berdasarkan user ID
export const joinUserRoom = (userId) => {
    if (socket.connected) {
        socket.emit("join_inbox", userId);
    } else {
        socket.connect();
        socket.on("connect", () => {
            socket.emit("join_inbox", userId);
        });
    }
};
