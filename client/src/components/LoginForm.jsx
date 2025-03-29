import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./AutorizationStyle.css";

const LoginForm = () => {
  const [login, setLogin] = useState("");
  const [password, setPassword] = useState("");
  const [position, setPosition] = useState("");
  const [showPopup, setShowPopup] = useState(false);

  const navigate = useNavigate();

  const tryAutorize = async (e) => {
    e.preventDefault();
    try {
      const response = await fetch("http://localhost/Common_Chat/AutorizationPHP.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: 'include',
        body: JSON.stringify({ login, password }),
      });
  
      const text = await response.text();
      console.log("Полученный текст ответа:", text);
  
      let data;
      try {
        data = JSON.parse(text);
      } catch (jsonError) {
        console.error("Ошибка парсинга JSON:", jsonError, text);
        return;
      }
  
      console.log("Ответ от сервера:", data);
      if (data.success) {
        navigate("/chat");
      } else {
        setShowPopup(true);
      }
    } catch (error) {
      console.log("Ошибка:", error);
    }
  };
  

  const tryAddUser = async (e) => {
    e.preventDefault();
    try {
      const response = await fetch("http://localhost/Common_Chat/RegistrationPHP.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: 'include',
        body: JSON.stringify({ login, password, position }),
      });
      const data = await response.json();
      console.log("Ответ от сервера (регистрация):", data);
      if (data.success === true) {
        navigate("/chat");
      } else {
        console.log("Ошибка регистрации:", data.message);
      }
    } catch (error) {
      console.log("Ошибка:", error);
    }
  };

  return (
    <div className="FormTaker">
      {showPopup && (
        <div className="popup-overlay">
          <div className="popup">
            <p>Похоже, вы новенький, нужно узнать вашу роль.</p>
            <form onSubmit={tryAddUser}>
              <input
                type="text"
                id="position"
                name="position"
                required
                value={position}
                onChange={(e) => setPosition(e.target.value)}
              />
              <button type="submit">Войти</button>
            </form>
          </div>
        </div>
      )}
      <form onSubmit={tryAutorize}>
        <div className="input-container">
          <label htmlFor="login">Login</label>
          <input
            type="text"
            id="login"
            name="login"
            required
            value={login}
            onChange={(e) => setLogin(e.target.value)}
          />
        </div>
        <div className="input-container">
          <label htmlFor="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </div>
        <button type="submit">Login</button>
      </form>
    </div>
  );
};

export default LoginForm;
