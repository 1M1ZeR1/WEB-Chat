import React, { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import "./ChatPageStyle.css";

const ChatPage = () => {
  const [message, setMessage] = useState("");
  const [messages, setMessages] = useState([]);
  const [currentUserId, setCurrentUserId] = useState(null);
  const messagesEndRef = useRef(null);
  const navigate = useNavigate();

  const fetchMessages = async () => {
    try {
      const response = await fetch(`${process.env.REACT_APP_API_URL}/Common_Chat/GetMessagesPHP.php`, {
        method: "GET",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
      });

      if (response.status === 401) {
        navigate("/");
        return;
      }

      const data = await response.json();
      if (data.success) {
        setMessages(data.messages.reverse());
        setCurrentUserId(Number(data.currentUserId));
      } else if (data.error === "Unauthorized") {
        navigate("/");
      }
    } catch (error) {
      console.error("Ошибка загрузки сообщений:", error);
      if (error.message.includes("401")) {
        navigate("/");
      }
    }
  };

  useEffect(() => {
    const interval = setInterval(fetchMessages, 3000);
    return () => clearInterval(interval);
  },[]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const handleSend = async (e) => {
    e.preventDefault();
    if (!message.trim()) return;

    try {
      const response = await fetch(`${process.env.REACT_APP_API_URL}/Common_Chat/ChatMessagesPHP.php`, {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          messageText: message,
          messageTime: new Date().toISOString(),
        }),
      });

      const data = await response.json();
      if (!data.success) throw new Error(data.error || "Ошибка отправки");
      
      setMessage("");
      await fetchMessages();
    } catch (error) {
      console.error("Ошибка отправки:", error);
      if (error.message.includes("Unauthorized")) {
        navigate("/");
      }
    }
  };

  const handleLogout = async () => {
    try {
      const response = await fetch(`${process.env.REACT_APP_API_URL}/Common_Chat/LogoutPHP.php`, {
        method: "POST",
        credentials: "include",
      });

      const data = await response.json();
      if (data.success) {
        document.cookie = "PHPSESSID=; path=/; domain=.web-chat-tca4.vercel.app; expires=Thu, 01 Jan 1970 00:00:00 GMT";
        navigate("/");
      }
    } catch (error) {
      console.error("Ошибка выхода:", error);
    }
  };

  const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  return (
    <div className="chat-container">
      <div className="chat-header">
        <button className="logout-button" onClick={handleLogout}>
          Выйти
        </button>
        <h2>Team Unicorns</h2>
        <span className="date">{new Date().toLocaleDateString()}</span>
      </div>

      <div className="chat-messages">
        {messages.map((msg) => (
          <div
            key={msg.message_id}
            className={`chat-message ${
              msg.user_id === currentUserId ? "sent" : "received"
            }`}
          >
            <div className="message-content">
              {msg.user_id !== currentUserId && (
                <div className="message-info">
                  <span className="username">{msg.username}</span>
                  <span className="position">{msg.position}</span>
                </div>
              )}
              <div className="message-text">{msg.message_text}</div>
              <div className="message-time">
                {formatTime(msg.message_time)}
              </div>
            </div>
          </div>
        ))}
        <div ref={messagesEndRef} />
      </div>

      <form className="message-input" onSubmit={handleSend}>
        <input
          type="text"
          placeholder="Введите сообщение..."
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          required
        />
        <button type="submit">Отправить</button>
      </form>
    </div>
  );
};

export default ChatPage;