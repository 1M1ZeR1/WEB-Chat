import React, { useState, useEffect } from "react";
import "./ChatPageStyle.css";

const ChatPage = () => {
  const [message, setMessage] = useState("");
  const [messages, setMessages] = useState([]);
  const [currentUserId, setCurrentUserId] = useState(null);

  const fetchMessages = async () => {
    try {
      const response = await fetch("http://localhost/Common_Chat/GetMessagesPHP.php", {
        method: "GET",
        credentials: "include",
      });
      
      const data = await response.json();
      if (data.success) {
        setMessages(data.messages.reverse());
        setCurrentUserId(Number(data.currentUserId));
      }
    } catch (error) {
      console.error("Ошибка загрузки сообщений:", error);
    }
  };
  

  useEffect(() => {
    fetchMessages();
    const interval = setInterval(fetchMessages, 2000);
    return () => clearInterval(interval);
  }, []);

  const handleSend = async (e) => {
    e.preventDefault();
    
    const now = new Date();
    const messageTime = 
      `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ` +
      `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;

    const messageData = {
      messageText: message,
      messageTime: messageTime,
    };

    try {
      const response = await fetch("http://localhost/Common_Chat/ChatMessagesPHP.php", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(messageData),
      });
      
      const data = await response.json();
      
      if (!response.ok) throw new Error(data.error || "Ошибка сервера");
      if (data.success) {
        setMessage("");
        fetchMessages();
      }
    } catch (error) {
      console.error("Ошибка:", error.message);
    }
  };

  const formatTime = (datetime) => {
    const date = new Date(datetime);
    return date.toLocaleTimeString([], { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: true
    });
  };

  return (
    <div className="chat-container">
      <div className="chat-header">
        <h2>Team Unicorns</h2>
        <span className="date">{new Date().toLocaleDateString()}</span>
      </div>
      
      <div className="chat-messages">
        {messages.map((msg) => (
          <div 
            key={msg.message_id}
            className={`chat-message ${msg.user_id === currentUserId ? 'sent' : 'received'}`}
          >
            <p>
              {msg.user_id !== currentUserId && (
                <strong>{msg.username}</strong>
              )}
              {msg.message_text}
              <span className="time">{formatTime(msg.message_time)}</span>
            </p>
          </div>
        ))}
      </div>

      <div className="message-input">
        <form onSubmit={handleSend}>
          <input
            type="text"
            placeholder="Type a message…"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
          />
          <button type="submit">Send</button>
        </form>
      </div>
    </div>
  );
};

export default ChatPage;