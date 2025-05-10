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
      const response = await fetch(`${process.env.REACT_APP_API_URL}/Common_Chat/AutorizationPHP.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: 'include',
        body: JSON.stringify({ login, password }),
      });

      const contentType = response.headers.get("content-type");
      let data;
      
      if (contentType && contentType.includes("application/json")) {
        data = await response.json();
      } else {
        const text = await response.text();
        throw new Error(`Ожидался JSON, получено: ${text}`);
      }

      console.log("Ответ от сервера:", data);
      
      if (data.success) {
        document.cookie = `PHPSESSID=${data.session_id || ''}; path=/; domain=.web-chat-tca4.vercel.app; secure; samesite=none`;
        navigate("/chat");
      } else {
        setShowPopup(true);
      }
    } catch (error) {
      console.error("Ошибка авторизации:", error);
      setShowPopup(true);
    }
  };

  const tryAddUser = async (e) => {
    e.preventDefault();
    try {
      const response = await fetch(`${process.env.REACT_APP_API_URL}/Common_Chat/RegistrationPHP.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: 'include',
        body: JSON.stringify({ login, password, position }),
      });
      
      const data = await response.json();
      console.log("Ответ от сервера (регистрация):", data);
      
      if (data.success) {
        document.cookie = `PHPSESSID=${data.session_id || ''}; path=/; domain=.web-chat-tca4.vercel.app; secure; samesite=none`;
        navigate("/chat");
      }
    } catch (error) {
      console.error("Ошибка регистрации:", error);
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
                placeholder="Ваша должность"
              />
              <button type="submit">Зарегистрироваться</button>
            </form>
          </div>
        </div>
      )}
      <form onSubmit={tryAutorize}>
        <div className="input-container">
          <label htmlFor="login">Логин</label>
          <input
            type="text"
            id="login"
            name="login"
            required
            value={login}
            onChange={(e) => setLogin(e.target.value)}
            placeholder="Введите логин"
          />
        </div>
        <div className="input-container">
          <label htmlFor="password">Пароль</label>
          <input
            type="password"
            id="password"
            name="password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Введите пароль"
          />
        </div>
        <button type="submit">Войти</button>
      </form>
    </div>
  );
};

export default LoginForm;