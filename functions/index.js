const functions = require('firebase-functions');
const express = require('express');
const cors = require('cors');
const admin = require('firebase-admin');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');

admin.initializeApp();
const db = admin.firestore();

const app = express();
app.use(express.json());
app.use(cors({ origin: 'http://localhost:3000', credentials: true }));
app.options('*', (req, res) => res.sendStatus(200));

const SECRET = 'your_jwt_secret';

// Endpoint: /login
app.post('/login', async (req, res) => {
  try {
    const { login, password } = req.body;
    if (!login || !password)
      return res.status(400).json({ error: "Логин и пароль обязательны" });
      
    const usersRef = db.collection("users");
    const querySnapshot = await usersRef.where("username", "==", login).limit(1).get();
    if (querySnapshot.empty)
      return res.status(404).json({ error: "Пользователь не найден" });
      
    const userDoc = querySnapshot.docs[0];
    const user = userDoc.data();
    const valid = await bcrypt.compare(password, user.password);
    if (!valid)
      return res.status(401).json({ error: "Неверный пароль" });
      
    const token = jwt.sign({ id: userDoc.id, login: user.username, position: user.position }, SECRET, { expiresIn: '1h' });
    return res.json({ success: true, message: "Авторизация успешна", token });
  } catch (error) {
    return res.status(500).json({ error: error.message });
  }
});

// Endpoint: /register
app.post('/register', async (req, res) => {
  try {
    const { login, password, position } = req.body;
    if (!login || login.trim() === "")
      return res.status(400).json({ error: "Логин не указан" });
      
    const usersRef = db.collection("users");
    const querySnapshot = await usersRef.where("username", "==", login).limit(1).get();
    if (!querySnapshot.empty)
      return res.status(400).json({ error: "Пользователь уже существует" });
      
    const hashedPassword = await bcrypt.hash(password, 10);
    const newUser = {
      username: login,
      password: hashedPassword,
      position: position || "",
      createdAt: admin.firestore.FieldValue.serverTimestamp()
    };
    const userRef = await usersRef.add(newUser);
    const token = jwt.sign({ id: userRef.id, login, position }, SECRET, { expiresIn: '1h' });
    return res.json({ success: true, message: "Пользователь добавлен", redirect: "chat", token });
  } catch (err) {
    return res.status(500).json({ error: err.message });
  }
});

const authenticate = async (req, res, next) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith("Bearer "))
    return res.status(401).json({ error: "Не авторизован" });
  const token = authHeader.split(" ")[1];
  try {
    const decoded = jwt.verify(token, SECRET);
    req.user = decoded;
    next();
  } catch (err) {
    return res.status(401).json({ error: "Не авторизован" });
  }
};

// Endpoint: /get-messages
app.get('/get-messages', authenticate, async (req, res) => {
  try {
    const messagesRef = db.collection("messages");
    const snapshot = await messagesRef.orderBy("message_time", "desc").limit(50).get();
    let messages = [];
    snapshot.forEach(doc => {
      messages.push({ message_id: doc.id, ...doc.data() });
    });
    return res.json({ success: true, messages, currentUserId: req.user.id });
  } catch (error) {
    return res.status(500).json({ error: "Ошибка базы данных: " + error.message });
  }
});

// Endpoint: /send-message
app.post('/send-message', authenticate, async (req, res) => {
  try {
    const data = req.body;
    if (!data || !data.messageText || !data.messageTime)
      return res.status(400).json({ error: "Неверные данные" });
      
    const messageTime = new Date(data.messageTime);
    if (isNaN(messageTime.getTime()))
      return res.status(400).json({ error: "Неверный формат времени" });
      
    const msgData = {
      user_id: req.user.id,
      message_text: data.messageText,
      message_time: admin.firestore.Timestamp.fromDate(messageTime)
    };
    const docRef = await db.collection("messages").add(msgData);
    return res.json({ success: true, messageId: docRef.id });
  } catch (error) {
    return res.status(500).json({ error: "Ошибка базы данных: " + error.message });
  }
});

// Endpoint: /logout
app.post('/logout', async (req, res) => {
  return res.json({ success: true });
});

exports.api = functions.https.onRequest(app);
