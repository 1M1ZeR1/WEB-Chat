import React, { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import "./ChatPageStyle.css";

const ChatPage = () => {
  const [message, setMessage] = useState("");
  const [editedMessage_id, setEditedMessage_id] = useState(null);
  const [messages, setMessages] = useState([]);
  const [currentUserId, setCurrentUserId] = useState(null);
  const messagesEndRef = useRef(null);
  const navigate = useNavigate();
  const initialScrollDone = useRef(false);
  const [inEditMode, setInEditMode] = useState(false);


  const fetchMessages = async () => {
    try {
      const response = await fetch(
        `${process.env.REACT_APP_API_URL}/Common_Chat/GetMessagesPHP.php`,
        {
          method: "GET",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
        }
      );
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
  }, []);

  useEffect(() => {
    if (!initialScrollDone.current && messages.length > 0) {
      messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
      initialScrollDone.current = true;
    }
  }, [messages]);

  const handleSend = async (e) => {
    e.preventDefault();
    if (!message.trim()) return;
    try {
        const response = await fetch(
        `${process.env.REACT_APP_API_URL}/Common_Chat/ChatMessagesPHP.php`,
        {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            messageText: message,
            messageTime: new Date().toISOString(),
          }),
        }
        );
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

  const editSend = async (e)=>{
    e.preventDefault();
    setInEditMode(false);
    if (!message.trim()) return;
    try {
        const response = await fetch(
        `${process.env.REACT_APP_API_URL}/Common_Chat/EditMessagePHP.php`,
        {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            message_text: message,
            message_id: editedMessage_id
          }),
        }
        );
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
  }

  const handleLogout = async () => {
    try {
      const response = await fetch(
        `${process.env.REACT_APP_API_URL}/Common_Chat/LogoutPHP.php`,
        {
          method: "POST",
          credentials: "include",
        }
      );
      const data = await response.json();
      if (data.success) {
        document.cookie =
          "PHPSESSID=; path=/; domain=.web-chat-tca4.vercel.app; expires=Thu, 01 Jan 1970 00:00:00 GMT";
        navigate("/");
      }
    } catch (error) {
      console.error("Ошибка выхода:", error);
    }
  };

  const formatDateTime = (datetime) => {
    const date = new Date(datetime);
    const now = new Date();
    const isToday =
      date.getFullYear() === now.getFullYear() &&
      date.getMonth() === now.getMonth() &&
      date.getDate() === now.getDate();
    const time = date.toLocaleTimeString("ru-RU", {
      hour: "2-digit",
      minute: "2-digit",
    });
    if (isToday) {
      return time;
    } else {
      const dayMonth = date.toLocaleDateString("ru-RU", {
        day: "numeric",
        month: "long",
      });
      return `${dayMonth}, ${time}`;
    }
  };

  const handleDelete = async (message_id)=>{
    try {
      const response = await fetch(
        `${process.env.REACT_APP_API_URL}/Common_Chat/DeleteMessagePHP.php`,
        {
          method: "POST",
          credentials: "include",
           headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            message_id
          }),
        }
      );
      const data = await response.json();
      if (data.success) {
        await fetchMessages();
      }
    } catch (error) {
      console.error("Ошибка выхода:", error);
    }
  }

  const handleEdit = (message_text,message_id)=>{
    if(inEditMode){
      setInEditMode(false);
      setMessage("");
      return;
    }

    setEditedMessage_id(message_id);
    setMessage(message_text);
    setInEditMode(true);
  }

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
                  <span className="name-position">
                    {msg.username} — {msg.position}
                  </span>
                </div>
              )}
              <div className="message-text">{msg.message_text}</div>
              <div className="message-meta">
                {formatDateTime(msg.message_time)}
              </div>
              {msg.user_id === currentUserId && (
          <div className="message-actions">
            <button onClick={() => handleEdit(msg.message_text,msg.message_id)}>✏️</button>
            <button onClick={() => handleDelete(msg.message_id)}>🗑️</button>
          </div>
        )}
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
        <button type="submit" onClick={(e)=>{

          if(!inEditMode){
            handleSend(e);
          }
          else{
            editSend(e);
          }
        }
        }>Отправить</button>
      </form>
    </div>
  );
};

export default ChatPage;
