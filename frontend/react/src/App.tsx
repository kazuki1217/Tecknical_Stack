// ログイン画面、ログイン後の画面、アカウント登録画面
import { useEffect, useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Register from './Register';
import Login from './Login';
import PostList from './PostList';

function App() {
  const [user, setUser] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // ローカルストレージからトークンを取得
    const token = localStorage.getItem('token');
    if (!token) {
      // トークンがない = 未ログイン → ローディング終了
      setLoading(false);
      return;
    }

    // トークンを使ってユーザー情報取得
    fetch('http://localhost:8000/api/loginsuccess', {
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    })
      .then(res => {
      console.log('ステータスコード:', res.status);
        return res.ok ? res.json() : null; // ステータス200系であればJSONとして取得
      })
      .then(data => {
        console.log('取得したユーザーデータ:', data);
        if (data) setUser(data.name); // ユーザー名をstateにセット（ログイン状態とみなす）
        setLoading(false); // ローディング完了
      });
  }, []);

  if (loading) return <p>読み込み中...</p>;

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={user ? <Navigate to="/posts" /> : <Login setUser={setUser} />} />
        <Route path="/account" element={<Register />} />
        <Route path="/posts" element={user ? <PostList user={user} /> : <Navigate to="/" />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
