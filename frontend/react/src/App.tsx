import { useEffect, useState } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import axios from "axios";

import Register from "./components/Register";
import Login from "./components/Login";
import PostList from "./components/PostList";
import SearchPosts from "./components/SearchPosts";

/**
 * ルートコンポーネント
 *
 * @returns JSX.Element
 */
function App() {
  const [isLoggedIn, setIsLggedIn] = useState<boolean>(false); // ログイン状態の有無を管理
  const [user, setUser] = useState<string | null>(null); // ログイン状態のユーザ名を管理

  useEffect(() => {
    checkAuth();
  }, []);

  /** ログイン認証チェック（トークン有無で画面遷移を制御） */
  const checkAuth = async () => {
    // ローカルストレージからトークンを取得
    const token = localStorage.getItem("token");
    if (!token) {
      setIsLggedIn(false);
      return;
    }

    try {
      // トークンが有効である場合 → ログイン状態にする
      const res = await axios.get("http://localhost:8000/api/user", {
        headers: { Authorization: `Bearer ${token}` },
      });

      console.log("ステータスコード:", res.status);
      if (res.status === 200) {
        setIsLggedIn(true);
        setUser(res.data.name);
      }
    } catch (error) {
      console.log("APIエラー:", error);
      setIsLggedIn(false);
    }
  };

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={isLoggedIn ? <Navigate to="/posts" /> : <Login setIsLoggedIn={setIsLggedIn} setUser={setUser} />} />
        <Route path="/account" element={<Register />} />
        <Route path="/posts" element={isLoggedIn ? <PostList user={user} /> : <Navigate to="/" />} />
        <Route path="/search" element={isLoggedIn ? <SearchPosts user={user} /> : <Navigate to="/" />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
