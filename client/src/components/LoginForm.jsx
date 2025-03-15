import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./AutorizationStyle.css";

const LoginForm = () => {
  const [login, setLogin] = useState("");
  const [password, setPassword] = useState("");

  const navigate = useNavigate();

  const handleSubmit = (e) => {
    //e.preventDefault();
    console.log("Login:", login, "Password:", password);
    navigate("/chat");
  };

  return (
    <div className="FormTaker">
      <form onSubmit={handleSubmit}>
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
