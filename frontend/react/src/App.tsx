import { useEffect, useState } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import axios from "axios";

import Register from "./components/Register";
import Login from "./components/Login";
import PostList from "./components/PostList";
import SearchPosts from "./components/SearchPosts";
import NotFound from "./components/NotFound";

/**
 * ルートコンポーネント
 *
 * @returns JSX.Element
 */
function App() {
  const [isLoggedIn, setIsLggedIn] = useState<boolean | null>(null); // ログイン状態の有無を管理
  const [loggedInUserName, setLoggedInUserName] = useState<string | null>(null); // ログイン状態のユーザ名を管理
  
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
      const res = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/user`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      console.log("ステータスコード:", res.status);
      if (res.status === 200) {
        setIsLggedIn(true);
        setLoggedInUserName(res.data.data.name);
      }
    } catch (error) {
      console.log("APIエラー:", error);
      setIsLggedIn(false);
    }
  };

  // チェック中なら何も表示しない
  if (isLoggedIn === null) return null;

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={isLoggedIn ? <Navigate to="/posts" /> : <Login setIsLoggedIn={setIsLggedIn} setLoggedInUserName={setLoggedInUserName} />} />
        <Route path="/account" element={<Register />} />
        <Route path="/posts" element={isLoggedIn ? <PostList loggedInUserName={loggedInUserName} /> : <Navigate to="/" />} />
        <Route path="/search" element={isLoggedIn ? <SearchPosts loggedInUserName={loggedInUserName} /> : <Navigate to="/" />} />
        {/* どのパスにも一致しなかった場合 */}
        <Route path="*" element={<NotFound />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
