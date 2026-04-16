import { io } from "socket.io-client";

const URL = import.meta.env.VITE_NODE_SERVICE_URL || "http://localhost:3000";

export const socket = io(URL, {
    autoConnect: false
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
