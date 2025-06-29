import { useEffect, useState } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import axios from "axios";

// 自作コンポーネント
import Register from "./Register"; // アカウント登録ページ
import Login from "./Login"; // ログイン情報入力ページ
import PostList from "./PostList"; // 投稿一覧ページ

// ルートコンポーネント
function App() {
  const [loading, setLoading] = useState<boolean>(true); // ローディング画面を管理
  const [isLoggedIn, setIsLggedIn] = useState<boolean>(false); // ログイン状態の有無を管理
  const [user, setUser] = useState<string | null>(null); // ログイン状態のユーザ名を管理

  useEffect(() => {
    // ローカルストレージからトークンを取得
    const token = localStorage.getItem("token");

    if (!token) {
      setLoading(false);
      return;
    }

    // トークンが有効である場合 → ログイン状態にする
    axios
      .get("http://localhost:8000/api/user", {
        headers: { Authorization: `Bearer ${token}` },
      })
      .then((res) => {
        console.log("ステータスコード:", res.status);
        if (res.status === 200) {
          setIsLggedIn(true);
          setUser(res.data.name);
        }
        setLoading(false);
      });
  }, []);

  if (loading) return <p>読み込み中...</p>;

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={isLoggedIn ? <Navigate to="/posts" /> : <Login setIsLoggedIn={setIsLggedIn} setUser={setUser} />} />
        <Route path="/account" element={<Register />} />
        <Route path="/posts" element={isLoggedIn ? <PostList user={user} /> : <Navigate to="/" />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
