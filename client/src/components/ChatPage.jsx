import React, { useState } from "react";
import "./ChatPageStyle.css";

const ChatPage = () => {
  const [message, setMessage] = useState("");

  const handleSend = (e) => {
    e.preventDefault();
    console.log("Отправка сообщения:", message);
    setMessage("");
  };

  return (
    <div className="chat-container">
      <div className="chat-header">
        <h2>Team Unicorns</h2>
        <span className="date">8/20/2020</span>
      </div>
      <div className="chat-messages">
        <div className="chat-message sent">
          <p>
            <strong>Test message</strong> 👋{" "}
            <span className="time">00:00 AM</span>
          </p>
        </div>
        <div className="chat-message sent">
          <p>
            <strong>Test message 2</strong>{" "}
            <span className="time">1:31 AM</span>
          </p>
        </div>
        <div className="chat-message received">
          <p>
            <strong>Ivan</strong> Dev <br />
            Test message??{" "}
            <span className="time">11:00 AM</span>
          </p>
        </div>
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
