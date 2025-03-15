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
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ login, password })
      });

      const data = await response.json();
      console.log("Ответ от сервера:", data);

      if (data.exists === false) {
        setShowPopup(true);
      } else {
        navigate("/chat");
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
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ login, password, position })
      });

      const data = await response.json();
      console.log("Ответ от сервера (регистрация):", data);

      if(data.exists === true){
        navigate("/chat");
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
